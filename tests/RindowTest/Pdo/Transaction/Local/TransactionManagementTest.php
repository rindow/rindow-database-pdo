<?php
namespace RindowTest\Database\Pdo\Transaction\Local\TransactionManagementTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Rindow\Stdlib\Entity\AbstractEntity;
use Rindow\Stdlib\Entity\PropertyAccessPolicy;
use Rindow\Database\Pdo\Orm\AbstractMapper;
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

class Color implements PropertyAccessPolicy
{
    public $id;

    public $product;

    public $color;
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

class Product extends AbstractEntity
{
    static public $colorNames = array(1=>"Red",2=>"Green",3=>"Blue");

    public function getColorNames()
    {
        return self::$colorNames;
    }

    public $id;

    public $category;

    public $name;

    public $colors;

    public function addColor($colorId)
    {
        $color = new Color();
        $color->color = $colorId;
        $color->product = $this;
        $this->colors[] = $color;
    }
}

class CategoryMapper extends AbstractMapper implements DataMapper
{
    const CLASS_NAME = 'RindowTest\Database\Pdo\Transaction\Local\TransactionManagementTest\Category';
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

    //protected $errorInsert = array();
    //public function setErrorInsert($em,$switch)
    //{
    //    $this->errorInsert[spl_object_hash($em)] = $switch;
    //}
    //public function getErrorInsert($em)
    //{
    //    if(!isset($this->errorInsert[spl_object_hash($em)]))
    //        return null;
    //    return $this->errorInsert[spl_object_hash($em)];
    //}

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
        if($entity->name == 'failure' || $entity->name == 'failureTier1')
        //if($this->getErrorInsert($entityManager))
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

    /**
    *  @TransactionAttribute(value='required',isolation='serializable')
    */
    public function testCommit($failure=false,$readAccess=false,$resource='orm')
    {
        $this->logger->log('in testCommit');
        switch($resource){
            case 'orm':
                if($readAccess)
                    $category = $this->entityManager->find(1);
                $category = new Category();
                $category->name = 'test';
                $this->entityManager->persist($category);
                break;
            default:
                $this->dataSource->getConnection()->connect();
                break;
        }
        if($failure)
            throw new TestException("error");
        $this->logger->log('out testCommit');
    }

    /**
    *  @TransactionAttribute('required')
    */
    public function testTierOne($failure=false)
    {
        $this->logger->log('in testTierOne');
        $this->testCommit($failure);
        $this->logger->log('out testTierOne');
    }

    /**
    *  @TransactionAttribute('required')
    */
    public function testTierOneWithAccess($failure=false,$readAccess=false,$resource='orm')
    {
        $this->logger->log('in testTierOne');
        switch($resource){
            case 'orm':
                if($readAccess)
                    $this->entityManager->find(1);
                $category = new Category();
                $category->name = 'tier1';
                $this->entityManager->persist($category);
                break;
            default:
                $this->dataSource->getConnection()->access();
                break;
        }
        $this->testCommit($failure,false,$resource);
        $this->logger->log('out testTierOne');
    }

    /**
    *  @TransactionAttribute('nested')
    */
    public function testNested($failure=false,$name)
    {
        $category = new Category();
        $category->name = $name;
        $this->entityManager->persist($category);
        if($failure===true) {
            throw new TestException("error");
        } elseif($failure==='mapper') {
            $category->name = 'failure';
            //$mapper = $this->entityManager->getRepository(__NAMESPACE__.'\Category')->getMapper();
            //$em = $this->entityManager->getRepository(__NAMESPACE__.'\Category')->getEntityManager();
            //$mapper->setErrorInsert($em,true);
        }
    }
    /**
    *  @TransactionAttribute('required')
    */
    public function testCallNested($failure1=false,$failure2=false,$failureTier1=false)
    {
        $category = new Category();
        $category->name = 'tier1';
        $this->entityManager->persist($category);
        try {
            $this->testNested($failure1,'nested1');
        } catch(TestException $e) {
            $this->logger->log('Error at nested1:'.$e->getMessage());
        }

        $this->testNested($failure2,'nested2');

        if($failureTier1===true) {
            throw new TestException("error");
        } elseif($failureTier1==='mapper') {
            $category->name = 'failureTier1';
            //$mapper = $this->entityManager->getRepository(__NAMESPACE__.'\Category')->getMapper();
            //$em = $this->entityManager->getRepository(__NAMESPACE__.'\Category')->getEntityManager();
            //$mapper->setErrorInsert($em,true);
        }
    }

    /**
    *  @TransactionAttribute('requires_new')
    */
    public function testRequiresNew($failure=false)
    {
        $category = new Category();
        $category->name = 'test';
        $this->entityManager->persist($category);
        if($failure)
            throw new TestException("error");
    }

    /**
    *  @TransactionAttribute('required')
    */
    public function testRequiresNewTier1($failure1=false,$failure2=false,$failureTier1=false)
    {
        $category = new Category();
        $category->name = 'tier1';
        $this->entityManager->persist($category);
        $this->testRequiresNew($failure1);
        if($failureTier1)
            throw new TestException("error");
    }

    /**
    *  @TransactionAttribute('not_supported')
    */
    public function testNotSupported($failure=false)
    {
        $connection = $this->dataSource->getConnection();
        $connection->exec("INSERT INTO category (name) VALUES ('test')");
        if($failure)
            throw new TestException("error");
    }

    /**
    *  @TransactionAttribute('required')
    */
    public function testNotSupportedTier1($failure1=false,$failure2=false,$failureTier1=false)
    {
        $category = new Category();
        $category->name = 'tier1';
        $this->entityManager->persist($category);
        $this->testNotSupported($failure1);
        $connection = $this->dataSource->getConnection();
        $connection->exec("INSERT INTO category (name) VALUES ('tier1-raw')");
        if($failureTier1)
            throw new TestException("error");
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
                    //'Rindow\Database\Pdo\Module' => true,
                    'Rindow\Persistence\OrmShell\Module' => true,
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
                ),
                'components' => array(
                    __NAMESPACE__.'\TestLogger'=>array('proxy'=>'disable'),
                    __NAMESPACE__.'\TestDao' => array(
                        'properties' => array(
                            'entityManager' => array('ref'=>'EntityManager'),
                            'dataSource' => array('ref'=>'DataSource'),
                            'logger' => array('ref'=>'TestLogger'),
                        ),
                    ),
                    __NAMESPACE__.'\CategoryMapper'=>array(
                        'parent' => 'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper',
                    ),
                    //'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource' => array(
                    //    'properties' => array(
                    //        // === for debug options ===
                    //        'debug' => array('value'=>true),
                    //        'logger' => array('ref'=>'Logger'),
                    //    ),
                    //),
                    //'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager' => array(
                    //    'properties' => array(
                    //        // === for debug options ===
                    //        'debug' => array('value'=>true),
                    //        'logger' => array('ref'=>'Logger'),
                    //    ),
                    //),
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
        unset($pdo);
    }

    public function testRequiredCommitLevel2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $test->testTierOne();

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);
    }

    public function testRequiredCommitLevel1AndLevel2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $test->testTierOneWithAccess($failure=false);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'tier1'),$row);
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
        unset($pdo);
    }

    public function testRequiredRollbackLevel1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        try {
            $test->testCommit($failure=true);
            $this->assertTrue(false);
        } catch(\Exception $e) {
            $this->assertTrue(true);
        }

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testRequiredRollbackLevel2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        try {
            $test->testTierOne($failure=true);
            $this->assertTrue(false);
        } catch(\Exception $e) {
            $this->assertTrue(true);
        }

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testRequiredRollbackLevel1AndLevel2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');

        try {
            $test->testTierOneWithAccess($failure=true);
            $this->assertTrue(false);
        } catch(\Exception $e) {
            $this->assertTrue(true);
        }

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testNestedCommitLevel1AndLevel2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $test->testCallNested($failure1=false,$failure2=false,$failureTier1=false);
        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'nested1'),$row);
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'nested2'),$row);
            if($row['id']==3)
                $this->assertEquals(array('id'=>3,'name'=>'tier1'),$row);
            $count++;
        }
        $this->assertEquals(3,$count);
        unset($pdo);
    }

    public function testNestedErrorLevel2AndContinue()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exception = null;
        try {
            $test->testCallNested($failure1=true,$failure2=false,$failureTier1=false);
        } catch(\Exception $e) {
            $exception = get_class($e);
        }
        $this->assertNull($exception);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'nested2'),$row);
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'tier1'),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
        unset($pdo);
    }

    public function testNestedErrorLevel1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exception = null;
        try {
            $test->testCallNested($failure1=false,$failure2=false,$failureTier1=true);
        } catch(\Exception $e) {
            $exception = get_class($e);
        }
        $this->assertEquals(__NAMESPACE__.'\TestException',$exception);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testNestedMapperErrorLevel2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exception = null;
        try {
            $test->testCallNested($failure1=false,$failure2='mapper',$failureTier1=false);
        } catch(\Exception $e) {
            $exception = get_class($e);
        }
        $this->assertEquals(__NAMESPACE__.'\TestException',$exception);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testNestedMapperErrorLevel2AndContinue()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exception = null;
        try {
            $test->testCallNested($failure1='mapper',$failure2=false,$failureTier1=false);
        } catch(\Exception $e) {
            $exception = get_class($e);
            throw $e;
        }
        $this->assertNull($exception);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'nested2'),$row);
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'tier1'),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
        unset($pdo);
    }

    public function testRequiresNewCommit()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exceptionMessage = null;
        try {
            $test->testRequiresNewTier1($failure1=false,$failure2=false,$failureTier1=false);
        } catch(\Exception $e) {
            $exceptionMessage = $e->getMessage();
        }
        $this->assertEquals('',$exceptionMessage);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'tier1'),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
        unset($pdo);
    }

    public function testRequiresNewRollbackTier1()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exceptionMessage = null;
        try {
            $test->testRequiresNewTier1($failure1=false,$failure2=false,$failureTier1=true);
        } catch(\Exception $e) {
            $exceptionMessage = $e->getMessage();
        }
        $this->assertEquals('error',$exceptionMessage);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);
    }

    public function testRequiresNewRollbackTier2()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exceptionMessage = null;
        try {
            $test->testRequiresNewTier1($failure1=true,$failure2=false,$failureTier1=false);
        } catch(\Exception $e) {
            $exceptionMessage = $e->getMessage();
        }
        $this->assertEquals('error',$exceptionMessage);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testNotSupportedCommit()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exceptionMessage = null;
        try {
            $test->testNotSupportedTier1($failure1=false,$failure2=false,$failureTier1=false);
        } catch(\Exception $e) {
            $exceptionMessage = $e->getMessage();
        }
        $this->assertNull($exceptionMessage);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'tier1-raw'),$row);
            if($row['id']==3)
                $this->assertEquals(array('id'=>3,'name'=>'tier1'),$row);
            $count++;
        }
        $this->assertEquals(3,$count);
        unset($pdo);
    }

    public function testNotSupportedRollback()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestDao');
        $exceptionMessage = null;
        try {
            $test->testNotSupportedTier1($failure1=false,$failure2=false,$failureTier1=true);
        } catch(\Exception $e) {
            $exceptionMessage = $e->getMessage();
        }
        $this->assertEquals('error',$exceptionMessage);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);
    }
}