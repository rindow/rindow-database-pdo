<?php
namespace RindowTest\Database\Pdo\Transaction\Local\ConnectionTest;

use PHPUnit\Framework\TestCase;
use PDO;
use Rindow\Database\Dao\Support\ResultList;
use Rindow\Database\Pdo\Transaction\Local\Connection;
use Rindow\Database\Pdo\Driver\Sqlite;

class TestException extends \Exception
{}

class TestClass
{
    public $id;
    public $name;
    public $day;
    public $ser;
    public $option;

    public function __construct($option = null)
    {
        $this->option = $option;
    }
}

class TestPDO
{
    public $log = array();
    public $throwError;
    public function __call($name,array $args)
    {
        $this->log[] = $name;
    }
    public function setAttribute($name,$value='')
    {
    }
    public function exec($statement)
    {
        $this->log[] = 'exec('.$statement.')';
        if($this->throwError && strpos($statement, 'SAVEPOINT')!==false)
            throw new TestException('SAVEPOINT error');
    }
    public function beginTransaction()
    {
        $this->log[] = 'beginTransaction';
        if($this->throwError)
            throw new TestException('beginTransaction error');
    }
    public function commit()
    {
        $this->log[] = 'commit';
        if($this->throwError)
            throw new TestException('commit error');
    }
    public function rollback()
    {
        $this->log[] = 'rollback';
        if($this->throwError)
            throw new TestException('rollback error');
    }
}

class TestConnection extends Connection
{
    protected function createPDOConnection($dsn ,$user, $password, $options)
    {
        $this->connection = new TestPDO();
    }
    public function txNestLevel()
    {
        return $this->txNestLevel;
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
        self::$RINDOW_TEST_DATA = __DIR__.'/../../../../data';
    }

    public static function tearDownAfterClass()
    {
    }

    public function setUp()
    {
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

    public function testNestTransactionLevelNormal()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $this->assertEquals(0,$connection->txNestLevel());
        $connection->beginTransaction();
        $this->assertEquals(1,$connection->txNestLevel());
        $connection->beginTransaction();
        $this->assertEquals(2,$connection->txNestLevel());
        $connection->beginTransaction();
        $this->assertEquals(3,$connection->txNestLevel());
        $connection->commit();
        $this->assertEquals(2,$connection->txNestLevel());
        $connection->rollback();
        $this->assertEquals(1,$connection->txNestLevel());
        $connection->commit();
        $this->assertEquals(0,$connection->txNestLevel());
        $connection->beginTransaction();
        $this->assertEquals(1,$connection->txNestLevel());
        $connection->beginTransaction();
        $this->assertEquals(2,$connection->txNestLevel());
        $connection->beginTransaction();
        $this->assertEquals(3,$connection->txNestLevel());
        $connection->rollback();
        $this->assertEquals(2,$connection->txNestLevel());
        $connection->commit();
        $this->assertEquals(1,$connection->txNestLevel());
        $connection->rollback();
        $this->assertEquals(0,$connection->txNestLevel());
        $this->assertEquals(
            array(
                'beginTransaction',
                'exec(SAVEPOINT rindow_tx_1)',
                'exec(SAVEPOINT rindow_tx_2)',
                'exec(RELEASE SAVEPOINT rindow_tx_2)',
                'exec(ROLLBACK TO SAVEPOINT rindow_tx_1)',
                'commit',
                'beginTransaction',
                'exec(SAVEPOINT rindow_tx_1)',
                'exec(SAVEPOINT rindow_tx_2)',
                'exec(ROLLBACK TO SAVEPOINT rindow_tx_2)',
                'exec(RELEASE SAVEPOINT rindow_tx_1)',
                'rollback',
            ),
            $connection->getRawConnection()->log);
    }

    public function testNestTransactionLevelError()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $connection->connect();

        // Fail begin transaction
        $this->assertEquals(0,$connection->txNestLevel());
        $connection->getRawConnection()->throwError = true;
        $catchException = false;
        try {
            $connection->beginTransaction();
        } catch (TestException $e) {
            $catchException = true;
        }
        $this->assertTrue($catchException);
        $this->assertEquals(0,$connection->txNestLevel()); // NOT count-up when fail to begin

        // success begin transaction
        $connection->getRawConnection()->throwError = false;
        $connection->beginTransaction();
        $this->assertEquals(1,$connection->txNestLevel());

        // Fail begin transaction(create savepoint)
        $connection->getRawConnection()->throwError = true;
        $catchException = false;
        try {
            $connection->beginTransaction();
        } catch (TestException $e) {
            $catchException = true;
        }
        $this->assertTrue($catchException);
        $this->assertEquals(1,$connection->txNestLevel()); // NOT count-up when fail to begin

        // Success begin transaction(create savepoint)
        $connection->getRawConnection()->throwError = false;
        $connection->beginTransaction();
        $this->assertEquals(2,$connection->txNestLevel());

        // Fail commit transaction(release savepoint)
        $connection->getRawConnection()->throwError = true;
        $catchException = false;
        try {
            $connection->commit();
        } catch (TestException $e) {
            $catchException = true;
        }
        $this->assertTrue($catchException);
        $this->assertEquals(1,$connection->txNestLevel()); // May rollback savepoint and count-down when fail to commit

        // Fail commit transaction
        $connection->getRawConnection()->throwError = true;
        $catchException = false;
        try {
            $connection->commit();
        } catch (TestException $e) {
            $catchException = true;
        }
        $this->assertTrue($catchException);
        $this->assertEquals(0,$connection->txNestLevel()); // May rollback transaction and count-down when fail to commit

        $connection->getRawConnection()->throwError = false;
        $connection->beginTransaction();
        $connection->beginTransaction();
        $this->assertEquals(2,$connection->txNestLevel());

        // Fail rollback transaction(rollback savepoint)
        $connection->getRawConnection()->throwError = true;
        $catchException = false;
        try {
            $connection->rollback();
        } catch (TestException $e) {
            $catchException = true;
        }
        $this->assertTrue($catchException);
        $this->assertEquals(1,$connection->txNestLevel()); // May rollback savepoint and count-down when fail to rollback

        // Fail rollback transaction
        $connection->getRawConnection()->throwError = true;
        $catchException = false;
        try {
            $connection->rollback();
        } catch (TestException $e) {
            $catchException = true;
        }
        $this->assertTrue($catchException);
        $this->assertEquals(0,$connection->txNestLevel()); // May rollback transaction and count-down when fail to rollback
    }

    public function testCommitOverRun1()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        try {
            $connection->commit();
        } catch(\Rindow\Database\Dao\Exception\DomainException $e) {
            $catchException = $e;
        }
        $this->assertEquals(0,$connection->txNestLevel());
        $this->assertEquals('No transaction',$catchException->getMessage());
    }

    public function testCommitOverRun2()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $connection->beginTransaction();
        $this->assertEquals(1,$connection->txNestLevel());
        $connection->commit();
        $this->assertEquals(0,$connection->txNestLevel());
        try {
            $connection->commit();
        } catch(\Rindow\Database\Dao\Exception\DomainException $e) {
            $catchException = $e;
        }
        $this->assertEquals(0,$connection->txNestLevel());
        $this->assertEquals('No transaction',$catchException->getMessage());
    }

    public function testRollbackOverRun1()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        try {
            $connection->rollback();
        } catch(\Rindow\Database\Dao\Exception\DomainException $e) {
            $catchException = $e;
        }
        $this->assertEquals(0,$connection->txNestLevel());
        $this->assertEquals('No transaction',$catchException->getMessage());
    }

    public function testRollbackOverRun2()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $connection->beginTransaction();
        $this->assertEquals(1,$connection->txNestLevel());
        $connection->rollback();
        $this->assertEquals(0,$connection->txNestLevel());
        try {
            $connection->rollback();
        } catch(\Rindow\Database\Dao\Exception\DomainException $e) {
            $catchException = $e;
        }
        $this->assertEquals(0,$connection->txNestLevel());
        $this->assertEquals('No transaction',$catchException->getMessage());
    }

    public function testResourceManager()
    {
        $config = $this->getConfig();
        $connection = new TestConnection($config);
        $mgr = $connection->getResourceManager();
        $this->assertInstanceof('Rindow\\Database\\Pdo\\Transaction\\Local\\PdoResourceManager',$mgr);
        $this->assertEquals(spl_object_hash($mgr),spl_object_hash($connection->getResourceManager()));
    }
}
