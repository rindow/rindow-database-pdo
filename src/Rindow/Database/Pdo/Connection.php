<?php
namespace Rindow\Database\Pdo;

use PDO;
use PDOStatement;
use PDOException;
use Rindow\Database\Dao\Exception;
use Interop\Lenient\Dao\Query\ResultList as ResultListInterface;
use Rindow\Database\Dao\Sql\Connection as ConnectionInterface;
use Rindow\Database\Dao\Support\ResultList;
use Rindow\Database\Pdo\Driver\Driver as DriverInterface;
use Rindow\Event\EventManager;

class Connection implements ConnectionInterface
{
    const ISOLATION_READ_UNCOMMITTED = 1;
    const ISOLATION_READ_COMMITTED   = 2;
    const ISOLATION_REPEATABLE_READ  = 3;
    const ISOLATION_SERIALIZABLE     = 4;
    const EVENT_CONNECTED = 'connected';

    static $driverNames = array(
        'sqlite' => 'Rindow\Database\Pdo\Driver\Sqlite',
        'mysql'  => 'Rindow\Database\Pdo\Driver\Mysql',
        'pgsql'  => 'Rindow\Database\Pdo\Driver\Pgsql',
    );

    protected $connection;
    protected $config;
    protected $driver;
    protected $autoCommit = true;
    protected $invocationOnConnected;
    protected $eventManager;
    protected $logger;
    protected $debug;

    public static function addDriver($driverName,$className)
    {
        self::$driverNames[$driverName] = $className;
    }

    public function __construct(array $config,DriverInterface $driver=null)
    {
        $this->config = $config;
        $this->driver = $driver;
    }

    public function getEventManager()
    {
        if($this->eventManager==null) {
            $this->eventManager = new EventManager();
        }
        return $this->eventManager;
    }

    //public function setInvocationOnConnected($handler)
    //{
    //    $this->invocationOnConnected = $handler;
    //}

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function isDebug()
    {
        return $this->debug;
    }

    public function getDriver()
    {
        if($this->driver!==null)
            return $this->driver;
        $driverClass = null;
        if(!isset($this->config['dsn'])) {
            throw new Exception\DomainException("A dsn is not found in configuration.");
        }
        $dsn = $this->config['dsn'];
        if(!isset($this->config['driver'])) {
            $tmp = explode(':', $dsn);
            $driverName = $tmp[0];
            if(isset(self::$driverNames[$driverName]))
                $driverClass = self::$driverNames[$driverName];
        } else {
            if(isset(self::$driverNames[$this->config['driver']]))
                $driverClass = self::$driverNames[$this->config['driver']];
            else
                $driverClass = $this->config['driver'];
        }
        if($driverClass) {
            if(!class_exists($driverClass))
                throw new Exception\DomainException("A driver class is not found.: $driverClass");
            $this->driver = new $driverClass();
        }
        return $this->driver;
    }

    public function __call($name,array $args)
    {
        $this->connect();
        if(!method_exists($this->connection,$name))
            throw new Exception\DomainException('Call to undefined method '.$name.'()');
        if($this->debug) {
            if($name==='exec' || $name==='query' || $name==='prepare') {
                $this->logDebug('PDO:'.$name.':'.$args[0]);
            } else {
                $this->logDebug('PDO:'.$name);
            }
        }
        try {
            $result = call_user_func_array(array($this->connection,$name),$args);
        }
        catch(PDOException $e) {
            throw new Exception\RuntimeException($e->getMessage(),$this->errorCodeMapping($e->getCode(),$e->getMessage()),$e);
        }
        if($result instanceof PDOStatement)
            $result = new Statement($result,$this);
        return $result;
    }

    public function setAutoCommit($autoCommit)
    {
        $this->autoCommit = $autoCommit;
    }

    public function getAutoCommit()
    {
        return $this->autoCommit;
    }

    public function getRawConnection()
    {
        return $this->connection;
    }

    public function connect()
    {
        if($this->connection)
            return;
        if(!isset($this->config['dsn']))
            throw new Exception\DomainException("A dsn is not specified.");
        $dsn = $this->config['dsn'];
        if(!isset($this->config['user']))
            $user = null;
        else
            $user = $this->config['user'];
        if(!isset($this->config['password']))
            $password = null;
        else
            $password = $this->config['password'];
        if(!isset($this->config['options']))
            $options = array();
        else
            $options = $this->config['options'];
        $this->getDriver();
        
        $this->createPDOConnection($dsn ,$user, $password, $options);
        if(!isset($this->config['options'][PDO::ATTR_ERRMODE]))
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if(!isset($this->config['options'][PDO::ATTR_DEFAULT_FETCH_MODE]))
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
        //if($this->invocationOnConnected)
        //    call_user_func($this->invocationOnConnected,$this);
        if($this->eventManager)
            $this->eventManager->notify(self::EVENT_CONNECTED,array(),$this);
    }

    protected function createPDOConnection($dsn ,$user, $password, $options)
    {
        try {
            $this->connection = new PDO($dsn ,$user, $password, $options);
        }
        catch(PDOException $e) {
            throw new Exception\RuntimeException($e->getMessage(),$this->errorCodeMapping($e->getCode(),$e->getMessage()),$e);
        }
    }

    public function isConnected()
    {
        return $this->connection ? true : false;
    }

    public function errorCodeMapping($code,$message)
    {
        $driver = $this->getDriver();
        if($driver)
            return $driver->errorCodeMapping($code,$message);
        return Exception\ExceptionInterface::ERROR;
    }

    public function getDriverName()
    {
        return $this->getDriver()->getPlatform();
    }

    public function executeQuery($sql,array $params=null,
        $fetchMode=null,$fetchClass=null,array $constructorArgs=null,
        /*ResultListInterface*/ $resultList=null)
    {
        $statement = $this->prepare($sql);
        if($params===null)
            $params = array();
        $statement->execute($params);
        if($fetchMode!==null) {
            $statement->setFetchMode($fetchMode,$fetchClass,$constructorArgs);
        }
        if($resultList===null)
            $resultList = new ResultList(array($statement,'fetch'));
        else
            $resultList->setFetchFunction(array($statement,'fetch'));
        return $resultList;
    }

    public function executeUpdate($sql,array $params=null)
    {
        $statement = $this->prepare($sql);
        if($params===null)
            $params = array();
        $statement->execute($params);
        return $statement->rowCount();
    }

    public function isFetchClassSupported()
    {
        return true;
    }

    public function getLastInsertId($table=null,$column=null)
    {
        if($this->getDriver()->getPlatform()==='pgsql')
            return $this->connection->lastInsertId($table.'_'.$column.'_seq');
        else
            return $this->connection->lastInsertId();
    }

    public function createSavepoint($savepoint)
    {
        $driver = $this->getDriver();
        $statements = $driver->createSavePointStatements($savepoint);
        foreach ($statements as $statement) {
            $this->exec($statement);
        }
    }

    public function releaseSavepoint($savepoint)
    {
        $driver = $this->getDriver();
        $statements = $driver->releaseSavepointStatements($savepoint);
        foreach ($statements as $statement) {
            $this->exec($statement);
        }
    }

    public function rollbackSavepoint($savepoint)
    {
        $driver = $this->getDriver();
        $statements = $driver->rollbackSavepointStatements($savepoint);
        foreach ($statements as $statement) {
            $this->exec($statement);
        }
    }

    public function setIsolationLevel($level)
    {
        $driver = $this->getDriver();
        $level = intval($level);
        $statements = $driver->setTransactionIsolationLevelStatements($level);
        foreach ($statements as $statement) {
            $this->exec($statement);
        }
    }

    public function close()
    {
        if($this->connection) {
            $this->connection = null;
        }
    }

    protected function logDebug($message, array $context = array())
    {
        if(!$this->debug || $this->logger==null)
            return;
        if(empty($context))
            $context = array('class'=>get_class($this));
        $this->logger->debug($message,$context);
    }
}
