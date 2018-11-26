<?php
namespace RindowTest\Database\Pdo\Transaction\Distributed\DataSourceTest;

use PHPUnit\Framework\TestCase;
use Rindow\Database\Pdo\Transaction\Xa\DataSource;
use Rindow\Database\Pdo\Transaction\Xa\Connection;
use Rindow\Database\Pdo\Driver\DriverInterface;
use Interop\Lenient\Transaction\Xa\XAResource as XAResourceInterface;
use Interop\Lenient\Transaction\Xa\Xid as XidInterface;
use Rindow\Transaction\Distributed\TransactionManager;

class TestLogger
{
    public $logdata = array();
    public function log($message)
    {
        $this->logdata[] = $message;
    }
}

/*
class TestDatabasePlatform
{
	protected $eventManager;
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
    }
}
*/
/*
class TestDriver implements DriverInterface
{
    public function errorCodeMapping($errorCode,$errorMessage){}
    public function getName() {return 'test';}
    public function getPlatform() {return 'test';}
    public function getCursorOptions($resultListType,$resultListConcurrency,$resultListHoldability){return array();}
}
*/
class TestXAResource implements XAResourceInterface
{
    public $logger;
    public function commit(/*XidInterface*/ $xid, $onePhase)
    { $this->logger->log('commit'); }
    public function end(/*XidInterface*/ $xid, $flags)
    { $this->logger->log('end'); }
    public function forget(/*XidInterface*/ $xid)
    { $this->logger->log('forget'); }
    public function getTransactionTimeout(){}
    public function isSameRM(/*XAResourceInterface*/ $xares)
    { $this->logger->log('isSameRM'); }
    public function prepare(/*XidInterface*/ $xid)
    { $this->logger->log('prepare'); }
    public function recover($flag)
    { $this->logger->log('recover'); }
    public function rollback(/*XidInterface*/ $xid)
    { $this->logger->log('rollback'); }
    public function setTransactionTimeout($seconds){}
    public function start(/*XidInterface*/ $xid, $flags)
    {
        $this->logger->log('start');
        if($flags != XAResourceInterface::TMNOFLAGS)
            throw new TestException('unmatch start option');
    }
}

class TestDriverConnection
{
    public function setAttribute($mode,$value)
    {
    }
}

class TestConnection extends Connection
{
    protected function createPDOConnection($dsn ,$user, $password, $options)
    {
        $this->connection = new TestDriverConnection();
    }
}

class TestXADataSource extends DataSource
{
    protected $connectionClass = 'RindowTest\Database\Pdo\Transaction\Distributed\DataSourceTest\TestConnection';

	public $testXares;

    protected function getResource($connection)
    {
        $this->logger->log('getResource');
        return $this->testXares;
    }
}

class Test extends TestCase
{
    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

    public function testNormal()
    {
    	$config = array(
	    	'dsn' => 'test',
    	);
        $logger = new TestLogger();
    	$tm = new TransactionManager();
        $ds = new TestXADataSource();
        $ds->testXares = new TestXAResource();
        $ds->testXares->logger = $logger;
        $ds->setLogger($logger);
        $ds->setConfig($config);
        $ds->setTransactionManager($tm);

        $logger->log('[get connection]');
        $conn = $ds->getConnection();
        $this->assertEquals(__NAMESPACE__.'\TestConnection',get_class($conn));
        //$this->assertEquals('Rindow\Database\Pdo\Transaction\Xa\Connection',get_class($conn));

        $logger->log('[begin transaction]');
        $tm->begin();

        $logger->log('[get connection]');
        $conn2 = $ds->getConnection();
        $this->assertEquals($conn,$conn2);

        //$conn->connect();
        $this->assertEquals(array(
                '[get connection]',
                '[begin transaction]',
                '[get connection]',
                'getResource',
                'start',
            ),
            $logger->logdata
        );
    }

    public function testNoTransaction()
    {
    	$config = array(
            'dsn' => 'test',
    	);
        $logger = new TestLogger();
        $tm = new TransactionManager();
        $ds = new TestXADataSource();
        $ds->testXares = new TestXAResource();
        $ds->testXares->logger = $logger;
        $ds->setLogger($logger);
        $ds->setConfig($config);
        $ds->setTransactionManager($tm);

        // $tm->begin();

        $logger->log('[get connection]');
        $conn = $ds->getConnection();
        $this->assertEquals(__NAMESPACE__.'\TestConnection',get_class($conn));
        //$this->assertEquals('Rindow\Database\Pdo\Transaction\Xa\Connection',get_class($conn));

        $logger->log('[get connection]');
        $conn2 = $ds->getConnection();
        $this->assertEquals($conn,$conn2);
        //$conn->connect();
        $this->assertEquals(array(
                '[get connection]',
                '[get connection]',
            ),
            $logger->logdata
        );
    }

/*
    public function testTimingBeginFirst()
    {

    	$xid = 'foo';
        $xares = $this->getMock(__NAMESPACE__.'\TestXAResource');
		$xares->expects($this->at(0))
				->method('lap')
                ->with(
                	$this->equalTo(0)
                );
		$xares->expects($this->at(1))
				->method('lap')
                ->with(
                	$this->equalTo(1)
                );
		$xares->expects($this->at(2))
				->method('lap')
                ->with(
                	$this->equalTo(2)
                );
		$xares->expects($this->at(3))
				->method('start')
                ->with(
                	$this->anything(),
                	$this->equalTo(XAResourceInterface::TMNOFLAGS)
                );
		$xares->expects($this->at(4))
				->method('lap')
                ->with(
                	$this->equalTo(3)
                );
		$xares->expects($this->at(5))
				->method('lap')
                ->with(
                	$this->equalTo(4)
                );
		$xares->expects($this->at(6))
				->method('lap')
                ->with(
                	$this->equalTo(5)
                );
		$xares->expects($this->at(7))
				->method('lap')
                ->with(
                	$this->equalTo(6)
                );
        $config = array(
            'dsn' => 'test',
        );
        $logger = new TestLogger();
        $tm = new TransactionManager();
        $ds = new TestXADataSource();
        $ds->testXares = new TestXAResource();
        $ds->testXares->logger = $logger;
        $ds->setLogger($logger);
        $ds->setConfig($config);
        $ds->setTransactionManager($tm);

        //$xares->lap(0);
        $tm->begin();

        //$xares->lap(1);
        $conn = $ds->getConnection();
        //$xares->lap(2);
        $conn->connect();
        //$xares->lap(3);
        $conn = $ds->getConnection();
        //$xares->lap(4);
        //$conn->connect();
        //$xares->lap(5);
        $conn = $ds->getConnection();
        //$xares->lap(6);
        $this->assertEquals(array(
            ),
            $logger->logdata
        );
    }

    public function testTimingConnectFirst()
    {
    	$xid = 'foo';
        $xares = $this->getMock(__NAMESPACE__.'\TestXAResource');
		$xares->expects($this->at(0))
				->method('lap')
                ->with(
                	$this->equalTo(0)
                );
		$xares->expects($this->at(1))
				->method('lap')
                ->with(
                	$this->equalTo(1)
                );
		$xares->expects($this->at(2))
				->method('lap')
                ->with(
                	$this->equalTo(2)
                );
		$xares->expects($this->at(3))
				->method('lap')
                ->with(
                	$this->equalTo(3)
                );
		$xares->expects($this->at(4))
				->method('start')
                ->with(
                	$this->anything(),
                	$this->equalTo(XAResourceInterface::TMNOFLAGS)
                );
		$xares->expects($this->at(5))
				->method('lap')
                ->with(
                	$this->equalTo(4)
                );
		$xares->expects($this->at(6))
				->method('lap')
                ->with(
                	$this->equalTo(5)
                );
		$xares->expects($this->at(7))
				->method('lap')
                ->with(
                	$this->equalTo(6)
                );
        $config = array(
            'dsn' => 'test',
        );
    	$tm = new TransactionManager();
        $ds = new TestXADataSource();
        $ds->testXares = $xares;
        $ds->setConfig($config);
        $ds->setTransactionManager($tm);

        $xares->lap(0);
        $conn = $ds->getConnection();
        $xares->lap(1);
        $conn->connect();
        $xares->lap(2);
        $tm->begin();
        $xares->lap(3);
        $conn = $ds->getConnection();
        $xares->lap(4);
        $conn->connect();
        $xares->lap(5);
        $conn = $ds->getConnection();
        $xares->lap(6);
    }
*/
}