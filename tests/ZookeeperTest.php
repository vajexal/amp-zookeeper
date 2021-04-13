<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Tests;

use Amp\Delayed;
use Amp\PHPUnit\AsyncTestCase;
use Vajexal\AmpZookeeper\CreateMode;
use Vajexal\AmpZookeeper\Data\Stat;
use Vajexal\AmpZookeeper\EventType;
use Vajexal\AmpZookeeper\Exception\KeeperException;
use Vajexal\AmpZookeeper\KeeperState;
use Vajexal\AmpZookeeper\Proto\WatcherEvent;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConnector;

class ZookeeperTest extends AsyncTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setTimeout(5000);
    }

    public function testZookeeper()
    {
        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            if (yield $zk->exists('/foo')) {
                yield $zk->delete('/foo');
            }

            yield $zk->create('/foo', 'bar');
            $this->assertEquals('bar', yield $zk->get('/foo'));

            /** @var Stat $stat */
            $stat = yield $zk->stat('/foo');
            $this->assertEquals(3, $stat->getDataLength());

            yield $zk->set('/foo', 'baz');
            $this->assertEquals('baz', yield $zk->get('/foo'));

            $this->assertContains('foo', yield $zk->getChildren('/'));

            $this->assertTrue(yield $zk->exists('/foo'));
            yield $zk->delete('/foo');
            $this->assertFalse(yield $zk->exists('/foo'));
        } finally {
            yield $zk->close();
        }
    }

    public function testSetEmptyNode()
    {
        $this->expectExceptionObject(new KeeperException('NoNode', KeeperException::NO_NODE));

        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            yield $zk->set('/foo', 'bar');
        } finally {
            yield $zk->close();
        }
    }

    public function testCreateWhenNodeExists()
    {
        $this->expectExceptionObject(new KeeperException('NodeExists', KeeperException::NODE_EXISTS));

        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            yield $zk->create('/foo', 'bar');
            yield $zk->create('/foo', 'bar');
        } finally {
            yield $zk->delete('/foo');
            yield $zk->close();
        }
    }

    public function testPing()
    {
        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)
            ->sessionTimeout(1000)
            ->connect();

        try {
            $this->assertEquals(1000, $zk->getSessionTimeout());

            yield new Delayed(2000);

            yield $zk->sync('/foo');
        } finally {
            yield $zk->close();
        }
    }

    public function testWatches()
    {
        $watcher = $this->createCallback(1, function (WatcherEvent $event) {
            $this->assertEquals(EventType::NODE_DELETED, $event->getType());
            $this->assertEquals(KeeperState::SYNC_CONNECTED, $event->getState());
            $this->assertEquals('/foo', $event->getPath());
        });

        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)
            ->watcher($watcher)
            ->connect();

        try {
            yield $zk->create('/foo', 'bar');
            yield $zk->get('/foo', true);
            yield $zk->delete('/foo');
        } finally {
            yield $zk->close();
        }
    }

    public function testRemoveWatches()
    {
        $watcher = $this->createCallback(0);

        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)
            ->watcher($watcher)
            ->connect();

        try {
            yield $zk->create('/foo', 'bar');
            yield $zk->get('/foo', true);
            yield $zk->removeWatches('/foo');
            yield $zk->delete('/foo');
        } finally {
            yield $zk->close();
        }
    }

    public function testPersistentWatches()
    {
        $watcher = $this->createCallback(2, function (WatcherEvent $event) {
            $this->assertEquals('/foo', $event->getPath());
        });

        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)
            ->watcher($watcher)
            ->connect();

        try {
            yield $zk->create('/foo', 'bar');
            yield $zk->addWatch('/foo');
            yield $zk->set('/foo', 'baz');
            yield $zk->delete('/foo');
        } finally {
            yield $zk->close();
        }
    }

    public function testEphemeralNode()
    {
        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            yield $zk->create('/foo', 'bar', CreateMode::EPHEMERAL);

            $this->assertEquals(['/foo'], yield $zk->getEphemerals());
        } finally {
            yield $zk->close();
        }

        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            $this->assertFalse(yield $zk->exists('/foo'));
        } finally {
            $zk->close();
        }
    }

    public function testSequentialNode()
    {
        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            yield $zk->create('/foo', 'bar', CreateMode::EPHEMERAL_SEQUENTIAL);

            $nodes = yield $zk->getChildren('/');
            $nodes = \array_filter($nodes, fn (string $node) => \preg_match('/^foo\d{10}$/', $node));
            $this->assertNotEmpty($nodes);
        } finally {
            yield $zk->close();
        }
    }

    public function testChrootPath()
    {
        /** @var Zookeeper $chrootedZk */
        $chrootedZk = $zk = yield (new ZookeeperConnector)
            ->connectString('127.0.0.1/foo')
            ->connect();
        /** @var Zookeeper $zk */
        $zk = yield (new ZookeeperConnector)->connect();

        try {
            yield $zk->create('/foo', 'bar');
            yield $chrootedZk->create('/bar', 'baz', CreateMode::EPHEMERAL);

            $this->assertEquals(['bar'], yield $zk->getChildren('/foo'));

            yield $chrootedZk->close();
            yield $zk->delete('/foo');
        } finally {
            yield $zk->close();
        }
    }
}
