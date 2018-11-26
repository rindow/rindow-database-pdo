<?php
namespace Rindow\Database\Pdo\Transaction\Xa\Platforms;

use Interop\Lenient\Transaction\Xa\XAResource as XAResourceInterface;
use Rindow\Transaction\Xa\XAException;
use Rindow\Transaction\Distributed\Xid;

use PDO;

class Pgsql
{
    protected $prepared;

    public function getName()
    {
        return 'pgsql';
    }
    
    protected function transXid($xid)
    {
        //return $xid.':'.substr(spl_object_hash($this),8,8);
        return $xid;
    }
    
    public function start($xid, $flags)
    {
        switch ($flags) {
            case XAResourceInterface::TMNOFLAGS:
            case XAResourceInterface::TMJOIN:
                return array(
                    "BEGIN"
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
                return array();
                break;
            default:
                throw new XAException('Invalid argument',XAException::XAER_INVAL);
        }
    }

    public function prepare($xid)
    {
        $xid = $this->transXid($xid);
        $this->prepared = $xid;
        return array(
            "PREPARE TRANSACTION '$xid'"
        );
    }

    public function commit($xid, $onePhase)
    {
        $xid = $this->transXid($xid);
        if($this->prepared || !$onePhase) {
            $this->prepared = false;
            return array(
                "COMMIT PREPARED '$xid'"
            );
        } else {
            return array(
                "COMMIT"
            );
        }
    }

    public function rollback($xid)
    {
        $xid = $this->transXid($xid);
        if($this->prepared) {
            $this->prepared = false;
            return array(
                "ROLLBACK PREPARED '$xid'"
            );
        } else {
            return array(
                "ROLLBACK"
            );
        }
    }

    public function forget($xid)
    {
        throw new XAException('Invalid argument',XAException::XAER_INVAL);
    }

    public function setTransactionTimeout($seconds)
    {
        return array(
            "SET LOCAL lock_timeout = $seconds"
        );
    }

    public function recover($connection,$flag)
    {
        $sql = "SELECT gid FROM pg_prepared_xacts";
        $stmt = $connection->query($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $xidList = array();
        foreach ($stmt as $row) {
            $id = explode(':',$row['gid']);
            $xidList[] = new Xid($id[0]);
        }
        return $xidList;
    }
}