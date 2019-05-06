<?php
namespace RindowTest\Database\Pdo\Transaction\Local\LocalTxModuleTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Rindow\Stdlib\Entity\AbstractEntity;
use Rindow\Messaging\Service\Database\GenericQueueDriver;
use Rindow\Database\Pdo\Orm\AbstractMapper;
use Rindow\Database\Pdo\Connection;
use Rindow\Database\Pdo\DataSource;
use Rindow\Persistence\OrmShell\DataMapper;

use Interop\Lenient\Transaction\Annotation\TransactionAttribute;
use Interop\Lenient\Transaction\Annotation\TransactionManagement;

class TestLogger
{
    public $logdata = array();
    public function log($message)
    {
        $this->logdata[] = $message;
    }
}

class Category
{
    public $id;

    public $name;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

class CategoryMapper extends AbstractMapper implements DataMapper
{
    const CLASS_NAME = 'RindowTest\Database\Pdo\Transaction\Local\LocalTxModuleTest\Category';
    const TABLE_NAME = 'category';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE category(id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)";
    const DROP_TABLE = "DROP TABLE category";
    const INSERT = "INSERT INTO category (name) VALUES ( :name )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE category SET name = :name WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM category WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM category WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM category";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM category";

    public $errorInsert;

    public function className()
    {
        return self::CLASS_NAME;
    }

    public function tableName()
    {
        return self::TABLE_NAME;
    }

    public function primaryKey()
    {
        return self::PRIMARYKEY;
    }

    public function hash($entityManager,$entity)
    {
        $hash = md5(strval($this->getId($entity))) .
            md5(strval($this->getField($entity,'name')));
        return md5($hash);
    }

    public function supplementEntity($entityManager,$entity)
    {
        return $entity;
    }

    public function subsidiaryPersist($entityManager,$entity)
    {
    }

    public function subsidiaryRemove($entityManager,$entity)
    {
    }

    protected function insertStatement()
    {
        return self::INSERT;
    }

    protected function bulidInsertParameter($entity)
    {
        if($this->errorInsert)
            throw new TestException('Insert Error in CategoryMapper');
            
        return array(':name'=>$entity->name);
    }

    protected function updateByPrimaryKeyStatement()
    {
        return self::UPDATE_BY_PRIMARYKEY;
    }

    protected function bulidUpdateParameter($entity)
    {
        return array(':name'=>$entity->name,':id'=>$entity->id);
    }

    protected function deleteByPrimaryKeyStatement()
    {
        return self::DELETE_BY_PRIMARYKEY;
    }

    protected function selectByPrimaryKeyStatement()
    {
        return self::SELECT_BY_PRIMARYKEY;
    }

    protected function selectAllStatement()
    {
        return self::SELECT_ALL;
    }

    protected function countAllStatement()
    {
        return self::COUNT_ALL;
    }

    protected function createSchemaStatement()
    {
        return self::CREATE_TABLE;
    }

    protected function dropSchemaStatement()
    {
        return self::DROP_TABLE;
    }

    protected function queryStatements()
    {
        return $this->queryStrings;
    }
}


/**
* @TransactionManagement()
*/
class TestDao
{
    protected $entityManager;
    protected $dataSource;
    protected $logger;

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function setMessagingTemplate($messagingTemplate)
    {
        $this->messagingTemplate = $messagingTemplate;
    }

    /**
    *  @TransactionAttribute('required')
    */
    public function testCommit($failure=false)
    {
        $this->logger->log('in testCommit');

        // ORM
        $category = new Category();
        $category->name = 'test';
        $this->entityManager->persist($category);

        // Messaging
        $this->messagingTemplate->convertAndSend('/queue/testdest','testmessage');

        $this->logger->log('out testCommit');
    }

    /**
    *  @TransactionAttribute('required')
    */
    public function testRollback($failure=false)
    {
        $this->logger->log('in testRollback');

        // ORM
        $category = new Category();
        $category->name = 'test';
        $this->entityManager->persist($category);

        // Messaging
        $this->messagingTemplate->convertAndSend('/queue/testdest','testmessage');

        $this->logger->log('out testRollback');
        throw new TestException("Error", 1);
    }
}
class TestException extends \Exception
{}

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
        self::$RINDOW_TEST_DATA = __DIR__.'/../../../../data';
        //try {
            $client = self::getPDOClientStatic();
        //} catch(\Exception $e) {
        //    self::$skip = $e->getMessage();
        //    return;
        //}
    }
    public static function tearDownAfterClass()
    {
        if(self::$skip)
            return;
        $client = self::getPDOClientStatic();
        $client->exec(CategoryMapper::DROP_TABLE);
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        $client = $this->getPDOClient();
        $client->exec(CategoryMapper::DROP_TABLE);
        $client->exec(CategoryMapper::CREATE_TABLE);
        $queue = $this->getQueueClient();
        $queue->dropSchema();
        $queue->createSchema();
    }

    public static function getQueueClientStatic()
    {
        $config = self::getStaticConfig();
        $config = $config['database']['connections']['default'];
        $dataSource = new DataSource($config);
        $connection = $dataSource->getConnection();
        $queue = new GenericQueueDriver($connection);
        return $queue;
    }
    public function getQueueClient()
    {
        return self::getQueueClientStatic();
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

    public function getConfig()
    {
        return self::getStaticConfig();
    }

    public static function getStaticConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                    'Rindow\Persistence\OrmShell\Module' => true,
                    'Rindow\Messaging\Service\Database\Module' => true,
                    'Rindow\Transaction\Local\Module' => true,
                    'Rindow\Database\Pdo\LocalTxModule' => true,
                    //'Rindow\Module\Monolog\Module' => true,
                ),
                'annotation_manager' => true,
                'enableCache' => false,
            ),
            'aop' => array(
                //'debug' => true,
                //'intercept_to' => array(
                //    __NAMESPACE__.'\TestDao' => true,
                //),
            ),
            'container' => array(
                'component_paths' => array(
                    __DIR__ => true,
                ),
                'aliases' => array(
                    'TestLogger' => __NAMESPACE__.'\TestLogger',
                    'EntityManager' => 'Rindow\Persistence\OrmShell\Transaction\DefaultPersistenceContext',
                    'DataSource' => 'Rindow\Database\Pdo\Transaction\DefaultDataSource',
                    'MessagingTemplate' => 'Rindow\\Messaging\\Service\\Database\\DefaultGenericMessagingTemplate',
                ),
                'components' => array(
                    __NAMESPACE__.'\TestLogger'=>array('proxy'=>'disable'),
                    __NAMESPACE__.'\TestDao' => array(
                        'properties' => array(
                            'entityManager' => array('ref'=>'EntityManager'),
                            'dataSource' => array('ref'=>'DataSource'),
                            'messagingTemplate' => array('ref'=>'MessagingTemplate'),
                            'logger' => array('ref'=>'TestLogger'),
                        ),
                    ),
                    __NAMESPACE__.'\CategoryMapper'=>array(
                        'parent' => 'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper',
                    ),
                ),
            ),
            'database' => array(
                'connections' => array(
                    'default' => array(
                        'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
                    ),
                ),
            ),
            'persistence' => array(
                'mappers' => array(
                    __NAMESPACE__.'\Category' => __NAMESPACE__.'\CategoryMapper',
                ),
            ),
            //'monolog' => array(
            //    'handlers' => array(
            //        'default' => array(
            //            'path'  => __DIR__.'/test.log',
            //        ),
            //    ),
            //),
        );
        return $config;
    }

    public function dumpTrace($e)
    {
        while($e) {
            echo "------------------\n";
            echo $e->getMessage()."\n";
            echo $e->getFile().'('.$e->getLine().')'."\n";
            echo $e->getTraceAsString();
            $e = $e->getPrevious();
        }
    }

    public function testCommitResourceTransactionLevel1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $ds = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource');
        $tx = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager');
        $tx->begin();
        $conn = $ds->getConnection();
        $conn->executeUpdate("INSERT INTO category (name) VALUES ( :name )",array('name'=>'test'));
        $tx->commit();
        $results = $conn->executeQuery("SELECT * FROM category");
        $count = 0;
        foreach ($results as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testRollbackResourceTransactionLevel1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $ds = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource');
        $tx = $mm->getServiceLocator()->get('Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager');
        $tx->begin();
        $conn = $ds->getConnection();
        $this->assertFalse($conn->isConnected());
        //$this->assertFalse(!empty($tx->isConnected()));
        $conn->executeUpdate("INSERT INTO category (name) VALUES ( :name )",array('name'=>'test'));
        $this->assertTrue($conn->isConnected());
        //$this->assertTrue(!empty($tx->isConnected()));
        $tx->rollback();
        $results = $conn->executeQuery("SELECT * FROM category");
        $count = 0;
        foreach ($results as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
    }

    public function testRequiredCommitLevel1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $test->testCommit($failure=false);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);

        $queue = $this->getQueueClient();
        $count = 0;
        while($frame = $queue->receive('/queue/testdest')) {
            $msg = unserialize($frame->body);
            $this->assertEquals('testmessage',$msg['p']);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);
    }

    public function testRequiredRollbackLevel1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');

        try {
            $test->testRollback($failure=false);
        } catch(TestException $e) {
            ;
        }

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(0,$count);

        $queue = $this->getQueueClient();
        $count = 0;
        while($frame = $queue->receive('/queue/testdest')) {
            $msg = unserialize($frame->body);
            $this->assertEquals('testmessage',$msg['p']);
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }
}