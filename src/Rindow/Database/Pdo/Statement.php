<?php
namespace Rindow\Database\Pdo;

use PDO;
use PDOStatement;
use PDOException;
use IteratorAggregate;
use Rindow\Database\Dao\Exception;
use Rindow\Database\Dao\Support\ResultList;
use Rindow\Database\Pdo\Driver\DriverInterface;

class Statement implements IteratorAggregate
{
    protected $statement;
    protected $connection;
    protected $resultList;

    public function __construct(PDOStatement $statement,Connection $connection,$resultList=null)
    {
        $this->statement = $statement;
        $this->connection = $connection;
        $this->resultList = $resultList;
    }

    public function __call($name,array $args)
    {
        if(!method_exists($this->statement,$name))
            throw new Exception\DomainException('Call to undefined method '.$name.'()');
        if($this->connection->isDebug() && $this->connection->getLogger()) {
            $this->connection->getLogger()->debug('PDOStatement:'.$name);
        }
        try {
            $result = call_user_func_array(array($this->statement,$name),$args);
        }
        catch(PDOException $e) {
            throw new Exception\RuntimeException($e->getMessage(),$this->connection->errorCodeMapping($e->getCode(),$e->getMessage()),$e);
        }
        return $result;
    }

    public function execute(array $parameters=null)
    {
        try {
            if($parameters===null)
                $parameters = array();
            if(!$this->connection->getAutoCommit() &&
                !$this->connection->inTransaction()    ) {
                $this->connection->beginTransaction();
            }
            if($this->connection->isDebug() && $this->connection->getLogger()) {
                $this->connection->getLogger()->debug('PDOStatement:execute');
            }
            return $this->statement->execute($parameters);
        }
        catch(PDOException $e) {
            throw new Exception\RuntimeException($e->getMessage(),$this->connection->errorCodeMapping($e->getCode(),$e->getMessage()),$e);
        }
    }

    public function setFetchMode($fetchMode,$fetchClass=null,array $constructorArgs=null)
    {
        if($this->connection->isDebug() && $this->connection->getLogger()) {
            $this->connection->getLogger()->debug('PDOStatement:setFetchMode');
        }
        try {
            if($fetchMode===PDO::FETCH_CLASS) {
                if($constructorArgs===null)
                    $constructorArgs=array();
                $this->statement->setFetchMode($fetchMode,$fetchClass,$constructorArgs);
            } else if($fetchMode===PDO::FETCH_INTO || $fetchMode===PDO::FETCH_COLUMN) {
                $this->statement->setFetchMode($fetchMode,$fetchClass);
            } else {
                $this->statement->setFetchMode($fetchMode);
            }
        }
        catch(PDOException $e) {
            throw new Exception\RuntimeException($e->getMessage(),$this->connection->errorCodeMapping($e->getCode(),$e->getMessage()),$e);
        }
    }

    public function setResultList($resultList)
    {
        $this->resultList = $resultList;
    }

    public function getIterator()
    {
        $resultList = $this->resultList;
        if($resultList===null)
            $resultList = new ResultList(array($this,'fetch'));
        else
            $resultList->setFetchFunction(array($this,'fetch'));
        return $resultList;
    }
}