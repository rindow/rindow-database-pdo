<?php
namespace RindowTest\Database\Pdo\Orm\AbstractMapperTest;

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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\AbstractMapperTest\Category';
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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\AbstractMapperTest\Color';
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

    const CLASS_PRODUCT = 'RindowTest\Database\Pdo\Orm\AbstractMapperTest\Product';

    protected $productRepository;
    protected $mappingClasses = array(
        'product' => self::CLASS_PRODUCT,
    );
    protected $queryStrings = array(
        ".all" => "SELECT * FROM color",
        ".by.product" => "SELECT * FROM color WHERE product = :product",
    );

    //public function getProductRepository()
    //{
    //    if($this->productRepository)
    //        return $this->productRepository;
    //    return $this->productRepository = $this->entityManager->getRepository(self::CLASS_PRODUCT);
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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\AbstractMapperTest\Product';
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

    const CLASS_CATEGORY = 'RindowTest\Database\Pdo\Orm\AbstractMapperTest\Category';
    const CLASS_COLOR    = 'RindowTest\Database\Pdo\Orm\AbstractMapperTest\Color';

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

    public function getConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Persistence\OrmShell\Module' => true,
                    'Rindow\Database\Pdo\StandaloneModule' => true,
                ),
            ),
            'container' => array(
                'aliases' => array(
                    'EntityManager' => 'Rindow\\Persistence\\OrmShell\\DefaultEntityManager',
                ),
                'components' => array(
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


    public function getLocalTransactionConfig()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Persistence\OrmShell\Module' => true,
                    'Rindow\Database\Pdo\LocalTxModule' => true,
                ),
                'enableCache' => false,
            ),
            'container' => array(
                'aliases' => array(
                    'EntityManager' => 'Rindow\\Persistence\\OrmShell\\Transaction\\DefaultPersistenceContext',
                    'TransactionManager' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager',
                ),
                'components' => array(
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

    public function testSimpleCreateReadUpdateDelete()
    {
        /* Create */
        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');

        $category = new Category();
        $category->name = 'test';
        $em->persist($category);
        $em->flush();
        $em->close();

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
        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $category = $em->find(__NAMESPACE__.'\Category', 1);
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
        $em->flush();

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
        $em->remove($category);
        $em->flush();

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
        $em->close();
    }


    public function testRefresh()
    {
        /* Create */
        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');

        $category = new Category();
        $category->name = 'test';
        $em->persist($category);
        $em->flush();
        $em->close();

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
        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $category = $em->find(__NAMESPACE__.'\Category', 1);
        $this->assertEquals(1,$category->id);
        $this->assertEquals('test',$category->name);
        $category->name = 'update';
        $this->assertEquals('update',$category->name);
        $em->refresh($category);
        $this->assertEquals('test',$category->name);
    }

    public function testSubsidiaryPersistOnCreate1()
    {
        $product = new Product();
        $product->name = 'prod1';
        $product->category = new Category();
        $product->category->id = 1;
        $product->category->name = 'cat1';
        $product->addColor(2);
        $product->addColor(3);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $em->persist($product);
        $em->flush();

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM product",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'category'=>'1','name'=>'prod1'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        $smt = $pdo->query("SELECT * FROM color",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($count==0)
                $this->assertEquals(array('id'=>1,'product'=>1,'color'=>2),$row);
            else
                $this->assertEquals(array('id'=>2,'product'=>1,'color'=>3),$row);
            $count++;
        }
        $this->assertEquals(2,$count);
        unset($pdo);

        $em->close();
    }

    public function testSupplementEntity1()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,3)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $product = $em->find(__NAMESPACE__.'\Product', 1);
        $this->assertEquals(1,$product->id);
        $this->assertEquals('prod1',$product->name);
        $this->assertEquals('cat1',$product->category->name);
        $colors = array();
        foreach ($product->colors as $color) {
            $colors[] = $color;
        }
        $this->assertCount(2,$colors);
        $this->assertEquals(1,$colors[0]->id);
        $this->assertEquals(2,$colors[0]->color);
        $this->assertEquals(1,$colors[0]->product->id);
        $this->assertEquals(2,$colors[1]->id);
        $this->assertEquals(3,$colors[1]->color);
        $this->assertEquals(1,$colors[1]->product->id);

        $em->close();
    }

    public function testSubsidiaryRemove1()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,3)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $product = $em->find(__NAMESPACE__.'\Product', 1);
        $this->assertEquals(1,$product->id);
        $this->assertEquals('prod1',$product->name);
        $this->assertEquals('cat1',$product->category->name);
        $colors = array();
        foreach ($product->colors as $color) {
            $colors[] = $color;
        }
        $this->assertCount(2,$colors);
        $this->assertEquals(1,$colors[0]->id);
        $this->assertEquals(2,$colors[0]->color);
        $this->assertEquals(1,$colors[0]->product->id);
        $this->assertEquals(2,$colors[1]->id);
        $this->assertEquals(3,$colors[1]->color);
        $this->assertEquals(1,$colors[1]->product->id);

        $em->remove($product);
        return;
        $em->flush();

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'cat1'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        $smt = $pdo->query("SELECT * FROM color",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        $smt = $pdo->query("SELECT * FROM product",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);

        unset($pdo);

        $em->close();
    }

    public function testSubsidiaryUpdateAndCascadeRemove1()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,3)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $product = $em->find(__NAMESPACE__.'\Product', 1);
        $this->assertEquals(1,$product->id);
        $this->assertEquals('prod1',$product->name);
        $this->assertEquals('cat1',$product->category->name);
        $colors = array();
        foreach ($product->colors as $color) {
            $colors[] = $color;
        }
        $this->assertCount(2,$colors);
        $this->assertEquals(1,$colors[0]->id);
        $this->assertEquals(2,$colors[0]->color);
        $this->assertEquals(1,$colors[0]->product->id);
        $this->assertEquals(2,$colors[1]->id);
        $this->assertEquals(3,$colors[1]->color);
        $this->assertEquals(1,$colors[1]->product->id);

        // ======= Update category name and Remove ColorId =========
        $product->category->name = 'Updated';
        unset($product->colors[1]);
        $em->flush();
        // =========================================================

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'name'=>'Updated'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        $smt = $pdo->query("SELECT * FROM color",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'product'=>1,'color'=>2),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        unset($pdo);

        $em->close();
    }

    public function testSubsidiaryUpdateAndCascadePersist1()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO category (name) VALUES ('cat2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,3)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $product = $em->find(__NAMESPACE__.'\Product', 1);
        $this->assertEquals(1,$product->id);
        $this->assertEquals('prod1',$product->name);
        $this->assertEquals('cat1',$product->category->name);
        $colors = array();
        foreach ($product->colors as $color) {
            $colors[] = $color;
        }
        $this->assertCount(2,$colors);
        $this->assertEquals(1,$colors[0]->id);
        $this->assertEquals(2,$colors[0]->color);
        $this->assertEquals(1,$colors[0]->product->id);
        $this->assertEquals(2,$colors[1]->id);
        $this->assertEquals(3,$colors[1]->color);
        $this->assertEquals(1,$colors[1]->product->id);

        // ============== Change category and Add ColorId ==========
        $category2 = $em->find(__NAMESPACE__.'\Category', 2);
        $product->category = $category2;
        $product->addColor(4);
        $em->flush();
        // =========================================================

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM product",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $this->assertEquals(array('id'=>1,'category'=>2,'name'=>'prod1'),$row);
            $count++;
        }
        $this->assertEquals(1,$count);
        $smt = $pdo->query("SELECT * FROM color",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($count==0)
                $this->assertEquals(array('id'=>1,'product'=>1,'color'=>2),$row);
            elseif($count==1)
                $this->assertEquals(array('id'=>2,'product'=>1,'color'=>3),$row);
            else
                $this->assertEquals(array('id'=>3,'product'=>1,'color'=>4),$row);
            $count++;
        }
        $this->assertEquals(3,$count);
        unset($pdo);

        $em->close();
    }


    public function testLocalTransactionalSimpleCreateReadUpdateDelete()
    {
        /* Create */
        $mm = new ModuleManager($this->getLocalTransactionConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $tx = $mm->getServiceLocator()->get('TransactionManager');

        $tx->begin();
        $category = new Category();
        $category->name = 'test';
        $em->persist($category);
        $tx->commit();

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
        $em = $mm->getServiceLocator()->get('EntityManager');
        $tx = $mm->getServiceLocator()->get('TransactionManager');
        $tx->begin();
        $category = $em->find(__NAMESPACE__.'\Category', 1);
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
        $tx->commit();

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
        $em = $mm->getServiceLocator()->get('EntityManager');
        $tx = $mm->getServiceLocator()->get('TransactionManager');
        $tx->begin();
        $category = $em->find(__NAMESPACE__.'\Category', 1);
        $this->assertEquals(1,$category->id);
        $this->assertEquals('updated',$category->name);

        $em->remove($category);
        $tx->commit();

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM category",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            $count++;
        }
        $this->assertEquals(0,$count);
        unset($pdo);
    }

    public function testNamedQuery()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,3)");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod2')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (2,1)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (2,2)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $dummy = new Product();
        $productClassName = get_class($dummy);
        $query = $em->createNamedQuery("product.by.category");
        $this->assertEquals('SELECT * FROM product WHERE category = :category',$query->getPreparedCriteria()->getSql());
        $query->setParameter('category',1);
        $results = $query->getResultList();
        $productCount = 0;
        foreach ($results as $product) {
            $this->assertInstanceOf(__NAMESPACE__.'\Product',  $product);
            $this->assertInstanceOf(__NAMESPACE__.'\Category', $product->category);
            $colorCount = 0;
            foreach ($product->colors as $color) {
                $this->assertInstanceOf(__NAMESPACE__.'\Color',$color);
                $colorCount++;
            }
            $this->assertEquals(2,$colorCount);
            $productCount++;
        }
        $this->assertEquals(2,$productCount);
        $em->close();
    }
}
