<?php
namespace Rindow\Database\Pdo\Transaction\Xa\Platforms;

use Interop\Lenient\Transaction\Xa\XAResource as XAResourceInterface;
use Rindow\Transaction\Xa\XAException;
use Rindow\Transaction\Distributed\Xid;
use PDO;

class Mysql
{
    public function getName()
    {
        return 'mysql';
    }

    protected function transXid($xid)
    {
        //return $xid.':'.substr(spl_object_hash($this),8,8);
        return $xid;
    }
    
    public function start($xid, $flags)
    {
        $xid = $this->transXid($xid);
        switch ($flags) {
            case XAResourceInterface::TMNOFLAGS:
            case XAResourceInterface::TMJOIN:
                return array(
                    "XA START '$xid'"
                );
                break;
            default:
                throw new XAException('Invalid argument or not supported.',XAException::XAER_INVAL);
        }
    }

    public function end($xid, $flags)
    {
        $xid = $this->transXid($xid);
        switch ($flags) {
            case XAResourceInterface::TMSUCCESS:
            case XAResourceInterface::TMFAIL:
                return array(
                    "XA END '$xid'"
                );
                break;
            default:
                throw new XAException('Invalid argument',XAException::XAER_INVAL);
        }
    }

    public function prepare($xid)
    {
        $xid = $this->transXid($xid);
        return array(
            "XA PREPARE '$xid'"
        );
    }

    public function commit($xid, $onePhase)
    {
        $xid = $this->transXid($xid);
        if($onePhase) {
            return array(
                "XA COMMIT '$xid' ONE PHASE"
            );
        } else {
            return array(
                "XA COMMIT '$xid'"
            );
        }
    }

    public function rollback($xid)
    {
        $xid = $this->transXid($xid);
        return array(
            "XA ROLLBACK '$xid'"
        );
    }

    public function forget($xid)
    {
        throw new XAException('Invalid argument',XAException::XAER_INVAL);
    }

    public function setTransactionTimeout($seconds)
    {
        return array(
            "SET innodb_lock_wait_timeout = $seconds"
        );
    }

    public function recover($connection,$flag)
    {
        $sql = "XA RECOVER";
        $stmt = $connection->query($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $xidList = array();
        foreach ($stmt as $row) {
            $id = explode(':',$row['data']);
            $xidList[] = new Xid($id[0]);
        }
        return $xidList;
    }
}