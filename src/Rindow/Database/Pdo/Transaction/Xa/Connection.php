<?php
namespace Rindow\Database\Pdo\Transaction\Xa;

use Rindow\Database\Pdo\Connection as PdoConnection;
use Rindow\Database\Dao\XAConnection;
use Rindow\Database\Dao\Exception;

class Connection extends PdoConnection implements XAConnection
{
    protected $xaResource;

    /**
    *  @return XAResource
    */
    public function getXAResource()
    {
        if($this->xaResource==null)
            $this->xaResource = new XAResource($this);
        return $this->xaResource;
    }

    /**
    *  @return Object
    */
    public function getConnection()
    {
        return $this;
    }

    /**
    *  @return void
    */
    public function close()
    {
        $this->xaResource = null;
        parent::close();
    }
}