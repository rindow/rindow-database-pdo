<?php
namespace Rindow\Database\Pdo\Transaction\Xa;

use Rindow\Database\Dao\Exception;
use Interop\Lenient\Transaction\Xa\XAResource as XAResourceInterface;
use Rindow\Transaction\Xa\XAException;
use PDOException;

class XAResource implements XAResourceInterface
{
    const DEFAULT_TIMEOUT = -1;

    const STATUS_NO_TRANSACTION = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_IDLE     = 2;
    const STATUS_PREPARED = 3;

    protected $status = self::STATUS_NO_TRANSACTION;
	protected $connection;
    protected $transactionTimeout = self::DEFAULT_TIMEOUT;
    protected $logger;
    protected $isDebug;

	public function __construct($connection=null)
	{
		if($connection)
			$this->setConnection($connection);
	}

	public function setConnection($connection)
	{
        if($connection==null)
            return;
		$this->connection = $connection;
        $platformName = $connection->getDriver()->getPlatform();
        $className = __NAMESPACE__.'\\Platforms\\'.ucfirst($platformName);
        if(!class_exists($className))
            throw new Exception\DomainException('this connection is not supported for the XAResource.');
        $this->xaPlatform = new $className();
        $this->setLogger($connection->getLogger());
        $this->setDebug($connection->isDebug());
	}

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setDebug($debug=true)
    {
        $this->isDebug = $debug;
    }

    public function getPlatform()
    {
        return $this->xaPlatform;
    }

    public function getStatus()
    {
        return $this->status;
    }

	protected function xidToString($xid)
	{
		return $xid->getGlobalTransactionId();
	}

    protected function doExecuteCommands($commands)
    {
        try {
            foreach ($commands as $command) {
                $this->connection->exec($command);
            }
        } catch(Exception\RuntimeException $e) {
            throw new XAException($e->getMessage(),0,$e);
        }
    }

    public function start(/* XidInterface */ $xid, $flags)
    {
        if($this->isDebug)
            $this->logger->debug('XAResource::start');
        $commands = $this->xaPlatform->start($this->xidToString($xid), $flags);
        $this->doExecuteCommands($commands);
        $this->status = self::STATUS_ACTIVE;
    }

    public function end(/* XidInterface */ $xid, $flags)
    {
        if($this->isDebug)
            $this->logger->debug('XAResource::end');
        $commands = $this->xaPlatform->end($this->xidToString($xid), $flags);
        $this->doExecuteCommands($commands);
        $this->status = self::STATUS_IDLE;
    }

    public function prepare(/* XidInterface */ $xid)
    {
        if($this->isDebug)
            $this->logger->debug('XAResource::prepare');
        $commands = $this->xaPlatform->prepare($this->xidToString($xid));
        $this->doExecuteCommands($commands);
        $this->status = self::STATUS_PREPARED;
        return XAResourceInterface::XA_OK;
    }

    public function commit(/* XidInterface */ $xid, $onePhase)
    {
        if($this->isDebug)
            $this->logger->debug('XAResource::commit');
        $commands = $this->xaPlatform->commit($this->xidToString($xid),$onePhase);
        $this->doExecuteCommands($commands);
        $this->status = self::STATUS_NO_TRANSACTION;
    }

    public function rollback(/* XidInterface */ $xid)
    {
        if($this->isDebug)
            $this->logger->debug('XAResource::rollback');
        $commands = $this->xaPlatform->rollback($this->xidToString($xid));
        $this->doExecuteCommands($commands);
        $this->status = self::STATUS_NO_TRANSACTION;
    }

    public function forget(/* XidInterface */ $xid)
    {
        if($this->isDebug)
            $this->logger->debug('XAResource::forget');
        $commands = $this->xaPlatform->forget($this->xidToString($xid));
        $this->doExecuteCommands($commands);
        $this->status = self::STATUS_NO_TRANSACTION;
    }

    public function getTransactionTimeout()
    {
        return $this->transactionTimeout;
    }

    public function setTransactionTimeout($seconds)
    {
        $this->transactionTimeout = $seconds;
        $commands = $this->xaPlatform->setTransactionTimeout($seconds);
        $this->doExecuteCommands($commands);
    }

    public function isSameRM(/* XAResourceInterface */ $xares)
    {
        return ($xares === $this) ? true : false;
    }

    public function recover($flag)
    {
        return $this->xaPlatform->recover($this->connection,$flag);
    }
}