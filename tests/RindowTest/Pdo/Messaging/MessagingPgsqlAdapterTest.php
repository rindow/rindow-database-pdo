<?php
namespace RindowTest\Database\Pdo\Messaging\MessagingPgsqlAdapterTest;

use PHPUnit\Framework\TestCase;
use Rindow\Messaging\Service\Database\GenericQueueDriver;
use Rindow\Database\Pdo\DataSource;

class Test extends TestCase
{
    public static $skip = false;
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo_pgsql')) {
            self::$skip = 'pdo_pgsql extension not loaded';
            return;
        }
        try {
            $dsn = "pgsql:host=127.0.0.1;dbname=postgres";
            $username = "postgres";
            $password = "password";
            $options  = array();
            $client = new \PDO($dsn, $username, $password, $options);
        } catch(\Exception $e) {
            self::$skip = 'Connect failed to pgsql.';
            return;
        }
    }

    public function getConfig()
    {
        $config = array(
                'dsn' => "pgsql:host=127.0.0.1;dbname=postgres",
                'user'   => 'postgres',
                'password' => 'password',
                'AckTimeoutSecond' => 5,
        );
        return $config;
    }

    public function setUp()
    {
        if(self::$skip) {
            $this->markTestSkipped(self::$skip);
            return;
        }
        $dataSource = new DataSource($this->getConfig());
        $connection = $dataSource->getConnection();
        $queue = new GenericQueueDriver($connection);
        $queue->dropSchema();
        $queue->createSchema();
    }

    public function testNormal()
    {
        $dataSource = new DataSource($this->getConfig());
        $connection = $dataSource->getConnection();
        $queue = new GenericQueueDriver($connection);
        $queueName  = '/queue/foo';
        $queueName2  = '/queue/foo2';
        $msg  = 'bar';
        $msg2 = 'bar2';

        try {
            $queue->send($queueName, $msg);
            $queue->send($queueName2, $msg2);
            $queue->send($queueName, $msg);
            $queue->send($queueName2, $msg2);
        } catch(\Exception $e) {
            echo 'Connection failed: ' . $e->getMessage();
            return;
        }

        $queue->subscribe($queueName);
        $queue->subscribe($queueName2);

        $frames = $queue->receiveFrames(2);

        $this->assertEquals(2,count($frames));
        $this->assertEquals('bar',$frames[0]->body);
        $this->assertEquals('bar2',$frames[1]->body);

        $queue->ackFrames($frames);

        $frame = $queue->receive();
        $queue->ack($frame);
        $this->assertEquals('bar',$frame->body);
        $frame = $queue->receive();
        $queue->ack($frame);
        $this->assertEquals('bar2',$frame->body);

        $frame = $queue->receive();
        $this->assertFalse($frame);
    }

    public function testTransactionAndAck()
    {
        $config = $this->getConfig();
        $dataSource = new DataSource($this->getConfig());
        $connection = $dataSource->getConnection();
        $queue = new GenericQueueDriver($connection);
        $queueName  = '/queue/foo';
        $queueName2  = '/queue/foo2';
        $msg  = 'bar';
        $msg2 = 'bar2';

        $connection->beginTransaction();
        $queue->send($queueName, $msg);
        $queue->send($queueName2, $msg2);
        $connection->commit();

        $connection->beginTransaction();
        $queue->send($queueName, $msg);
        $connection->rollBack();

        $dataSource = new DataSource($this->getConfig());
        $connection = $dataSource->getConnection();
        $queue2 = new GenericQueueDriver($connection);
        $queue2->subscribe($queueName);
        $queue2->subscribe($queueName2);
        $frames = $queue2->receiveFrames(4);
        $this->assertEquals(2,count($frames));
        $this->assertEquals('bar',$frames[0]->body);
        $this->assertEquals('bar2',$frames[1]->body);

        unset($queue2);

        $dataSource = new DataSource($this->getConfig());
        $connection = $dataSource->getConnection();
        $queue2 = new GenericQueueDriver($connection);
        $queue2->subscribe($queueName);
        $queue2->subscribe($queueName2);
        $frames = $queue2->receiveFrames(4);
        $this->assertEquals(2,count($frames));
        $this->assertEquals('bar',$frames[0]->body);
        $this->assertEquals('bar2',$frames[1]->body);

        $queue->ackFrames($frames);
    }

}
