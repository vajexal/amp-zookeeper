<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Tests;

use Amp\PHPUnit\AsyncTestCase;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConfig;

class ZookeeperTest extends AsyncTestCase
{
    public function testZookeeper()
    {
        $this->setTimeout(5000);

        /** @var Zookeeper $zk */
        $zk = yield Zookeeper::connect(new ZookeeperConfig);

        try {
            if (yield $zk->exists('/foo')) {
                yield $zk->delete('/foo');
            }

            yield $zk->create('/foo', 'bar');
            $this->assertEquals('bar', yield $zk->get('/foo'));

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
}
