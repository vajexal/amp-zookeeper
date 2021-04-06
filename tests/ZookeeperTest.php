<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Tests;

use Amp\Delayed;
use Amp\PHPUnit\AsyncTestCase;
use Vajexal\AmpZookeeper\Exception\KeeperException;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConfig;

class ZookeeperTest extends AsyncTestCase
{
    private Zookeeper $zk;

    protected function setUpAsync()
    {
        parent::setUpAsync();

        $this->setTimeout(5000);

        $this->zk = yield Zookeeper::connect();
    }

    protected function tearDownAsync()
    {
        parent::tearDownAsync();

        yield $this->zk->close();
    }

    public function testZookeeper()
    {
        if (yield $this->zk->exists('/foo')) {
            yield $this->zk->delete('/foo');
        }

        yield $this->zk->create('/foo', 'bar');
        $this->assertEquals('bar', yield $this->zk->get('/foo'));

        yield $this->zk->set('/foo', 'baz');
        $this->assertEquals('baz', yield $this->zk->get('/foo'));

        $this->assertContains('foo', yield $this->zk->getChildren('/'));

        $this->assertTrue(yield $this->zk->exists('/foo'));
        yield $this->zk->delete('/foo');
        $this->assertFalse(yield $this->zk->exists('/foo'));
    }

    public function testSetEmptyNode()
    {
        $this->expectExceptionObject(new KeeperException('NoNode', KeeperException::NO_NODE));

        yield $this->zk->set('/foo', 'bar');
    }

    public function testCreateWhenNodeExists()
    {
        $this->expectExceptionObject(new KeeperException('NodeExists', KeeperException::NODE_EXISTS));

        try {
            yield $this->zk->create('/foo', 'bar');
            yield $this->zk->create('/foo', 'bar');
        } finally {
            yield $this->zk->delete('/foo');
        }
    }

    public function testPing()
    {
        /** @var Zookeeper $zk */
        $zk = yield Zookeeper::connect(
            (new ZookeeperConfig)
                ->sessionTimeout(1000)
        );

        $this->assertEquals(1000, $zk->getSessionTimeout());

        yield new Delayed(2000);

        $this->assertFalse(yield $zk->exists('/foo'));

        yield $zk->close();
    }
}
