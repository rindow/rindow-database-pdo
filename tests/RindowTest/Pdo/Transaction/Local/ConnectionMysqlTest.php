<?php
namespace RindowTest\Database\Pdo\Transaction\Local\ConnectionMysqlTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Rindow\Database\Dao\Support\ResultList;
use Rindow\Database\Pdo\Transaction\Local\Connection;

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

    public function testNestTransactionInsideRollback()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        try {
            $connection->beginTransaction();
            $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test','2014/01/18',1));
            $this->assertEquals(1,$insert);
            $connection->beginTransaction();
            $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test2','2014/01/18',2));
            $this->assertEquals(1,$insert);
            $connection->rollback();
            $connection->commit();
            $results = $connection->executeQuery("SELECT * FROM testdb");
            $count = 0;
            foreach($results as $row) {
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>'2014-01-18','ser'=>1),$row);
                $count++;
            }
            $this->assertEquals(1,$count);
        } catch(\Exception $e) {
            $connection->close();
            echo get_class($e)."\n";
            echo get_class($e->getPrevious())."\n";
            echo $e->getMessage()."\n";
            //throw $e;
        }
    }

    public function testNestTransactionOutsideRollback()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        try {
            $connection->beginTransaction();
            $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test','2014/01/18',1));
            $this->assertEquals(1,$insert);
            $connection->beginTransaction();
            $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test2','2014/01/18',2));
            $this->assertEquals(1,$insert);
            $connection->commit();
            $connection->rollback();
            $results = $connection->executeQuery("SELECT * FROM testdb");
            $count = 0;
            foreach($results as $row) {
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>'2014-01-18','ser'=>1),$row);
                $count++;
            }
            $this->assertEquals(0,$count);
        } catch(\Exception $e) {
            try {
                $connection->rollback();
            } catch(\Exception $ee) {}
            try {
                $connection->rollback();
            } catch(\Exception $ee) {}
            $connection->close();
            echo get_class($e)."\n";
            echo get_class($e->getPrevious())."\n";
            echo $e->getMessage()."\n";
            throw $e;
        }
    }

    public function testTransactionTimeout()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        try {
            $connection->beginTransaction();
            $connection->setTransactionTimeout(1000);
            $connection->commit();
        } catch(\Exception $e) {
            try {
                $connection->rollback();
            } catch(\Exception $ee) {}
            try {
                $connection->rollback();
            } catch(\Exception $ee) {}
            $connection->close();
            echo get_class($e)."\n";
            echo get_class($e->getPrevious())."\n";
            echo $e->getMessage()."\n";
            throw $e;
        }
        $this->assertTrue(true);
    }
}
