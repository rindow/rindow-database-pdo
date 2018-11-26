<?php
namespace RindowTest\Database\Pdo\Transaction\Local\PdoResourceManagerTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Rindow\Database\Pdo\Transaction\Local\Connection;
use Rindow\Database\Pdo\Transaction\Local\PdoResourceManager;
use Interop\Lenient\Transaction\TransactionDefinition as TransactionDefinitionInterface;
use Rindow\Transaction\Support\TransactionDefinition;

class TestException extends \Exception
{}

class TestClass
{
    public $id;
    public $name;
    public $day;
    public $ser;
    public $option;

    public function __construct($option = null)
    {
        $this->option = $option;
    }
}

class TestPDO
{
    public $log = array();
    public $throwError;
    public function __call($name,array $args)
    {
        $this->log[] = $name;
    }
    public function setAttribute($name,$value='')
    {
    }
    public function exec($statement)
    {
        $this->log[] = 'exec('.$statement.')';
        if($this->throwError && strpos($statement, 'SAVEPOINT')!==false)
            throw new TestException('SAVEPOINT error');
    }
    public function beginTransaction()
    {
        $this->log[] = 'beginTransaction';
        if($this->throwError)
            throw new TestException('beginTransaction error');
    }
    public function commit()
    {
        $this->log[] = 'commit';
        if($this->throwError)
            throw new TestException('commit error');
    }
    public function rollback()
    {
        $this->log[] = 'rollback';
        if($this->throwError)
            throw new TestException('rollback error');
    }
}

class TestConnection extends Connection
{
    protected function createPDOConnection($dsn ,$user, $password, $options)
    {
        $this->connection = new TestPDO();
    }
    public function txNestLevel()
    {
        return $this->txNestLevel;
    }
    protected function createResourceManager()
    {
        return new TestResourceManager($this,$this->config);
    }
}

class TestResourceManager extends PdoResourceManager
{
    public function isConnected()
    {
        return $this->connected;
    }
    public function getPreTxLevel()
    {
        return $this->preTxLevel;
    }
}

class Test extends TestCase
{
    static $RINDOW_TEST_DATA;
    public static $skip = false;
    public static function setUpBeforeClass()
    {
    }

    public static function tearDownAfterClass()
    {
    }

    public function setUp()
    {
    }

    public function getConfig()
    {
        $config = array(
            'dsn' => "mysql:host=127.0.0.1;dbname=".RINDOW_TEST_MYSQL_DBNAME,
            'user'     => RINDOW_TEST_MYSQL_USER,
            'password' => RINDOW_TEST_MYSQL_PASSWORD,
            'options' => array(
                //PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ),
        );
        return $config;
    }

    public function testConnectUntilExecuteCommand()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $resource = $connection->getResourceManager();

        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertEquals(0,$resource->getPreTxLevel());
        $definition = new TransactionDefinition();
        $definition->setIsolationLevel(TransactionDefinitionInterface::ISOLATION_SERIALIZABLE);
        $definition->setTimeout(10);
        $resource->beginTransaction($definition);
        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertNull($connection->getRawConnection());
        $this->assertEquals(1,$resource->getPreTxLevel());
        $resource->commit();
        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertNull($connection->getRawConnection());
        $this->assertEquals(0,$resource->getPreTxLevel());
        $resource->beginTransaction($definition);
        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertNull($connection->getRawConnection());
        $this->assertEquals(1,$resource->getPreTxLevel());
        $resource->rollback();
        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertNull($connection->getRawConnection());
        $this->assertEquals(0,$resource->getPreTxLevel());
    }

    public function testLazyConnect()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $resource = $connection->getResourceManager();

        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertEquals(0,$resource->getPreTxLevel());
        $definition = new TransactionDefinition();
        $definition->setIsolationLevel(TransactionDefinitionInterface::ISOLATION_SERIALIZABLE);
        $definition->setTimeout(10);
        $resource->beginTransaction($definition);
        $resource->beginTransaction();
        $this->assertFalse($connection->isConnected());
        $this->assertFalse($resource->isConnected());
        $this->assertNull($connection->getRawConnection());
        $this->assertEquals(2,$resource->getPreTxLevel());

        $connection->exec('TEST');
        $this->assertTrue($connection->isConnected());
        $this->assertTrue($resource->isConnected());
        $this->assertEquals(2,$resource->getPreTxLevel());
        $this->assertEquals(array(
                'exec(SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE)',
                'exec(SET innodb_lock_wait_timeout=10)',
                'beginTransaction',
                'exec(SAVEPOINT rindow_tx_1)',
                'exec(TEST)',
            ),
            $connection->getRawConnection()->log);

        $connection->getRawConnection()->log = array();
        $resource->commit();
        $resource->rollback();
        $this->assertTrue($connection->isConnected());
        $this->assertTrue($resource->isConnected());
        $this->assertEquals(2,$resource->getPreTxLevel());
        $this->assertEquals(array(
                'exec(RELEASE SAVEPOINT rindow_tx_1)',
                'rollback',
            ),
            $connection->getRawConnection()->log);
    }

    public function testCreateFromConnectedConnection()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $connection->exec('TEST');
        $this->assertTrue($connection->isConnected());

        $resource = $connection->getResourceManager();
        $this->assertTrue($resource->isConnected());
        $this->assertEquals(0,$resource->getPreTxLevel());

        $definition = new TransactionDefinition();
        $definition->setIsolationLevel(TransactionDefinitionInterface::ISOLATION_SERIALIZABLE);
        $definition->setTimeout(10);
        $resource->beginTransaction($definition);
        $this->assertTrue($resource->isConnected());
        $this->assertEquals(0,$resource->getPreTxLevel());
        $this->assertEquals(array(
                'exec(TEST)',
                'exec(SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE)',
                'exec(SET innodb_lock_wait_timeout=10)',
                'beginTransaction',
            ),
            $connection->getRawConnection()->log);
    }
}
