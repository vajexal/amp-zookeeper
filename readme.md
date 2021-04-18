[Zookeeper](https://zookeeper.apache.org) client for [amphp](https://amphp.org)

[![Build Status](https://github.com/vajexal/amp-zookeeper/workflows/Build/badge.svg)](https://github.com/vajexal/amp-zookeeper/actions)

### Installation

```bash
composer require vajexal/amp-zookeeper
```

### Usage

```php
<?php

declare(strict_types=1);

use Amp\Loop;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConnector;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield (new ZookeeperConnector)->connect();

    yield $zk->create('/foo', 'bar');
    var_dump(yield $zk->get('/foo'));

    yield $zk->set('/foo', 'baz');
    var_dump(yield $zk->get('/foo'));
    var_dump(yield $zk->getChildren('/'));

    yield $zk->delete('/foo');
    var_dump(yield $zk->exists('/foo'));

    yield $zk->close();
});
```

#### Watches

```php
<?php

declare(strict_types=1);

use Amp\Loop;
use Vajexal\AmpZookeeper\Proto\WatcherEvent;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConnector;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield (new ZookeeperConnector)
        ->watcher(function (WatcherEvent $event) {
            var_dump($event);
        })
        ->connect();

    yield $zk->create('/foo', 'bar');
    yield $zk->get('/foo', true);
    yield $zk->delete('/foo');

    yield $zk->close();
});
```

[Persistent watch](https://zookeeper.apache.org/doc/r3.7.0/zookeeperProgrammers.html#sc_WatchPersistentRecursive) can be added using `addWatch` method

#### Ephemeral Nodes

```php
<?php

declare(strict_types=1);

use Amp\Loop;
use Vajexal\AmpZookeeper\CreateMode;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConnector;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield (new ZookeeperConnector)->connect();

    yield $zk->create('/foo', 'bar', CreateMode::EPHEMERAL);
    var_dump(yield $zk->getEphemerals());
    yield $zk->close();

    /** @var Zookeeper $zk */
    $zk = yield (new ZookeeperConnector)->connect();

    var_dump(yield $zk->exists('/foo'));
    $zk->close();
});
```

#### Sequential Nodes

```php
<?php

declare(strict_types=1);

use Amp\Loop;
use Vajexal\AmpZookeeper\CreateMode;
use Vajexal\AmpZookeeper\Zookeeper;
use Vajexal\AmpZookeeper\ZookeeperConnector;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield (new ZookeeperConnector)->connect();

    yield $zk->create('/foo', 'bar', CreateMode::EPHEMERAL_SEQUENTIAL);

    var_dump(yield $zk->getChildren('/'));

    yield $zk->close();
});
```
