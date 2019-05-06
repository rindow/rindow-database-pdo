<?php
namespace RindowTest\Database\Pdo\Repository\RepositoryOnModuleTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Rindow\Container\ModuleManager;

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
    		'module_manager' => array(
    			'modules' => array(
    				'Rindow\Aop\Module' => true,
    				'Rindow\Database\Dao\Sql\Module' => true,
    				'Rindow\Transaction\Local\Module' => true,
    				'Rindow\Database\Pdo\LocalTxModule' => true,
    			),
                'enableCache' => false,
    		),
    		'database' => array(
    			'connections' => array(
    				'default' => array(
                        'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
    				),
    			),
    		),
    		'container' => array(
    			'components' => array(
    				__NAMESPACE__.'\TestDbRepository' => array(
    					'parent' => 'Rindow\\Database\\Dao\\Repository\\AbstractSqlRepository',
    					'properties' => array(
                            'tableName' => array('value' => 'testdb'),
    					),
    				),
    			),
    		),
    	);

    	return $config;
    }

    public function testSaveAndFind()
    {
    	$mm = new ModuleManager($this->getConfig());
    	$repository = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDbRepository');
    	$entity = array('name'=>'test','day'=>'2015/01/01','ser'=>1);
    	$entity = $repository->save($entity);
    	$entity2 = $repository->findById($entity['id']);
    	$this->assertEquals('test',$entity2['name']);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\DuplicateKeyException
     * @expectedExceptionCode    -5
     */
    public function testThrowDuplicateKeyException()
    {
    	$mm = new ModuleManager($this->getConfig());
    	$repository = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDbRepository');
    	$entity = array('name'=>'test','day'=>'2015/01/01','ser'=>1);
    	$repository->save($entity);
    	$entity = array('name'=>'test','day'=>'2015/01/01','ser'=>1);
    	$repository->save($entity);
    }
}
