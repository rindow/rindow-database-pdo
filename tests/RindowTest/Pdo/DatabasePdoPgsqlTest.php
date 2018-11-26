<?php
namespace RindowTest\Database\Pdo\DatabasePdoPgsqlTest;

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
        if (!extension_loaded('pdo_pgsql')) {
            self::$skip = 'pdo_pgsql not loaded.';
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

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        $client = $this->getPDOClient();
        $client->exec("DROP TABLE IF EXISTS testdb");
        $client->exec("CREATE TABLE testdb ( id SERIAL PRIMARY KEY , name TEXT , day DATE, ser INTEGER UNIQUE)");
    }
    public static function getPDOClientStatic()
    {
        $dsn = "pgsql:host=localhost;dbname=".RINDOW_TEST_PGSQL_DBNAME;
        $username = RINDOW_TEST_PGSQL_USER;
        $password = RINDOW_TEST_PGSQL_PASSWORD;
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
            'dsn' => "pgsql:host=localhost;dbname=".RINDOW_TEST_PGSQL_DBNAME,
            'user'     => RINDOW_TEST_PGSQL_USER,
            'password' => RINDOW_TEST_PGSQL_PASSWORD,
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
     * @expectedExceptionMessage SQLSTATE[42601]: Syntax error: 7 ERROR:
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
     * @expectedExceptionMessage SQLSTATE[08006] [7] could not connect to server:
     * @expectedExceptionCode    -29
     */
    public function testConnectFailedError()
    {
        $config = array(
            'dsn' => "pgsql:host=127.0.0.1;port=99;dbname=".RINDOW_TEST_PGSQL_DBNAME,
            'user'     => RINDOW_TEST_PGSQL_USER,
            'password' => RINDOW_TEST_PGSQL_PASSWORD,
        );
        $connection = new Connection($config);
        // 7
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[08006]
     * @expectedExceptionCode    -29
     */
    public function testLoginFailedError()
    {
        $config = array(
            'dsn' => "pgsql:host=127.0.0.1;dbname=".RINDOW_TEST_PGSQL_DBNAME,
            'user'     => RINDOW_TEST_PGSQL_USER,
            'password' => '',
        );
        $connection = new Connection($config);
        // 7
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[08006] [7] FATAL:
     * @expectedExceptionCode    -29
     */
    public function testNoDatabaseError()
    {
        $config = array(
            'dsn' => "pgsql:host=127.0.0.1;dbname=none",
            'user'     => RINDOW_TEST_PGSQL_USER,
            'password' => RINDOW_TEST_PGSQL_PASSWORD,
        );
        $connection = new Connection($config);
        // 7
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[08P01]:
     * @expectedExceptionCode    -7
     */
    public function testPrepareParameterError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        // 7
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
     * @expectedExceptionMessage SQLSTATE[42601]: Syntax error:
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
     * @expectedExceptionMessage SQLSTATE[42P01]: Undefined table:
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
     * @expectedExceptionMessage SQLSTATE[42703]: Undefined column:
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
     * @expectedExceptionMessage SQLSTATE[23502]: Not null violation:
     * @expectedExceptionCode    -28
     */
    public function testNotNullConstraintViolation()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "23502",    "Not null violation" }
        $connection->exec("INSERT INTO testdb (id,name) VALUES (null,'abc')");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[23505]: Unique violation:
     * @expectedExceptionCode    -5
     */
    public function testDuplicateError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,'abc')");
        //{ "23505",    "Unique violation" }
        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,'abc')");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[22P02]: Invalid text representation:
     * @expectedExceptionCode    -8
     */
    public function testTypeError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "22P02",    "Invalid text representation" }
        $connection->exec("INSERT INTO testdb (ser,name) VALUES ('a','abc')");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[22008]: Datetime field overflow:
     * @expectedExceptionCode    -12
     */
    public function testDateError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "22008",    "Datetime field overflow" }
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc','2014-40-50')");
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
        //{ "22007",    "Invalid datetime format" }
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc','2014#AA#AA')");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[42601]: Syntax error:
     * @expectedExceptionCode    -2
     */
    public function testValueCountOnRow()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "42601",    "Syntax error" }
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
}
