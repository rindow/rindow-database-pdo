<?php
namespace RindowTest\Database\Pdo\DatabasePdoSqliteTest;

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
    static $RINDOW_TEST_DATA;
    public static $skip = false;
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::$skip = 'pdo_sqlite extension not loaded';
            return;
        }
        self::$RINDOW_TEST_DATA = __DIR__.'/../../data';
        //try {
            $dsn = "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite";
            $username = null;
            $password = null;
            $options  = array();
            $client = new \PDO($dsn, $username, $password, $options);
        //} catch(\Exception $e) {
        //    self::$skip = $e->getMessage();
        //    return;
        //}
    }

    public static function tearDownAfterClass()
    {
        if(self::$skip)
            return;
        $dsn = "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite";
        $username = null;
        $password = null;
        $options  = array();
        $client = new \PDO($dsn, $username, $password, $options);
        $client->exec("DROP TABLE IF EXISTS testdb");
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        $dsn = "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite";
        $username = null;
        $password = null;
        $options  = array();
        $client = new \PDO($dsn, $username, $password, $options);
        $client->exec("DROP TABLE IF EXISTS testdb");
        $client->exec("CREATE TABLE testdb ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, day DATE , ser INTEGER UNIQUE)");
    }

    public function getConfig()
    {
        $config = array(
            'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
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
		$connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
		$statement = $connection->prepare("SELECT * FROM testdb");
		$statement->execute();
        $count = 0;
		while($row=$statement->fetch()) {
			$this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
		}
        $this->assertEquals(1,$count);
	}

	public function testResultList()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
		$statement = $connection->prepare("SELECT * FROM testdb");
		$statement->execute();
		$results = new ResultList(array($statement,'fetch'));
        $count = 0;
		foreach($results as $row) {
			$this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
		}
        $this->assertEquals(1,$count);
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 near "ERRORSTATEMENT": syntax error
     * @expectedExceptionCode    -2
     */
	public function testErrorCodeMappingSyntaxError()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("ERRORSTATEMENT");
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000] [14] unable to open database file
     * @expectedExceptionCode    -24
     */
    public function testConnectFailedError()
    {
        $config = array(
            'dsn' => "sqlite:".".",
        );
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (id,name) VALUES ( 1,'boo' )");
    }

    ///**
    // * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
    // * @expectedExceptionMessage sql execute error.:
    // * @expectedExceptionCode    -7
    // */
    public function testPrepareParameterNumberError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (id, name, ser) VALUES (1, 'abc', :ser )");
        $statement = $connection->query("SELECT * FROM testdb");
        $value = $statement->fetch();
        $this->assertNull($value['ser']);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * //expectedExceptionMessage SQLSTATE[HY000]: General error: 25 bind or column index out of range
     * @expectedExceptionMessage  SQLSTATE[HY000]: General error:
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
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 near "SYNTAX": syntax error
     * @expectedExceptionCode    -2
     */
    public function testSyntaxError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "HY000",    "General error" },: syntax error
        $connection->exec("SYNTAX ERROR");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 no such table: notable
     * @expectedExceptionCode    -18
     */
    public function testTableNotFound()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "HY000",    "General error" },no such table:
        $connection->query("SELECT * FROM notable");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 no such column: none
     * @expectedExceptionCode    -19
     */
    public function testColumnNotFound()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "HY000",    "General error" },no such column:
        $connection->query("SELECT none FROM testdb");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation: 19
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
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation: 19
     * @expectedExceptionCode    -5
     */
    public function testDuplicateError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,'abc')");
        //{ "23000",    "Integrity constraint violation" }
        $connection->exec("INSERT INTO testdb (id,name) VALUES (1,'abc')");
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 20 datatype mismatch
     * @expectedExceptionCode    -8
     */
    public function testTypeError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "HY000",    "General error" },datatype mismatch
        $connection->exec("INSERT INTO testdb (id,name) VALUES ('a','abc')");
    }

    ///**
    // * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
    // * @expectedExceptionMessage sql execute error.:
    // * @expectedExceptionCode    -12
    // */
    public function testDateError()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "00000",    "No error" },
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc','2014-40-50')");
        $statement = $connection->query("SELECT * FROM testdb");
        $value = $statement->fetch();
        $this->assertEquals('2014-40-50',$value['day']);
    }

    ///**
    // * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
    // * @expectedExceptionMessage sql execute error.:
    // * @expectedExceptionCode    -12
    // */
    public function testDate2Error()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "00000",    "No error" },
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc','2014#AA#AA')");
        $statement = $connection->query("SELECT * FROM testdb");
        $value = $statement->fetch();
        $this->assertEquals('2014#AA#AA',$value['day']);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 2 values for 3 columns
     * @expectedExceptionCode    -1
     */
    public function testValueCountOnRow()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        //{ "HY000",    "General error" },
        $connection->exec("INSERT INTO testdb (id,name,day) VALUES (2,'abc')");
    }

    public function testReleaseSavepoint()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->beginTransaction();
        $connection->createSavepoint('testtest');
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test',2,1));
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
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test',2,1));
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
