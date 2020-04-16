<?php
namespace RindowTest\Database\Pdo\Repository\PdoRepositoryTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Interop\Lenient\Dao\Query\Expression;
use Interop\Lenient\Dao\Repository\DataMapper;
//use Rindow\Database\Dao\Repository\GenericSqlRepositoryFactory;
use Rindow\Database\Dao\Repository\GenericSqlRepository;
use Rindow\Database\Dao\Sql\TableTemplate;
use Rindow\Database\Dao\Support\QueryBuilder;
use Rindow\Transaction\Support\TransactionBoundary;
use Rindow\Database\Pdo\DataSource;

class TestDataMapper implements DataMapper
{
    public function map($data)
    {
        return $data;
    }

    public function demap($entity)
    {
        return get_object_vars($entity);
    }

    public function fillId($entity,$id)
    {
        $entity->id = $id;
        return $entity;
    }

    public function getFetchClass()
    {
        return __NAMESPACE__.'\TestEntity';
    }
}

class TestEntity
{
    public $id;
    public $name;
    public $day;
    public $ser;
}

class TestSqlRepository extends GenericSqlRepository
{
    public function map($data)
    {
        return $data;
    }

    public function demap($entity)
    {
        return get_object_vars($entity);
    }

    public function fillId($entity,$id)
    {
        if(is_object($entity))
            $entity->id = $id;
        else
            $entity['id'] = $id;
        return $entity;
    }

    public function getFetchClass()
    {
        return __NAMESPACE__.'\TestEntity';
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

    public function getRepository($tableName,$className=null,$keyName=null,$dataMapper=null)
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($config);
        $queryBuilder = new QueryBuilder();
        $tableOperations = new TableTemplate($dataSource,$queryBuilder);
        if($className) {
            $repository = new $className($tableOperations,$tableName,$keyName,$dataMapper);
        } else {
            $repository = new GenericSqlRepository($tableOperations,$tableName,$keyName,$dataMapper);
        }
        return $repository;
    }

    public function testCreateNormal()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

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
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $repository->save(array('id'=>1,'name'=>'update1'));

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
    }

    public function testUpdateUpsert()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);

        $pdo = $this->getPDOClient();
        $smt = $pdo->query("SELECT * FROM testdb",\PDO::FETCH_NAMED);
        $count = 0;
        foreach($smt as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testDeleteNormal()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $repository->deleteById(1);

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
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $cursor = $repository->findAll(array('name'=>'test2'));
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('ser',Expression::GREATER_THAN_OR_EQUAL,10));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('ser',Expression::GREATER_THAN,1));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('ser',Expression::LESS_THAN,10));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('ser',Expression::LESS_THAN_OR_EQUAL,1));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==1)
                $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('ser',Expression::IN,array(10)));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('ser',Expression::NOT_EQUAL,1));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $filter = array($repository->getQueryBuilder()->createExpression('name',Expression::BEGIN_WITH,'test2'));
        $cursor = $repository->findAll($filter);
        $count = 0;
        foreach($cursor as $row) {
            if($row['id']==2)
                $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
            else
                $this->assertTrue(false);
            $count++;
        }
        $this->assertEquals(1,$count);

        $cursor = $repository->findAll();
        $count = 0;
        foreach($cursor as $row) {
            $count++;
        }
        $this->assertEquals(2,$count);
    }

    public function testfindByIdNormal()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $row = $repository->findById(2);
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);
    }

    public function testfindByIdNoData()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $row = $repository->findById(100);
        $this->assertNull($row);
    }

    /**
     * @expectedException        Rindow\Database\Dao\Exception\RuntimeException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 no such table: notable
     */
    public function testFindByIdNoTable()
    {
        $repository = $this->getRepository('notable');
        $row = $repository->findById(1);
        $this->assertNull($row);
    }

    public function testCount()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $count = $repository->count();
        $this->assertEquals(2,$count);

        $count = $repository->count(array('id'=>2));
        $this->assertEquals(1,$count);

        $count = $repository->count(array('id'=>100));
        $this->assertEquals(0,$count);
    }

    public function testExistsById()
    {
        $repository = $this->getRepository('testdb');
        $row = $repository->save(array('name'=>'test','day'=>1,'ser'=>1));
        $this->assertEquals(array('id'=>1,'name'=>'test','day'=>1,'ser'=>1),$row);
        $row = $repository->save(array('name'=>'test2','day'=>1,'ser'=>10));
        $this->assertEquals(array('id'=>2,'name'=>'test2','day'=>1,'ser'=>10),$row);

        $this->assertTrue($repository->existsById(1));
        $this->assertTrue($repository->existsById(2));
        $this->assertFalse($repository->existsById(100));
    }

    public function testDataMapper()
    {
        $repository = $this->getRepository('testdb');
        $repository->setDataMapper(new TestDataMapper());

        $entity = new TestEntity();
        $entity->id = null;
        $entity->name = 'a1';
        $entity->day = 1;
        $entity->ser = 1;
        $entity2 = $repository->save($entity);
        $this->assertEquals(array('name'=>'a1','day'=>1,'ser'=>1,'id'=>1),get_object_vars($entity));
        $this->assertEquals($entity,$entity2);
        $id = $entity->id;

        $entity = new TestEntity();
        $entity->id = 1;
        $entity->name = 'boo';
        $entity->day = 1;
        $entity->ser = 1;
        $entity2 = $repository->save($entity);
        $this->assertEquals(array('id'=>1,'name'=>'boo','day'=>1,'ser'=>1),get_object_vars($entity));
        $this->assertEquals($entity,$entity2);

        $results = $repository->findAll();
        $count=0;
        foreach ($results as $entity) {
            $r = new TestEntity();
            $r->id = $id;
            $r->name = 'boo';
            $r->day = 1;
            $r->ser = 1;
            $this->assertEquals($r,$entity);
            $count++;
        }
        $this->assertEquals(1,$count);
    }

    public function testCustomSqlRepository()
    {
        $repository = $this->getRepository('testdb',__NAMESPACE__.'\TestSqlRepository');

        $entity = new TestEntity();
        $entity->id = null;
        $entity->name = 'a1';
        $entity->day = 1;
        $entity->ser = 1;
        $entity2 = $repository->save($entity);
        $this->assertEquals(array('name'=>'a1','day'=>1,'ser'=>1,'id'=>1),get_object_vars($entity));
        $this->assertEquals($entity,$entity2);
        $id = $entity->id;

        $entity = new TestEntity();
        $entity->id = 1;
        $entity->name = 'boo';
        $entity->day = 1;
        $entity->ser = 1;
        $entity2 = $repository->save($entity);
        $this->assertEquals(array('id'=>1,'name'=>'boo','day'=>1,'ser'=>1),get_object_vars($entity));
        $this->assertEquals($entity,$entity2);

        $results = $repository->findAll();
        $count=0;
        foreach ($results as $entity) {
            $r = new TestEntity();
            $r->id = $id;
            $r->name = 'boo';
            $r->day = 1;
            $r->ser = 1;
            $this->assertEquals($r,$entity);
            $count++;
        }
        $this->assertEquals(1,$count);
    }
}
