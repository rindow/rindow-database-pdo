<?php
namespace Rindow\Database\Pdo\Transaction\Xa;

use Rindow\Database\Dao\Exception;
use Rindow\Database\Pdo\DataSource as PdoDataSource;
use Rindow\Database\Pdo\Connection as PdoConnection;

class DataSource extends PdoDataSource
{
    protected $connectionClass = 'Rindow\Database\Pdo\Transaction\Xa\Connection';
/*
    protected function createConnectionInstance($className,$config,$driver)
    {
        $connection = parent::createConnectionInstance($className,$config,$driver);
        $connection->getEventManager()->attach(
            PdoConnection::EVENT_CONNECTED,
            array($this,'onConnected'),array(),$connection);
        return $connection;
    }

    public function onConnected($event)
    {
        $connection = $event->getTarget();
        $this->enlistToTransaction($connection);
    }

    protected function enlistToTransaction($connection)
    {
        if(!$connection->isConnected())
            return;
        parent::enlistToTransaction($connection);
    }
*/
    protected function getResource($connection)
    {
        return $connection->getXAResource();
    }
}
