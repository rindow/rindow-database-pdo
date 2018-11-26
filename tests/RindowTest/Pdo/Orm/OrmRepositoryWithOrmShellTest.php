<?php
namespace RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest;

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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest\Category';
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

    protected $queryStrings = array(
        ".all" => "SELECT * FROM category",
    );

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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest\Color';
    const TABLE_NAME = 'color';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE color(id INTEGER PRIMARY KEY AUTOINCREMENT, product INTEGER, color INTEGER)";
    const DROP_TABLE = "DROP TABLE color";
    const INSERT = "INSERT INTO color ( product , color ) VALUES ( :product , :color )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE color SET product = :product , color = :color WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM color WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM color WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM color";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM color";

    const CLASS_PRODUCT = 'RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest\Product';

    protected $mappingClasses = array(
        'product' => self::CLASS_PRODUCT,
    );
    protected $queryStrings = array(
        ".all" => "SELECT * FROM color",
        ".by.product" => "SELECT * FROM color WHERE product = :product",
    );

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
        $entity->product = $entityManager->find(self::CLASS_PRODUCT,$entity->product);
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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest\Product';
    const TABLE_NAME = 'product';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE product(id INTEGER PRIMARY KEY AUTOINCREMENT, category INTEGER, name TEXT)";
    const DROP_TABLE = "DROP TABLE product";
    const INSERT = "INSERT INTO product (category,name) VALUES ( :category , :name )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE product SET category = :category , name = :name WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM product WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM product WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM product";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM product";

    const CLASS_CATEGORY = 'RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest\Category';
    const CLASS_COLOR    = 'RindowTest\Database\Pdo\Orm\OrmRepositoryWithOrmShellTest\Color';

    protected $categoryRepository;
    protected $colorRepository;
    protected $mappingClasses = array(
        'category' => self::CLASS_CATEGORY,
    );
    protected $queryStrings = array(
        "product.all" => "SELECT * FROM product",
        "product.by.category" => "SELECT * FROM product WHERE category = :category",
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
        $client->exec(ColorMapper::DROP_TABLE);
        $client->exec(CategoryMapper::DROP_TABLE);
        $client->exec(ProductMapper::DROP_TABLE);
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
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

    public function getLocalTransactionConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                    'Rindow\Transaction\Local\Module' => true,
                    'Rindow\Persistence\Orm\Module' => true,
                    'Rindow\Persistence\OrmShell\Module' => true,
                    'Rindow\Database\Pdo\LocalTxModule' => true,
                ),
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\ProductRepository'=>array(
                    	'parent' => 'Rindow\\Persistence\\Orm\\Repository\\AbstractOrmRepository',
                    	'properties' => array(
                    		'className' => array('value'=>__NAMESPACE__.'\\Product'),
                    	),
                    ),
                    __NAMESPACE__.'\CategoryRepository'=>array(
                    	'parent' => 'Rindow\\Persistence\\Orm\\Repository\\AbstractOrmRepository',
                    	'properties' => array(
                    		'className' => array('value'=>__NAMESPACE__.'\\Category'),
                    	),
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
            'database' => array(
                'connections' => array(
                    'default' => array(
                        'dsn' => "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite",
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

    public function testLocalTransactionalSimpleCreateReadUpdateDelete()
    {
        /* Create */
        $mm = new ModuleManager($this->getLocalTransactionConfig());
        $products = $mm->getServiceLocator()->get(__NAMESPACE__.'\ProductRepository');
        $categories = $mm->getServiceLocator()->get(__NAMESPACE__.'\CategoryRepository');

        $category = new Category();
        $category->name = 'test';
        $categories->save($category);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);

        /* Read */
        $mm = new ModuleManager($this->getLocalTransactionConfig());
        $categories = $mm->getServiceLocator()->get(__NAMESPACE__.'\CategoryRepository');

        $category = $categories->findById(1);
        $this->assertEquals(1,$category->id);
        $this->assertEquals('test',$category->name);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'test'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);

        /* Update */
        $category->name ='updated';
        $categories->save($category);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'updated'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);

        /* Delete */
        $mm = new ModuleManager($this->getLocalTransactionConfig());
        $categories = $mm->getServiceLocator()->get(__NAMESPACE__.'\CategoryRepository');

        $category = $categories->findById(1);
        $this->assertEquals(1,$category->id);
        $this->assertEquals('updated',$category->name);

        $categories->delete($category);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }
}
