<?php
namespace RindowTest\Database\Pdo\DatabasePdoTest;

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
		$connection = new Connection($config,new Sqlite());
		$connection->exec("ERRORSTATEMENT");
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 near "ERRORSTATEMENT": syntax error
     * @expectedExceptionCode    -2
     */
	public function testDriverOnConfig()
	{
		$config = $this->getConfig();
		$config['driver'] = 'sqlite';
		$connection = new Connection($config);
		$connection->exec("ERRORSTATEMENT");
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\DomainException
     * @expectedExceptionMessage A driver class is not found.: FooBar
     * @expectedExceptionCode    0
     */
	public function testDirectDriverClassOnConfigAndNotFound()
	{
		$config = $this->getConfig();
		$config['driver'] = 'FooBar';
		$connection = new Connection($config);
		$connection->exec("ERRORSTATEMENT");
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 near "ERRORSTATEMENT": syntax error
     * @expectedExceptionCode    -2
     */
	public function testDriverOnDsn()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("ERRORSTATEMENT");
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\DomainException
     * @expectedExceptionMessage A driver class is not found.: Driver\Class\On\Dsn
     * @expectedExceptionCode    0
     */
	public function testDriverOnDsnAndNotFound()
	{
		$config = $this->getConfig();
		$config['dsn'] = 'FooBarOnDns:abc=def';
		Connection::addDriver('FooBarOnDns','Driver\Class\On\Dsn');
		$connection = new Connection($config);
		$connection->exec("ERRORSTATEMENT");
	}

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[23000]: Integrity constraint violation: 19
     * @expectedExceptionCode    -5
     */
	public function testErrorInPDOStatement()
	{
		$config = $this->getConfig();
		$connection = new Connection($config);
		$connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
		$statement = $connection->prepare("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
		$statement->execute();
	}

    public function testexecuteQueryNoParam()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteQueryWithParam()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $results = $connection->executeQuery("SELECT * FROM testdb WHERE day=:day",array('day'=>1));
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteQueryWithFetchMode()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $results = $connection->executeQuery("SELECT * FROM testdb WHERE day=:day",array('day'=>1),PDO::FETCH_OBJ);
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals((object)array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteQueryWithClass()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $results = $connection->executeQuery("SELECT * FROM testdb WHERE day=:day",array('day'=>1),PDO::FETCH_CLASS,__NAMESPACE__.'\\TestClass');
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(__NAMESPACE__.'\\TestClass',get_class($row));
            $this->assertEquals(1,$row->id);
            $this->assertEquals('test',$row->name);
            $this->assertEquals(1,$row->day);
            $this->assertEquals(1,$row->ser);
            $this->assertEquals(null,$row->option);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteQueryWithConstruct()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $results = $connection->executeQuery("SELECT * FROM testdb WHERE day=:day",array('day'=>1),PDO::FETCH_CLASS,__NAMESPACE__.'\\TestClass',array(1));
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(__NAMESPACE__.'\\TestClass',get_class($row));
            $this->assertEquals(1,$row->id);
            $this->assertEquals('test',$row->name);
            $this->assertEquals(1,$row->day);
            $this->assertEquals(1,$row->ser);
            $this->assertEquals(1,$row->option);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteQueryWithInto()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $results = $connection->executeQuery("SELECT * FROM testdb WHERE day=:day",array('day'=>1),PDO::FETCH_INTO,new TestClass());
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(__NAMESPACE__.'\\TestClass',get_class($row));
            $this->assertEquals(1,$row->id);
            $this->assertEquals('test',$row->name);
            $this->assertEquals(1,$row->day);
            $this->assertEquals(1,$row->ser);
            $this->assertEquals(null,$row->option);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteUpdateNoParam()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $this->assertEquals(1,$insert);
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testexecuteUpdateWithParam()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test',2,1));
        $this->assertEquals(1,$insert);
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>2,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testTransactionCommit()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->beginTransaction();
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test',2,1));
        $this->assertEquals(1,$insert);
        $connection->commit();
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>2,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testTransactionRollback()
    {
        $config = $this->getConfig();
        $connection = new Connection($config);

        $connection->beginTransaction();
        $insert = $connection->executeUpdate("INSERT INTO testdb (name,day,ser) values (?,?,?)",array('test',2,1));
        $this->assertEquals(1,$insert);
        $connection->rollback();
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
    }
}
