<?php
namespace RindowTest\Database\Pdo\Orm\CriteriaMapperTest;

use PHPUnit\Framework\TestCase;
use Rindow\Persistence\Orm\Criteria\CriteriaBuilder;
use Rindow\Database\Pdo\Orm\CriteriaMapper;
use Rindow\Container\Container;

use Rindow\Stdlib\Entity\AbstractEntity;
use Rindow\Stdlib\Entity\PropertyAccessPolicy;
use Rindow\Container\ModuleManager;
use Rindow\Database\Pdo\Orm\AbstractMapper;
use Rindow\Persistence\OrmShell\EntityManager;
use Rindow\Persistence\OrmShell\DataMapper;

class TestMapper
{
	public function tablename()
	{
		return 'testtable';
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

class CategoryMapper extends AbstractMapper
{
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\CriteriaMapperTest\Category';
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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\CriteriaMapperTest\Color';
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

    const CLASS_PRODUCT = 'RindowTest\Database\Pdo\Orm\CriteriaMapperTest\Product';

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
    const CLASS_NAME = 'RindowTest\Database\Pdo\Orm\CriteriaMapperTest\Product';
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

    const CLASS_CATEGORY = 'RindowTest\Database\Pdo\Orm\CriteriaMapperTest\Category';
    const CLASS_COLOR    = 'RindowTest\Database\Pdo\Orm\CriteriaMapperTest\Color';

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
                    // Pdo
        
                    // MongoDB
                    //'Database'      => 'Rindow\Module\Mongodb\DefaultConnection',
        
                    'EntityManager' => 'Rindow\Persistence\OrmShell\DefaultEntityManager',
                    'CriteriaBuilder' => 'Rindow\Persistence\OrmShell\DefaultCriteriaBuilder',
                    'PaginatorFactory' => 'Rindow\Persistence\OrmShell\Paginator\DefaultPaginatorFactory',
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

    public function testBuilder()
    {
        $cb = new CriteriaBuilder();
        $q = $cb->createQuery('FooResult');
        $this->assertInstanceOf('Rindow\Persistence\Orm\Criteria\CriteriaQuery',$q);

        $r = $q->from('FooEntity')->alias('p');
        $p1 = $cb->parameter('integer','p1');
        $q->select($r)
            ->distinct(true)
            ->where($cb->and_(
                $cb->gt($r->get('field1'),$p1),
                $cb->lt($r->get('field1_2'),$p1)))
            ->orderBy(
                $cb->desc($r->get('field2')),
                $r->get('field2_2'))
            ->groupBy(
                $r->get('field3'),
                $r->get('field3_2'))
            ->having($cb->gt($r->get('field4'),100));

        $className = 'FooEntity';
        $mapperName = __NAMESPACE__.'\TestMapper';
        $config = array(
            'components'=>array(
                __NAMESPACE__.'\TestMapper'=>array('scope'=>'prototype'),
            ),
        );
       	$sm = new Container($config);
        $em = new EntityManager();
        $em->setServiceLocator($sm);
        $em->registerMapper($className, $mapperName);
        $cd = new CriteriaMapper($em);
        $preparedCriteria = $cd->prepare($q);

        $sql = "SELECT distinct p.* FROM testtable as p".
                " WHERE (p.field1 > :p1) AND (p.field1_2 < :p1)".
                " GROUP BY p.field3 , p.field3_2 HAVING p.field4 > 100".
                " ORDER BY p.field2 DESC,p.field2_2";
        $this->assertEquals($sql,$preparedCriteria->getSql());
    }


    public function testQueryCount()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO category (name) VALUES ('cat2')");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $cb = $mm->getServiceLocator()->get('CriteriaBuilder');

        $q = $cb->createQuery();
        $root = $q->from(__NAMESPACE__.'\Category');
        $q->select($cb->count($root));

        $query = $em->createQuery($q);
        $result = $query->getSingleResult();
        $this->assertEquals(2,$result);

        $em->close();
    }

    public function testCriteriaQueryWhere()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO category (name) VALUES ('cat2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (2,'prod2')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (2,3)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $cb = $mm->getServiceLocator()->get('CriteriaBuilder');

        $q = $cb->createQuery();
        $root = $q->from(__NAMESPACE__.'\Product');
        $p = $cb->parameter(null,'p');
        $q->select($root)
            ->where($cb->ge($root->get('category'),$p));
        $query = $em->createQuery($q);
        $query->setParameter('p',2);
        $results = $query->getResultList();
        $count = 0;
        foreach ($results as $product) {
            $this->assertEquals(2,$product->id);
            $this->assertEquals('prod2',$product->name);
            $this->assertEquals(2,$product->category->id);
            $this->assertEquals('cat2',$product->category->name);
            $colorCount=0;
            foreach ($product->colors as $color) {
                $this->assertEquals(3,$color->color);
                $colorCount++;
            }
            $this->assertEquals(1,$colorCount);
            $count++;
        }
        $this->assertEquals(1,$count);

        $em->close();
    }

    public function testCriteriaQueryGroupByHaving()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO category (name) VALUES ('cat2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (2,'prod2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (2,'prod3')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (2,3)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (3,4)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $cb = $mm->getServiceLocator()->get('CriteriaBuilder');

        $q = $cb->createQuery();
        $root = $q->from(__NAMESPACE__.'\Product');
        $p = $cb->parameter(null,'p');
        $q->multiselect(
                $root->get('category')->alias('category'),
                //$root->get('id')->alias('id')
                $cb->count($root)->alias('count')
            )
            ->groupBy($root->get('category'))
            ->having($cb->ge($cb->count($root),2));

        $query = $em->createQuery($q);
        $results = $query->getResultList();
        $count = 0;
        foreach ($results as $row) {
            $this->assertEquals(2,$row['category']);
            $this->assertEquals(2,$row['count']);
            $count++;
        }
        $this->assertEquals(1,$count);

        $em->close();
    }

    public function testCriteriaQueryJoin()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO category (name) VALUES ('cat2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (2,'prod2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (2,'prod3')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (2,3)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (3,4)");
        unset($pdo);

        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $cb = $mm->getServiceLocator()->get('CriteriaBuilder');

        $q = $cb->createQuery();
        $product = $q->from(__NAMESPACE__.'\Product');
        $p = $cb->parameter(null,'p');
        $category = $product->join('category');
        $category->on($cb->ge($category->get('name'),'cat2'));
        $q->select($product);

        $query = $em->createQuery($q);
        $results = $query->getResultList();
        $count = 0;
        foreach ($results as $prod) {
            if($count==0)
                $this->assertEquals('prod2',$prod->name);
            else
                $this->assertEquals('prod3',$prod->name);
            $count++;
        }
        $this->assertEquals(2,$count);

        $em->close();
    }

    public function testPaginator()
    {
        $pdo = $this->getPDOClient();
        $pdo->exec("INSERT INTO category (name) VALUES ('cat1')");
        $pdo->exec("INSERT INTO category (name) VALUES ('cat2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (2,'prod1')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod2')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod3')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod4')");
        $pdo->exec("INSERT INTO product (category,name) VALUES (1,'prod5')");
        $pdo->exec("INSERT INTO color (product,color) VALUES (1,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (2,3)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (3,1)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (4,2)");
        $pdo->exec("INSERT INTO color (product,color) VALUES (5,3)");
        unset($pdo);


        $mm = new ModuleManager($this->getConfig());
        $em = $mm->getServiceLocator()->get('EntityManager');
        $cb = $mm->getServiceLocator()->get('CriteriaBuilder');
        $paginatorFactory = $mm->getServiceLocator()->get('PaginatorFactory');

        /* page 1  */

        $q = $cb->createQuery();
        $root = $q->from(__NAMESPACE__.'\Product');
        $p = $cb->parameter(null,'p');
        $q->select($root)
            ->where($cb->equal($root->get('category'),$p));
        $query = $em->createQuery($q);
        $query->setParameter('p',1);

        $paginator = $paginatorFactory->createPaginator($query);
        $paginator->setItemMaxPerPage(3);
        $paginator->setPage(1);
        $count = 0;
        foreach ($paginator as $product) {
            if($count==0) {
                $this->assertEquals(2,$product->id);
            } elseif($count==1) {
                $this->assertEquals(3,$product->id);
            } elseif($count==2) {
                $this->assertEquals(4,$product->id);
            }
            $count++;
        }
        $this->assertEquals(3,$count);

        /* page 2  */

        $q = $cb->createQuery();
        $root = $q->from(__NAMESPACE__.'\Product');
        $p = $cb->parameter(null,'p');
        $q->select($root)
            ->where($cb->equal($root->get('category'),$p));
        $query = $em->createQuery($q);
        $query->setParameter('p',1);

        $paginator = $paginatorFactory->createPaginator($query);
        $paginator->setItemMaxPerPage(3);
        $paginator->setPage(2);
        $count = 0;
        foreach ($paginator as $product) {
            if($count==0) {
                $this->assertEquals(5,$product->id);
            }
            $count++;
        }
        $this->assertEquals(1,$count);

        $em->close();

    }
}
