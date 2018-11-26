<?php
namespace RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Entity\AbstractEntity;
use Rindow\Stdlib\Entity\PropertyAccessPolicy;
use Rindow\Container\ModuleManager;
use Rindow\Database\Pdo\Orm\AbstractMapper;
use Rindow\Persistence\OrmShell\EntityManager;
use Rindow\Persistence\OrmShell\DataMapper;

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

class CategoryMapper extends AbstractMapper
{
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest\Category';
    const TABLE_NAME = 'category';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE category(id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT)";
    const DROP_TABLE = "DROP TABLE IF EXISTS category";
    const INSERT = "INSERT INTO category (name) VALUES ( :name )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE category SET name = :name WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM category WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM category WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM category";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM category";

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

class ColorMapper extends AbstractMapper
{
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest\Color';
    const TABLE_NAME = 'color';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE color(id INTEGER PRIMARY KEY AUTO_INCREMENT, product INTEGER, color INTEGER)";
    const DROP_TABLE = "DROP TABLE IF EXISTS color";
    const INSERT = "INSERT INTO color ( product , color ) VALUES ( :product , :color )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE color SET product = :product , color = :color WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM color WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM color WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM color";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM color";

    const CLASS_PRODUCT = 'RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest\Product';

    protected $productRepository;
    protected $mappingClasses = array(
        'product' => self::CLASS_PRODUCT,
    );

    public function getProductRepository($entityManager)
    {
        return $entityManager->getRepository(self::CLASS_PRODUCT);
    }

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
            md5(strval($this->getField($entity,'product')->id)) .
            md5(strval($this->getField($entity,'color')));
        return md5($hash);
    }

    public function supplementEntity($entityManager,$entity)
    {
        $entity->product = $this->getProductRepository($entityManager)->find($entity->product);
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
        return array( ':product'=>$entity->product->id, ':color'=>$entity->color );
    }

    protected function updateByPrimaryKeyStatement()
    {
        return self::UPDATE_BY_PRIMARYKEY;
    }

    protected function bulidUpdateParameter($entity)
    {
        return array(':product'=>$entity->product->id, ':color'=>$entity->color,':id'=>$entity->id);
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

class ProductMapper extends AbstractMapper
{
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest\Product';
    const TABLE_NAME = 'product';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE product(id INTEGER PRIMARY KEY AUTO_INCREMENT, category INTEGER, name TEXT)";
    const DROP_TABLE = "DROP TABLE IF EXISTS product";
    const INSERT = "INSERT INTO product (category,name) VALUES ( :category , :name )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE product SET category = :category , name = :name WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM product WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM product WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM product";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM product";

    const CLASS_CATEGORY = 'RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest\Category';
    const CLASS_COLOR    = 'RindowTest\Database\Pdo\Orm\DistributedTransactionAdvisorMysqlTest\Color';

    protected $categoryRepository;
    protected $colorRepository;
    protected $mappingClasses = array(
        'category' => self::CLASS_CATEGORY,
    );

    public function getCategoryRepository($entityManager)
    {
        return $entityManager->getRepository(self::CLASS_CATEGORY);
    }

    public function getColorRepository($entityManager)
    {
        return $entityManager->getRepository(self::CLASS_COLOR);
    }

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
        $categoryMapper = $this->getCategoryRepository($entityManager)->getMapper();
        $hash = md5(strval($this->getId($entity))) .
            md5(strval($categoryMapper->getId($this->getField($entity,'category')))) .
            md5(strval($this->getField($entity,'name')));
        return md5($hash);
    }

    public function supplementEntity($entityManager,$entity)
    {
        $entity->category = $this->getCategoryRepository($entityManager)->find($entity->category);
        $entity->colors = $this->getColorRepository($entityManager)->findBy(array('product'=>$entity->id));
        $entity->colors->setCascade(array('persist','remove'));
        return $entity;
    }

    public function subsidiaryPersist($entityManager,$entity)
    {
        if($entity->colors===null)
            return;
        $colorRepository = $this->getColorRepository($entityManager);
        foreach ($entity->colors as $color) {
            $colorRepository->persist($color);
        }
    }

    public function subsidiaryRemove($entityManager,$entity)
    {
        if($entity->colors===null)
            return;
        $colorRepository = $this->getColorRepository($entityManager);
        foreach ($entity->colors as $color) {
            $colorRepository->remove($color);
        }
    }

    protected function insertStatement()
    {
        return self::INSERT;
    }

    protected function bulidInsertParameter($entity)
    {
        if($entity==null)
            throw new \Exception("null entity");
        if($entity->category==null)
            throw new \Exception("null category");
            
        return array(':category'=>$entity->category->id,':name'=>$entity->name);
    }

    protected function updateByPrimaryKeyStatement()
    {
        return self::UPDATE_BY_PRIMARYKEY;
    }

    protected function bulidUpdateParameter($entity)
    {
        return array(':category'=>$entity->category->id,':name'=>$entity->name,':id'=>$entity->id);
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

class TestCommit
{
	protected $entityManager;

	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function testCommit()
	{
        $category = new Category();
        $category->setName('cat');
        $this->entityManager->persist($category);
        $product = new Product();
        $product->setName('aaa');
        $product->setCategory($category);
        $this->entityManager->persist($product);
	}

	public function testRollback()
	{
        $category = new Category();
        $category->setName('cat');
        $this->entityManager->persist($category);
        $product = new Product();
        $product->setName('aaa');
        $product->setCategory($category);
        $this->entityManager->persist($product);
        throw new TestException("error");
	}

	public function testTierOne()
	{
		$this->testCommit();
	}
}

class TestException extends \Exception
{}

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
        \Rindow\Stdlib\Cache\CacheFactory::clearFileCache(\Rindow\Stdlib\Cache\CacheFactory::$fileCachePath.'/doctrine/cache');
        \Rindow\Stdlib\Cache\CacheFactory::clearFileCache(\Rindow\Stdlib\Cache\CacheFactory::$fileCachePath.'/doctrine/proxyDir');
    }

    public static function tearDownAfterClass()
    {
        if(self::$skip)
            return;
        $client = self::getPDOClientStatic();
        $client->exec("DROP TABLE IF EXISTS products");
    }

    protected function connectionClass()
    {
        return __NAMESPACE__ . '\TestConnection';
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        $client = $this->getPDOClient();
        $client->exec(ColorMapper::DROP_TABLE);
        $client->exec(CategoryMapper::DROP_TABLE);
        $client->exec(ProductMapper::DROP_TABLE);
        $client->exec(ColorMapper::CREATE_TABLE);
        $client->exec(CategoryMapper::CREATE_TABLE);
        $client->exec(ProductMapper::CREATE_TABLE);
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
        return self::getStaticConfig();
    }

    public static function getStaticConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\\Aop\\Module' => true,
                    'Rindow\\Transaction\\Distributed\\Module' => true,
                    'Rindow\\Persistence\\OrmShell\\Module' => true,
                    'Rindow\\Database\\Pdo\\DistributedTxModule' => true,
                    //'Rindow\\Module\\Monolog\\Module' => true,
                ),
            ),
            'aop' => array(
                'intercept_to' => array(
                    __NAMESPACE__=>true,
                ),
            ),
            'database' => array(
                'connections' => array(
                    'default' => array(
                        'dsn' => 'mysql:host=127.0.0.1;dbname='.RINDOW_TEST_MYSQL_DBNAME,
                        'user'     => RINDOW_TEST_MYSQL_USER,
                        'password' => RINDOW_TEST_MYSQL_PASSWORD,
                    ),
                ),
                'is_devmode' => false,
                //'mapping_drivers' => array(
                //    'annotation' => array(
                //        'paths' => array(
                //            'RindowTest\\Doctrine\\Resources\\Entity' => RINDOW_TEST_RESOURCES.'/RindowTest/Doctrine/Resources/Entity'),
                //    ),
                //),
            ),
            'container' => array(
                //'debug' => true,
                'components' => array(
                    'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource' => array(
                        'properties' => array(
                            //'logger' => array('ref'=>'Logger'),
                            //'debug'  => array('value'=>true),
                        ),
                    ),
                    'Rindow\\Transaction\\Distributed\\DefaultTransactionManager' => array(
                        'properties' => array(
                            //'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                    __NAMESPACE__.'\\TestCommit' => array(
                    	'properties' => array(
                    		'entityManager' => array('ref'=>'Rindow\\Persistence\\OrmShell\\Transaction\\DefaultPersistenceContext'),
                    	),
                    ),
                    __NAMESPACE__.'\\TestAdvisor' => array(
                        'class' => 'Rindow\\Transaction\\Support\\TransactionAdvisor',
                    	'properties' => array(
                    		'transactionManager' => array('ref'=>'Rindow\\Transaction\\Distributed\\DefaultTransactionManager'),
                    	),
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\ProductMapper'=>array(
                        'parent' => 'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper',
                    ),
                    __NAMESPACE__.'\CategoryMapper'=>array(
                        'parent' => 'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper',
                    ),
                    __NAMESPACE__.'\ColorMapper'=>array(
                        'parent' => 'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper',
                    ),
                ),
            ),
            'persistence' => array(
                'mappers' => array(
                    // O/R Mapping for PDO
                    __NAMESPACE__.'\Product'  => __NAMESPACE__.'\ProductMapper',
                    __NAMESPACE__.'\Category' => __NAMESPACE__.'\CategoryMapper',
                    __NAMESPACE__.'\Color'    => __NAMESPACE__.'\ColorMapper',
        
                    // O/D Mapping for MongoDB
                    //'Acme\MyApp\Entity\Product'  => 'Acme\MyApp\Persistence\ODM\ProductMapper',
                    //'Acme\MyApp\Entity\Category' => 'Acme\MyApp\Persistence\ODM\CategoryMapper',
                ),
            ),
        );
        return $config;
    }

    public function countRow()
    {
        $client = $this->getPDOClient();
        $stmt = $client->query("SELECT * FROM product");
        $c = 0;
        foreach ($stmt as $row) {
            $c += 1;
        }
        return $c;
    }

    public function testRequiredCommit()
    {
    	$config = array(
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\\TestAdvisor' => array(
			            'advices' => array(
			            	array(
			                    'type' => 'around',
			                    'pointcut' => 'execution('.__NAMESPACE__.'\\TestCommit::testCommit())',
			                    'method' => 'required',
			            	),
			            ),
			        ),
                ),
            ),
        );
    	$config = array_replace_recursive($config, $this->getConfig());

        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\\TestCommit');
        $test->testCommit();
        $this->assertEquals(1,$this->countRow());
    }

    public function testRequiredRollback()
    {
    	$config = array(
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\\TestAdvisor' => array(
			            'advices' => array(
			            	array(
			                    'type' => 'around',
			                    'pointcut' => 'execution('.__NAMESPACE__.'\TestCommit::testRollback())',
			                    'method' => 'required',
			            	),
			            ),
			        ),
                ),
            ),
        );
    	$config = array_replace_recursive($config, $this->getConfig());

        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestCommit');
        try {
	        $test->testRollback();
        } catch(TestException $e) {
        	;
        }
        $this->assertEquals(0,$this->countRow());
    }

    /**
     * @expectedException Rindow\Transaction\Exception\IllegalStateException
     */
    public function testMandatoryNoTransaction()
    {
    	$config = array(
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\\TestAdvisor' => array(
			            'advices' => array(
			            	array(
			                    'type' => 'around',
			                    'pointcut' => 'execution('.__NAMESPACE__.'\TestCommit::testCommit())',
			                    'method' => 'mandatory',
			            	),
			            ),
			        ),
                ),
            ),
        );
    	$config = array_replace_recursive($config, $this->getConfig());

        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestCommit');
	    $test->testCommit();
    }

    public function testMandatoryActiveTransaction()
    {
    	$config = array(
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\\TestAdvisor' => array(
			            'advices' => array(
			            	array(
			                    'type' => 'around',
			                    'pointcut' => 'execution('.__NAMESPACE__.'\TestCommit::testCommit())',
			                    'method' => 'mandatory',
			            	),
			            	array(
			                    'type' => 'around',
			                    'pointcut' => 'execution('.__NAMESPACE__.'\TestCommit::testTierOne())',
			                    'method' => 'required',
			            	),
			            ),
			        ),
                ),
            ),
        );
    	$config = array_replace_recursive($config, $this->getConfig());

        $mm = new ModuleManager($config);
        $test = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestCommit');
	    $test->testTierOne();
        $this->assertEquals(1,$this->countRow());
    }
}