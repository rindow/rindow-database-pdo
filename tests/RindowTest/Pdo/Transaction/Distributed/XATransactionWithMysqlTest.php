<?php
namespace RindowTest\Database\Pdo\Transaction\Distributed\XATransactionWithMysqlTest;

use PHPUnit\Framework\TestCase;
use Rindow\Transaction\Distributed\TransactionManager;

use Rindow\Database\Pdo\Transaction\Xa\DataSource;

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
        $client->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE,\PDO::FETCH_ASSOC);
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

    public function testCommitNormal()
    {
    	//$logger = new Logger('test');
    	$config = $this->getConfig();
    	$tm = new TransactionManager();
    	//$tm->setLogger($logger);
        $ds = new DataSource($config);
    	$ds->setTransactionManager($tm);
        //$ds2 = new DataSource($config);
    	//$ds2->setTransactionManager($tm);

        try {
        	$tm->begin();
        	$conn = $ds->getConnection();
        	//$conn2 = $ds2->getConnection();
    
            //$this->assertFalse($conn->isConnected());
            $this->assertTrue($conn->isConnected());
    
            $conn->exec("INSERT INTO testdb (name) VALUES ('aaa1') ");
    	    //$conn2->exec("INSERT INTO testdb (name) VALUES ('aaa2') ");
    
            $this->assertEquals(0,$this->countRow());
        	$tm->commit();
            $this->assertEquals(1,$this->countRow());
            //$this->assertEquals(2,$this->countRow());
        } catch(\Exception $e) {
            $conn->close();
            throw $e;
        }
    }

    public function testRollbackNormal()
    {
        //$logger = new Logger('test');
        $config = $this->getConfig();
        $tm = new TransactionManager();
        //$tm->setLogger($logger);
        $ds = new DataSource($config);
        $ds->setTransactionManager($tm);
        //$ds2 = new DataSource($config);
        //$ds2->setTransactionManager($tm);

        try {
            $tm->begin();
            $conn = $ds->getConnection();
            //$conn2 = $ds2->getConnection();
    
            //$this->assertFalse($conn->isConnected());
            $this->assertTrue($conn->isConnected());
    
            $conn->exec("INSERT INTO testdb (name) VALUES ('aaa1') ");
            //$conn2->exec("INSERT INTO testdb (name) VALUES ('aaa2') ");
    
            $this->assertEquals(0,$this->countRow());
            $tm->rollback();
            $this->assertEquals(0,$this->countRow());
        } catch(\Exception $e) {
            $conn->close();
            throw $e;
        }
    }

    public function testCommitSavepoint()
    {
        //$logger = new Logger('test');
        //$sqllogger = new SQLLogger($logger);
        $config = $this->getConfig();
        $ds = new DataSource($config);
        //$ds->setLogger($logger);
        //$ds->setDebug(true);
        $tm = new TransactionManager();
        //$tm->setLogger($logger);
        $ds->setTransactionManager($tm);

        $savepoint = 'foo';
        try {
            $tm->begin();
            $conn = $ds->getConnection();
            $this->assertEquals('Rindow\Database\Pdo\Transaction\Xa\Connection',get_class($conn));
            //$conn->getConfiguration()->setSQLLogger($sqllogger);
    
            //$this->assertFalse($conn->isConnected());
            $this->assertTrue($conn->isConnected());
    
            $conn->createSavepoint($savepoint);
    
            $this->assertTrue($conn->isConnected());
    
            $conn->exec("INSERT INTO testdb (name) VALUES ('aaa1') ");
    
            $conn->releaseSavepoint($savepoint);
    
            $this->assertEquals(0,$this->countRow());
            $tm->commit();
            $this->assertEquals(1,$this->countRow());
        } catch(\Exception $e) {
            $conn->close();
            throw $e;
        }
    }

    public function testRollbackSavepoint()
    {
        //$logger = new Logger('test');
        //$sqllogger = new SQLLogger($logger);
        $config = $this->getConfig();
        $ds = new DataSource($config);
        //$ds->setLogger($logger);
        //$ds->setDebug(true);
        $tm = new TransactionManager();
        //$tm->setLogger($logger);
        $ds->setTransactionManager($tm);

        $savepoint = 'foo';
        try {
            $tm->begin();
            $conn = $ds->getConnection();
            $this->assertEquals('Rindow\Database\Pdo\Transaction\Xa\Connection',get_class($conn));
            //$conn->getConfiguration()->setSQLLogger($sqllogger);
    
            //$this->assertFalse($conn->isConnected());
            $this->assertTrue($conn->isConnected());
    
            $conn->createSavepoint($savepoint);
    
            $this->assertTrue($conn->isConnected());
    
            $conn->exec("INSERT INTO testdb (name) VALUES ('aaa1') ");
    
            $conn->rollbackSavepoint($savepoint);
    
            $this->assertEquals(0,$this->countRow());
            $tm->commit();
            $this->assertEquals(0,$this->countRow());
        } catch(\Exception $e) {
            $conn->close();
            throw $e;
        }
    }
}