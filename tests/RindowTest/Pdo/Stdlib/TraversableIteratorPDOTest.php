<?php
namespace RindowTest\Database\Pdo\Stdlib\TraversableIteratorPDOTest;

use PHPUnit\Framework\TestCase;
use PDO;
use IteratorIterator;

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
    }
    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        try {
            $client = $this->getClient();
            $client->exec("DROP TABLE IF EXISTS testdb");
            $client->exec("CREATE TABLE testdb ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)");
            $client->exec("INSERT INTO testdb (name) values ('foo1')");
            $client->exec("INSERT INTO testdb (name) values ('foo2')");
        } catch(\Exception $e) {
            self::$skip = $e->getMessage();
            $this->markTestSkipped(self::$skip);
            return;
        }
    }
    public function getClient()
    {
        $dsn = "sqlite:".self::$RINDOW_TEST_DATA."/test.db.sqlite";
        $username = null;
        $password = null;
        $options  = array();
        $client = new PDO($dsn, $username, $password, $options);
        $client->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
        return $client;
    }
    public function getIterator($client=null)
    {
        if($client==null)
            $client = $this->getClient();
        return $client->query("SELECT * FROM testdb ORDER BY id");
    }

    public function testIterator()
    {
        $client = $this->getClient();
        $stmt = $this->getIterator($client);
        $iterator = new IteratorIterator($stmt);

        $results = array();
        foreach ($iterator as $value) {
            $results[] = $value;
        }
        $this->assertCount(2,$results);

        $stmt = $this->getIterator($client);
        $iterator = new IteratorIterator($stmt);
        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertEquals(array('id'=>1,'name'=>'foo1'),$iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertEquals(array('id'=>2,'name'=>'foo2'),$iterator->current());
        $iterator->next();
        $this->assertFalse($iterator->valid());

        $iterator->rewind();

        // ***** it is NOT able to rewind ******
        $this->assertFalse($iterator->valid());
    }

    public function testNestIterator()
    {
        $client = $this->getClient();
        $stmt = $this->getIterator($client);
        $iterator = new IteratorIterator($stmt);
        $iterator = new IteratorIterator($iterator);

        $results = array();
        foreach ($iterator as $value) {
            $results[] = $value;
        }
        $this->assertCount(2,$results);
    }
}
