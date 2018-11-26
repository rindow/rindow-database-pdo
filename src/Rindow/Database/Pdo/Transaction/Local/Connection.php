<?php
namespace Rindow\Database\Pdo\Transaction\Local;

use Interop\Lenient\Transaction\ResourceManager;
use Rindow\Database\Pdo\Connection as PdoConnection;
use Rindow\Database\Dao\Exception;

class Connection extends PdoConnection
{
    protected $resourceManager;
    protected $txNestLevel = 0;
    protected $savepointPrefix = 'rindow_tx_';

    protected function createResourceManager()
    {
        return new PdoResourceManager($this,$this->config);
    }

    public function getResourceManager()
    {
        if($this->resourceManager==null) {
            $this->resourceManager = $this->createResourceManager();
        }
        return $this->resourceManager;
    }

    public function beginTransaction()
    {
        if($this->txNestLevel==0) {
            parent::beginTransaction();
        } else {
            $savepoint = $this->savepointPrefix.$this->txNestLevel;
            $this->createSavepoint($savepoint);
        }
        $this->txNestLevel++;
    }

    public function commit()
    {
        $this->txNestLevel--;
        if($this->txNestLevel<0) {
            $this->txNestLevel++;
            throw new Exception\DomainException('No transaction', 1);
        } elseif($this->txNestLevel==0) {
            parent::commit();
        } else {
            $savepoint = $this->savepointPrefix.$this->txNestLevel;
            $this->releaseSavepoint($savepoint);
        }
    }

    public function rollback()
    {
        $this->txNestLevel--;
        if($this->txNestLevel<0) {
            $this->txNestLevel++;
            throw new Exception\DomainException('No transaction', 1);
        } elseif($this->txNestLevel==0) {
            parent::rollback();
        } else {
            $savepoint = $this->savepointPrefix.$this->txNestLevel;
            $this->rollbackSavepoint($savepoint);
        }
    }

    public function setTransactionTimeout($timeout)
    {
        $driver = $this->getDriver();
        $this->connect();
        $timeout = intval($timeout);
        $statements = $driver->setTransactionTimeout($timeout,$this->connection);
        foreach ($statements as $statement) {
            $this->exec($statement);
        }
    }
}
