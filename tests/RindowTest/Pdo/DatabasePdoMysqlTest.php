<?php
namespace RindowTest\Database\Pdo\DatabasePdoMysqlTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Rindow\Database\Dao\Support\ResultList;
use Rindow\Database\Pdo\Connection;
use Rindow\Database\Pdo\Driver\Sqlite;

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

	public function testNormal()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("INSERT INTO testdb (name,day,ser) values ('test','2014-01-31',1)");
		$statement = $connection->prepare("SELECT * FROM testdb");
		$statement->execute();
        $count = 0;
		while($row=$statement->fetch()) {
			$this->assertEquals(array('id'=>'1','name'=>'test','day'=>'2014-01-31','ser'=>1),$row);
            $count++;
		}
        $this->assertEquals(1,$count);
	}

	public function testResultList()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("INSERT INTO testdb (name,day,ser) values ('test','2014-01-31',1)");
		$statement = $connection->prepare("SELECT * FROM testdb");
		$statement->execute();
		$results = new ResultList(array($statement,'fetch'));
        $count = 0;
		foreach($results as $row) {
			$this->assertEquals(array('id'=>1,'name'=>'test','day'=>'2014-01-31','ser'=>1),$row);
            $count++;
		}
        $this->assertEquals(1,$count);
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax
     * @expectedExceptionCode    -2
     */
	public function testConvertErrorCode()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("ERRORSTATEMENT");
	}


    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000] [2002]
     * @expectedExceptionCode    -24
     */
    public function testConnectFailedError()
    {
        $config = array(
            'dsn' => "mysql:host=127.0.0.1;port=99;dbname=".RINDOW_TEST_MYSQL_DBNAME,
            'user'     => RINDOW_TEST_MYSQL_USER,
            'password' => RINDOW_TEST_MYSQL_PASSWORD,
        );
        $connection = new Connection($config);
        // 2002
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionCode    -29
     * ###expectedExceptionMessage SQLSTATE[HY000] [1045] Access denied for user
     */
    public function testLoginFailedError()
    {
        $config = array(
            'dsn' => "mysql:host=127.0.0.1;dbname=".RINDOW_TEST_MYSQL_DBNAME,
            'user'     => RINDOW_TEST_MYSQL_USER,
            'password' => '',
        );
        $connection = new Connection($config);
        // 1045
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionCode    -27
     * ##expectedExceptionMessage SQLSTATE[HY000] [1049] Unknown database
     */
    public function testNoDatabaseError()
    {
        $config = array(
            'dsn' => "mysql:host=127.0.0.1;dbname=none",
            'user'     => RINDOW_TEST_MYSQL_USER,
            'password' => RINDOW_TEST_MYSQL_PASSWORD,
        );
        $connection = new Connection($config);
        // 1049
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionCode    -7
     * @expectedExceptionMessage SQLSTATE[HY093]: Invalid parameter number:
     */
    public function testPrepareParameterError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $statement = $connection->prepare("SELECT * FROM testdb WHERE id = :id");
        $statement->execute(array());
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY093]: Invalid parameter number:
     * @expectedExceptionCode    -7
     */
    public function testPrepareParameterMismatchError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $statment = $connection->prepare("SELECT * FROM testdb WHERE name = :name");
        $statment->execute(array(':nonono'=>'foo'));
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation:
     * @expectedExceptionCode    -2
     */
    public function testSyntaxError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "42601",    "Syntax error" }
        $connection->exec("SYNTAX ERROR");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found:
     * @expectedExceptionCode    -18
     */
    public function testTableNotFound()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "42P01",    "Undefined table" }
        $connection->query("SELECT * FROM notable");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[42S22]: Column not found:
     * @expectedExceptionCode    -19
     */
    public function testColumnNotFound()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "42703",    "Undefined column" }
        $connection->query("SELECT none FROM testdb");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation:
     * @expectedExceptionCode    -28
     */
    public function testNotNullConstraintViolation()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "23000",    "Integrity constraint violation" }
        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,null)");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation:
     * @expectedExceptionCode    -5
     */
    public function testPrimaryKeyError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,'abc')");
        //{ "23000",    "Integrity constraint violation" }
        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,'abc')");
    }


    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation:
     * @expectedExceptionCode    -5
     */
    public function testDuplicateError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->exec("INSERT INTO testdb (id,name,day,ser) VALUES (1,'abc',null,1)");
        //{ "23000",    "Integrity constraint violation" }
        $connection->exec("INSERT INTO testdb (id,name,day,ser) VALUES (2,'abc',null,1)");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1366 Incorrect integer value: 'a' for column 'ser' at row 1
     * @expectedExceptionCode    -1
     */
    public function testTypeError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //IF Mysql<5.6 THEN { "00000",    "No error" },
        $connection->exec("INSERT INTO testdb (ser,name) VALUES ('a','abc')");
        $statment = $connection->query("SELECT * FROM testdb");
        $value = $statment->fetch();
        $this->assertEquals(0,$value['ser']);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[22007]: Invalid datetime format:
     * @expectedExceptionCode    -12
     */
    public function testDateError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //IF Mysql<5.6 THEN { "00000",    "No error" },
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc','2014-40-50')");
        $statment = $connection->query("SELECT * FROM testdb");
        $value = $statment->fetch();
        $this->assertEquals('0000-00-00',$value['day']);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[22007]: Invalid datetime format:
     * @expectedExceptionCode    -12
     */
    public function testDate2Error()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //IF Mysql<5.6 THEN { "00000",    "No error" },
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc','2014#AA#AA')");
        $statment = $connection->query("SELECT * FROM testdb");
        $value = $statment->fetch();
        $this->assertEquals('0000-00-00',$value['day']);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[21S01]: Insert value list does not match column list:
     * @expectedExceptionCode    -22
     */
    public function testValueCountOnRow()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "21S01",    "Insert value list does not match column list" },
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc')");
    }

    public function testReleaseSavepoint()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->beginTransaction();
        $connection->createSavepoint('testtest');
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (:name,:day,:ser)",array(':name'=>'test',':day'=>'2014-01-31',':ser'=>1));
        $this->assertEquals(1,$insert);
        $connection->releaseSavepoint('testtest');
        $connection->commit();
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testRollbackSavepoint()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->beginTransaction();
        $connection->createSavepoint('testtest');
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (:name,:day,:ser)",array(':name'=>'test',':day'=>'2014-01-31',':ser'=>1));
        $this->assertEquals(1,$insert);
        $connection->rollbackSavepoint('testtest');
        $connection->commit();
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
    }

    public function testSameSavepointName()
    {
        $config = $this->getConfig();
        $conn1 = new Connection($config);
        $conn2 = new Connection($config);
        try {
            $conn1->beginTransaction();
            $conn2->beginTransaction();
            $conn1->createSavepoint('testtest');
            $conn2->createSavepoint('testtest');
            $insert = $conn1->executeUpdate("INSERT INTO testdb (name,day,ser) values (:name,:day,:ser)",array(':name'=>'test',':day'=>'2014-01-31',':ser'=>1));
            $insert = $conn2->executeUpdate("INSERT INTO testdb (name,day,ser) values (:name,:day,:ser)",array(':name'=>'test',':day'=>'2014-01-31',':ser'=>2));
            $conn1->releaseSavepoint('testtest');
            $conn2->releaseSavepoint('testtest');
            $conn1->commit();
            $conn2->commit();
        } catch(\Exception $e) {
            echo get_class($e)."\n";
            echo get_class($e->getPrevious())."\n";
            echo $e->getMessage()."\n";
        }
        $results = $conn1->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $count++;
        }
        $this->assertEquals(2,$count);
    }
}
