<?php
namespace Rindow\Database\Pdo;

use Rindow\Database\Dao\Exception;
use Interop\Lenient\Dao\Resource\DataSource as DataSourceInterface;

class DataSource implements DataSourceInterface
{
    protected $config;
    protected $driver;
    protected $transactionManager;
    protected $connection;
    protected $connectionClass = 'Rindow\Database\Pdo\Connection';
    protected $logger;
    protected $debug;

    public function __construct($config=null)
    {
        if($config)
            $this->setConfig($config);
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setConnectionClass($connectionClass)
    {
        $this->connectionClass = $connectionClass;
    }

    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setDebug($debug=true)
    {
        $this->debug = $debug;
    }

    public function setTransactionManager($transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    public function getTransactionManager()
    {
        return $this->transactionManager;
    }

    protected function createConnectionInstance($className,$config,$driver)
    {
        return new $className($config,$driver);
    }

    public function getConnection($username=null, $password=null)
    {
        if($this->connection) {
            $this->enlistToTransaction($this->connection);
            return $this->connection;
        }
        if(!$this->config)
            throw new Exception\DomainException('Data source is not configured.');
        $config = $this->config;
        if($username)
            $config['user'] = $username;
        if($password)
            $config['password'] = $password;
        $connection = $this->createConnectionInstance($this->connectionClass,$config,$this->driver);
        if($this->logger)
            $connection->setLogger($this->logger);
        if($this->debug)
            $connection->setDebug($this->debug);
        $this->enlistToTransaction($connection);
        $this->connection = $connection;
        return $connection;
    }

    protected function enlistToTransaction($connection)
    {
        if($this->transactionManager==null)
            return;
        $transaction = $this->transactionManager->getTransaction();
        if($transaction==null)
            return;
        $transaction->enlistResource($this->getResource($connection));
    }

    protected function getResource($connection)
    {
        return $connection;
    }
}
