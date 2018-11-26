<?php
namespace Rindow\Database\Pdo\Transaction\Local;

use Rindow\Database\Pdo\DataSource as PdoDataSource;

class DataSource extends PdoDataSource
{
    protected function getResource($connection)
    {
        return $connection->getResourceManager();
    }
}