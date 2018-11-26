<?php
namespace Rindow\Database\Pdo\Transaction\Local;

use Interop\Lenient\Transaction\ResourceManager;
use Interop\Lenient\Transaction\TransactionDefinition;
use Rindow\Database\Dao\Exception;
use Rindow\Database\Pdo\Connection as PdoConnection;

class PdoResourceManager implements ResourceManager
{
    protected static $isolationLevels = array(
        TransactionDefinition::ISOLATION_READ_UNCOMMITTED => PdoConnection::ISOLATION_READ_UNCOMMITTED,
        TransactionDefinition::ISOLATION_READ_COMMITTED   => PdoConnection::ISOLATION_READ_COMMITTED,
        TransactionDefinition::ISOLATION_REPEATABLE_READ  => PdoConnection::ISOLATION_REPEATABLE_READ,
        TransactionDefinition::ISOLATION_SERIALIZABLE     => PdoConnection::ISOLATION_SERIALIZABLE,
    );
    protected $isolationLevel = TransactionDefinition::ISOLATION_DEFAULT;
    protected $timeout = -1;
    protected $connected = false;
    protected $logger;
    protected $debug;
    protected $name;
    protected $connection;
    protected $config;
    protected $preTxLevel = 0;

    public function __construct($connection,array $config)
    {
        $this->connection = $connection;
        $this->config = $config;
        $this->connected = $this->connection->isConnected();
        $this->logger = $this->connection->getLogger();
        $this->debug  = $this->connection->isDebug();
        if(isset($this->config['name'])) {
            $this->name = $this->config['name'];
        }
        $this->connection->getEventManager()->attach(
            PdoConnection::EVENT_CONNECTED,array($this,'onConnected'));
    }

    public function getName()
    {
        return $this->name;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function isNestedTransactionAllowed()
    {
        return true;
    }

    public function setReadOnly($readOnly)
    {
        // N/A
    }

    public function onConnected($connection)
    {
        if($this->connected)
            return;
        $this->connected = true;
        
        if($this->isolationLevel!=TransactionDefinition::ISOLATION_DEFAULT) {
            $this->connection->setIsolationLevel(self::$isolationLevels[$this->isolationLevel]);
        }
        if($this->timeout>=0) {
            $this->connection->setTransactionTimeout($this->timeout);
        }
        for($lvl=0; $lvl<$this->preTxLevel; $lvl++) {
            $this->connection->beginTransaction();
        }
    }

    public function setIsolationLevel($isolation)
    {
        if($isolation==TransactionDefinition::ISOLATION_DEFAULT)
            return;
        if($this->isolationLevel!=$isolation) {
            $this->isolationLevel = $isolation;
            if($this->connected) {
                $this->connection->setIsolationLevel(self::$isolationLevels[$isolation]);
            }
        }
    }

    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;
        if($this->timeout>=0) {
            if($this->connected) {
                $this->connection->setTransactionTimeout($this->timeout);
            }
        }
    }

    public function beginTransaction($definition=null)
    {
        if($definition) {
            if(($isolation=$definition->getIsolationLevel())>0) {
                $this->setIsolationLevel($isolation);
            }
            if($definition->isReadOnly())
                $this->setReadOnly(true);
            if(($timeout=$definition->getTimeout())>0)
                $this->setTimeout($timeout);
        }
        if($this->connected) {
            $this->connection->beginTransaction();
        } else {
            $this->preTxLevel++;
        }
    }

    public function commit()
    {
        if($this->connected) {
            $this->connection->commit();
        } else {
            if($this->preTxLevel<=0)
                throw new Exception\DomainException('No transaction', 1);
            $this->preTxLevel--;
        }
    }

    public function rollback()
    {
        if($this->connected) {
            $this->connection->rollback();
        } else {
            if($this->preTxLevel<=0)
                throw new Exception\DomainException('No transaction', 1);
            $this->preTxLevel--;
        }
    }

    public function suspend()
    {
        throw new Exception\DomainException('suspend operation is not supported.');
    }

    public function resume($txObject)
    {
        throw new Exception\DomainException('resume operation is not supported.');
    }
}
