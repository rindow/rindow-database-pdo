<?php
namespace RindowTest\Database\Pdo\Transaction\Distributed\XAResourcePgsqlTest;

use PHPUnit\Framework\TestCase;
use Rindow\Database\Pdo\Transaction\Xa\DataSource;
use Rindow\Transaction\Distributed\Xid;
use Interop\Lenient\Transaction\Xa\XAResource as XAResourceInterface;

class Test extends TestCase
{
    public static $skip = false;
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo_pgsql')) {
            self::$skip = 'pdo_pgsql extension not loaded';
            return;
        }
        try {
            $client = self::getPDOClientStatic();
        } catch(\Exception $e) {
            self::$skip = 'Connect failed to pgsql.';
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
        $dsn = "pgsql:host=127.0.0.1;dbname=".RINDOW_TEST_PGSQL_DBNAME;
        $username = RINDOW_TEST_PGSQL_USER;
        $password = RINDOW_TEST_PGSQL_PASSWORD;
        $options  = array();
        $client = new \PDO($dsn, $username, $password, $options);
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
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        $this->rollbackAll();
        $client = $this->getPDOClient();
        $client->exec("DROP TABLE IF EXISTS testdb");
        $client->exec("CREATE TABLE testdb ( id SERIAL PRIMARY KEY , name TEXT , day DATE, ser INTEGER UNIQUE)");
    }

    public function getConfig()
    {
        $config = array(
            'dsn' => 'pgsql:host=127.0.0.1;dbname='.RINDOW_TEST_PGSQL_DBNAME,
            'user'     => RINDOW_TEST_PGSQL_USER,
            'password' => RINDOW_TEST_PGSQL_PASSWORD,
        );
        return $config;
    }

    public function rollbackAll()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        $xaResource = $connection->getXAResource();
        $xids = $xaResource->recover(0);
        foreach ($xids as $xid) {
            $xaResource->rollback($xid);
        }
        $connection->close();
    }

    public function testDataSource()
    {
        //$this->markTestIncomplete('It will temporarily skip');
        //return;
        $config = $this->getConfig();
    	$dataSource = new DataSource($config);
    	$connection = $dataSource->getConnection();
        try {
        	$this->assertEquals(
                'Rindow\Database\Pdo\Transaction\Xa\Connection',
                get_class($connection));
            $this->assertEquals(
                'Rindow\Database\Pdo\Transaction\Xa\XAResource',
                get_class($connection->getXAResource())
            );
            $this->assertEquals(
                'Rindow\Database\Pdo\Transaction\Xa\Platforms\Pgsql',
                get_class($connection->getXAResource()->getPlatform())
            );
    
            $this->assertFalse($connection->isConnected());
    
            $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
        } catch(\Exception $e) {
            $connection->close();
            throw $e;
        }
        $this->assertTrue(true);
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
        } catch(\Exception $e) {
            echo get_class($e).':'.$e->getMessage()."\n";
            $xaResource->rollback($xid);
            $connection->close();
            echo 'close';
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
            $xaResource->rollback($xid);
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
            $result = $xaResource->recover(0);
            $this->assertEquals(0,count($result));
        } catch(\Exception $e) {
            $xaResource->rollback($xid);
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
            $xaResource->rollback($xid);
            $connection->close();
            throw $e;
        }
    }

    public function testSetTransactionTimeout()
    {
        if(getenv('POSTGRESQL_VERSION') && getenv('POSTGRESQL_VERSION')<"9.3") {
            $this->markTestSkipped('Postgresql before version 9.3 does not support lock_timeout.');
            return;
        }
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

    //**
    // * @expectedException        Rindow\Transaction\Distributed\XA\XAException
    //*/
    /****
        Postgresql is not handling this test.
        Because it not use "Xid" when it do "BEGIN",
        and it not have "END" command.

    public function testUnknownXid()
    {
        $config = $this->getConfig();
        $dataSource = new XADataSource($config);
        $connection = $dataSource->getConnection();
        $xaResource = $connection->getXAResource();

        $xid = new Xid('foo');
        $xidNone = new Xid('none');
        $xaResource->start($xid,XAResourceInterface::TMNOFLAGS);
        $connection->exec("INSERT INTO testdb (name) VALUES ('aaa') ");
        $xaResource->end($xid,XAResourceInterface::TMSUCCESS);
        $this->assertEquals(0,$this->countRow());
        $xaResource->prepare($xid);
        try {
            $xaResource->rollback($xidNone);
        } catch(\Exception $e) {
            // CAUTION:
            // Postgresql must commit or rollback before close.
            // It is strange.
            $xaResource->rollback($xid);

            // CAUTION: DoctrineDBAL must be closed a connection when it raise Exception.
            //          Don't known why.
            $connection->close();
            throw $e;
        }
        $xaResource->rollback($xid);
        $connection->close();
    }
    ****/
}