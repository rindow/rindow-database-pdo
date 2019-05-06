<?php
namespace RindowTest\Database\Pdo\Transaction\Distributed\XAResourceMysqlTest;

use PHPUnit\Framework\TestCase;
use Rindow\Database\Pdo\Transaction\Xa\DataSource;
use Rindow\Transaction\Distributed\Xid;
use Interop\Lenient\Transaction\Xa\XAResource as XAResourceInterface;

class Test extends TestCase
{
    public static $skip = false;
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo_mysql')) {
            self::$skip = 'pdo_mysql extension not loaded';
            return;
        }
        try {
            $client = self::getPDOClientStatic();
        } catch(\Exception $e) {
            self::$skip = $e->getMessage();
            return;
        }
    }

    public static function tearDownAfterClass()
    {
        if(self::$skip)
            return;
        $client = self::getPDOClientStatic();
        $client->exec("DROP TABLE IF EXISTS testdb");
    }

    public static function getPDOClientStatic()
    {
        $dsn = "mysql:host=127.0.0.1;dbname=".RINDOW_TEST_MYSQL_DBNAME;
        $username = RINDOW_TEST_MYSQL_USER;
        $password = RINDOW_TEST_MYSQL_PASSWORD;
        $options  = array();
        @$client = new \PDO($dsn, $username, $password, $options);
        return $client;
    }
    public function getPDOClient()
    {
        return self::getPDOClientStatic();
    }

    public function countRow()
    {
        $client = $this->getPDOClient();
        $stmt = $client->query("SELECT * FROM testdb");
        $c = 0;
        foreach ($stmt as $row) {
            $c += 1;
        }
        return $c;
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        $client = $this->getPDOClient();
        $client->exec("DROP TABLE IF EXISTS testdb");
        $client->exec("CREATE TABLE testdb ( id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT NOT NULL, day DATE , ser INTEGER UNIQUE)");
    }

    public function getConfig()
    {
        $config = array(
            'dsn' => 'mysql:host=127.0.0.1;dbname='.RINDOW_TEST_MYSQL_DBNAME,
            'user'     => RINDOW_TEST_MYSQL_USER,
            'password' => RINDOW_TEST_MYSQL_PASSWORD,
        );
        return $config;
    }

    public function testDataSource()
    {
        $config = $this->getConfig();
    	$dataSource = new DataSource($config);
    	$connection = $dataSource->getConnection();
    	$this->assertEquals(
            'Rindow\Database\Pdo\Transaction\Xa\Connection',
            get_class($connection));
        $this->assertEquals(
            'Rindow\Database\Pdo\Transaction\Xa\XAResource',
            get_class($connection->getXAResource())
        );
        $this->assertEquals(
            'Rindow\Database\Pdo\Transaction\Xa\Platforms\Mysql',
            get_class($connection->getXAResource()->getPlatform())
        );

        $this->assertFalse($connection->isConnected());

        $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
    }

    public function testCommitNormal()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        try {
            $xaResource = $connection->getXAResource();
    
            $xid = new Xid('foo');
            $xaResource->start($xid,XAResourceInterface::TMNOFLAGS);
            $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
            $xaResource->end($xid,XAResourceInterface::TMSUCCESS);
            $this->assertEquals(XAResourceInterface::XA_OK,$xaResource->prepare($xid));
            $result = $xaResource->recover(0);
            $this->assertEquals(1,count($result));
            $this->assertEquals('foo',$result[0]->getGlobalTransactionId());
            $this->assertEquals(0,$this->countRow());
            $xaResource->commit($xid,false);
            $this->assertEquals(1,$this->countRow());
            $result = $xaResource->recover(0);
            $this->assertEquals(0,count($result));
        } catch(\Exception $e) {
            $connection->close();
            throw $e;
        }
    }

    public function testRollbackNormal()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        try {
            $xaResource = $connection->getXAResource();
    
            $xid = new Xid('foo');
            $xaResource->start($xid,XAResourceInterface::TMNOFLAGS);
            $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
            $xaResource->end($xid,XAResourceInterface::TMFAIL);
            $this->assertEquals(0,$this->countRow());
            $xaResource->rollback($xid);
            $this->assertEquals(0,$this->countRow());
        } catch(\Exception $e) {
            $connection->close();
            throw $e;
        }
    }

    public function testPrepareAndRollback()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        try {
            $xaResource = $connection->getXAResource();
    
            $xid = new Xid('foo');
            $xaResource->start($xid,XAResourceInterface::TMNOFLAGS);
            $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
            $xaResource->end($xid,XAResourceInterface::TMSUCCESS);
            $this->assertEquals(XAResourceInterface::XA_OK,$xaResource->prepare($xid));
            $result = $xaResource->recover(0);
            $this->assertEquals(1,count($result));
            $this->assertEquals('foo',$result[0]->getGlobalTransactionId());
            $this->assertEquals(0,$this->countRow());
            $xaResource->rollback($xid);
            $this->assertEquals(0,$this->countRow());
        } catch(\Exception $e) {
            $connection->close();
            throw $e;
        }
    }

    public function testCommitOnePhase()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        try {
            $xaResource = $connection->getXAResource();
    
            $xid = new Xid('foo');
            $xaResource->start($xid,XAResourceInterface::TMNOFLAGS);
            $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
            $xaResource->end($xid,XAResourceInterface::TMSUCCESS);
            $this->assertEquals(0,$this->countRow());
            $xaResource->commit($xid,true);
            $this->assertEquals(1,$this->countRow());
        } catch(\Exception $e) {
            $connection->close();
            throw $e;
        }
    }

    public function testSetTransactionTimeout()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        try {
            $xaResource = $connection->getXAResource();
    
            $xaResource->setTransactionTimeout(10);
        } catch(\Exception $e) {
            $connection->close();
            throw $e;
        }
        $this->assertTrue(true);
    }

    /**
     * @expectedException        Rindow\Transaction\Xa\XAException
    */
    public function testPrepareError()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        try {
            $xaResource = $connection->getXAResource();
    
            $xid = new Xid('foo');
            $xidNone = new Xid('none');
            $xaResource->start($xid,XAResourceInterface::TMNOFLAGS);
            $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
            $xaResource->end($xid,XAResourceInterface::TMSUCCESS);
            $this->assertEquals(0,$this->countRow());
            $xaResource->prepare($xidNone);
        } catch(\Exception $e) {
            // CAUTION: DoctrineDBAL must be closed a connection when it raise Exception.
            //          Don't known why.
            $connection->close();
            throw $e;
        }
    }
}