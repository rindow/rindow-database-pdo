<?php
namespace RindowTest\Database\Pdo\Core\TableTemplateTest;

use PHPUnit\Framework\TestCase;
use Rindow\Database\Dao\Sql\TableTemplate;
use Rindow\Transaction\Support\TransactionBoundary;
use Rindow\Container\Container;
use Rindow\Database\Pdo\DataSource;

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

    public static function getPDOClientStatic()
    {
        $dsn = "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite";
        $username = null;
        $password = null;
        $options  = array();
        @$client = new \PDO($dsn, $username, $password, $options);
        return $client;
    }
    public function getPDOClient()
    {
        return self::getPDOClientStatic();
    }

    public function getTableTemplate()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
		$template = new TableTemplate ($dataSource);
        return $template;
    }

    public function testCreateNormal()
    {
    	$template = $this->getTableTemplate();
        $template->insert('testdb',array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(1,$template->getLastInsertId('testdb','id'));
        $template->insert('testdb',array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(2,$template->getLastInsertId('testdb','id'));

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM testdb",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            elseif($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(2,$count);
    }

    public function testUpdateNormal()
    {
    	$template = $this->getTableTemplate();
        $template->insert('testdb',array('name'=>'test','day'=>1,'ser'=>1));
        $template->insert('testdb',array('name'=>'test2','day'=>1,'ser'=>10));

        $template->update('testdb',array('id'=>1,'day'=>1),array('name'=>'update1'));

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM testdb",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'update1','day'=>1,'ser'=>1),$row);
            elseif($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(2,$count);

        $template->update('testdb',array(),array('name'=>'update2'));
        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM testdb",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'update2','day'=>1,'ser'=>1),$row);
            elseif($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'update2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(2,$count);
    }

    public function testDeleteNormal()
    {
    	$template = $this->getTableTemplate();
        $template->insert('testdb',array('name'=>'test','day'=>1,'ser'=>1));
        $template->insert('testdb',array('name'=>'test2','day'=>1,'ser'=>10));

        $template->delete('testdb',array('id'=>1,'name'=>'test'));

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM testdb",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

	public function testFindNormal()
	{
    	$template = $this->getTableTemplate();
        $template->insert('testdb',array('name'=>'test','day'=>1,'ser'=>1));
        $template->insert('testdb',array('name'=>'test2','day'=>1,'ser'=>40));
        $template->insert('testdb',array('name'=>'test3','day'=>2,'ser'=>30));
        $template->insert('testdb',array('name'=>'test4','day'=>2,'ser'=>20));

		$results = $template->find('testdb');
        $count = 0;
        foreach($results as $row) {
            $count++;
        }
        $this->assertEquals(4,$count);

        // filter
		$results = $template->find('testdb',array('day'=>1,'ser'=>1));
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);

        // orderby
		$results = $template->find('testdb',null,array('day'=>1,'ser'=>1));
		$ids = array();
        foreach($results as $row) {
        	$ids[] = $row['id'];
        }
        $this->assertEquals(array(1,2,4,3),$ids);

        // orderby desc
		$results = $template->find('testdb',null,array('day'=>0,'ser'=>1));
		$ids = array();
        foreach($results as $row) {
        	$ids[] = $row['id'];
        }
        $this->assertEquals(array(4,3,1,2),$ids);

        // limit
		$results = $template->find('testdb',null,null,2);
		$ids = array();
        foreach($results as $row) {
        	$ids[] = $row['id'];
        }
        $this->assertEquals(array(1,2),$ids);

        // limit offset
		$results = $template->find('testdb',null,null,100,2);
		$ids = array();
        foreach($results as $row) {
        	$ids[] = $row['id'];
        }
        $this->assertEquals(array(3,4),$ids);

        // offset
		//$results = $template->find('testdb',null,null,null,2);
		//$ids = array();
        //foreach($results as $row) {
        //	$ids[] = $row['id'];
        //}
        //$this->assertEquals(array(3,4),$ids);
	}

	public function testCountNormal()
	{
    	$template = $this->getTableTemplate();
        $template->insert('testdb',array('name'=>'test','day'=>1,'ser'=>1));
        $template->insert('testdb',array('name'=>'test2','day'=>1,'ser'=>40));
        $template->insert('testdb',array('name'=>'test3','day'=>2,'ser'=>30));
        $template->insert('testdb',array('name'=>'test4','day'=>2,'ser'=>20));

		$this->assertEquals(4,$template->count('testdb'));

		$this->assertEquals(2,$template->count('testdb',array('day'=>1)));
	}
}
