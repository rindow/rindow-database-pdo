<?php
namespace RindowTest\Database\Pdo\Paginator\PaginatorSqlAdapterTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Paginator\Paginator;

use Rindow\Database\Dao\Paginator\GenericSqlAdapter;
use Rindow\Database\Pdo\DataSource;
use Rindow\Database\Pdo\Connection;

class TestClass
{
	public $id;
	public $name;
}

class Loader
{
    public function load($row)
    {
        if(!$row)
            return $row;
        $row->opt = 'opt-'.$row->id;
        return $row;
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
        self::$RINDOW_TEST_DATA = __DIR__.'/../../../data';
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
        $client->exec("CREATE TABLE testdb ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

    }
    public function setUpData($count)
    {
        $dsn = "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite";
        $username = null;
        $password = null;
        $options  = array();
        $client = new \PDO($dsn, $username, $password, $options);
        $statement = $client->prepare("INSERT INTO testdb (name) VALUES ( :name )");
        for ($i=0; $i < $count; $i++) { 
            $params = array(':name' => 'item-'.($i+1));
            $statement->execute($params);
        }
    }

    public function getItems($paginator)
    {
    	$results = array();
    	foreach ($paginator as $value) {
    		$results[] = $value;
    	}
    	return $results;
    }
    public function getConfig()
    {
        $config = array(
            'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
        );
        return $config;
    }

	public function test()
	{
		$this->setUpData(20);

        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();

        $sql = "SELECT * FROM testdb WHERE id < :id";
        $countsql = "SELECT COUNT(id) as count FROM testdb WHERE id < :id";
        $params = array(':id'=>16);
        $className = 'RindowTest\Database\Pdo\Paginator\PaginatorSqlAdapterTest\TestClass';

    	$paginatorAdapter = new GenericSqlAdapter($connection);
    	$paginatorAdapter->setQuery($sql,$params,$className);
    	$paginatorAdapter->setCountQuery($countsql,$params);
        $loader = new Loader();
        $paginatorAdapter->setLoader(array($loader,'load'));

        $paginator = new Paginator($paginatorAdapter);
        $paginator->setPage(2);

        $this->assertEquals(15, $paginator->getTotalItems());
        $results = array();
        $opt = array();
        foreach ($paginator as $value) {
            $this->assertEquals($className,get_class($value));
        	$results[$value->id] = $value->name;
            $opt[$value->id] = $value->opt;
        }
        $this->assertEquals(
        	array(6=>'item-6',7=>'item-7',8=>'item-8',9=>'item-9',10=>'item-10'),
        	$results);
        $this->assertEquals(
            array(6=>'opt-6',7=>'opt-7',8=>'opt-8',9=>'opt-9',10=>'opt-10'),
            $opt);
	}
}
