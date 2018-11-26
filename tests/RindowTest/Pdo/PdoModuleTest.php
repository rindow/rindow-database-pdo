<?php
namespace RindowTest\Database\Pdo\PdoModuleTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Rindow\Database\Dao\Support\ResultList;
use Rindow\Database\Pdo\Connection;
use Rindow\Database\Pdo\Driver\Sqlite;
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
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

	public function testStandaloneNormal()
	{
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Database\Pdo\StandaloneModule' => true,
                ),
            ),
            'database' => array(
                'connections' => array(
                    'default' => array(
                        'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $connection = $mm->getServiceLocator()->get('Rindow\Database\Pdo\DefaultConnection');

		$connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
		$results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
		foreach($results as $row) {
			$this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
		}
        $this->assertEquals(1,$count);
	}

    public function testLocalTransactionNormal()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Database\Pdo\LocalTxModule' => true,
                ),
            ),
            'database' => array(
                'connections' => array(
                    'default' => array(
                        'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $ds = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource');
        $txm = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager');

        $txm->begin();
        $connection = $ds->getConnection();

        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $txm->rollback();
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(0,$count);

        $txm->begin();
        $connection = $ds->getConnection();

        $connection->exec("INSERT INTO testdb (name,day,ser) values ('test',1,1)");
        $txm->commit();
        $results = $connection->executeQuery("SELECT * FROM testdb");
        $count = 0;
        foreach($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testRepositoryOnStandaloneModule()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module'=>true,
                    'Rindow\Database\Dao\Sql\Module' => true,
                    'Rindow\Database\Pdo\StandaloneModule' => true,
                ),
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
                    __NAMESPACE__.'\TestRepository' => array(
                        'parent' => 'Rindow\\Database\\Dao\\Repository\\AbstractSqlRepository',
                        'properties' => array(
                            'tableName' => array('config' => 'testtest::repository::test_repository::reference'),
                        ),
                    ),
                ),
            ),
            'testtest' => array(
                'repository' => array(
                    'test_repository' => array(
                        'reference' => 'testdb',
                    ),
                ),
            ),
        );

        $mm = new ModuleManager($config);
        $repository = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestRepository');
        $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $repository->save(array('name'=>'test2','day'=>2,'ser'=>2));
        $cursor = $repository->findAll();
        $count = 0;
        foreach ($cursor as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            else
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>2,'ser'=>2),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
    }

    public function testRepositoryOnLocalTxModule()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module'=>true,
                    'Rindow\Transaction\Local\Module'=>true,
                    'Rindow\Database\Dao\Sql\Module' => true,
                    'Rindow\Database\Pdo\LocalTxModule' => true,
                ),
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
                    __NAMESPACE__.'\TestRepository' => array(
                        'parent' => 'Rindow\\Database\\Dao\\Repository\\AbstractSqlRepository',
                        'properties' => array(
                            'tableName' => array('config' => 'testtest::repository::test_repository::reference'),
                        ),
                    ),
                ),
            ),
            'testtest' => array(
                'repository' => array(
                    'test_repository' => array(
                        'reference' => 'testdb',
                    ),
                ),
            ),
        );

        $mm = new ModuleManager($config);
        $repository = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestRepository');
        $tx = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager');
        $tx->begin();
        $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $repository->save(array('name'=>'test2','day'=>2,'ser'=>2));
        $tx->commit();
        $cursor = $repository->findAll();
        $count = 0;
        foreach ($cursor as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            else
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>2,'ser'=>2),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
    }
}
