Parody for [zookeeper](https://zookeeper.apache.org) client for [amphp](https://amphp.org)

[![Build Status](https://github.com/vajexal/amp-zookeeper/workflows/Build/badge.svg)](https://github.com/vajexal/amp-zookeeper/actions)

### Installation

```bash
composer require vajexal/amp-zookeeper:dev-master
```

### Usage

```php
<?php

declare(strict_types=1);

use Amp\Loop;
use Vajexal\AmpZookeeper\Zookeeper;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield Zookeeper::connect();

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
use Vajexal\AmpZookeeper\ZookeeperConfig;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield Zookeeper::connect(
        (new ZookeeperConfig)
            ->watcher(function (WatcherEvent $event) {
                var_dump($event);
            })
    );

    yield $zk->create('/foo', 'bar');
    yield $zk->get('/foo', true);
    yield $zk->delete('/foo');

    yield $zk->close();
});
```

#### Ephemeral Nodes

```php
<?php

declare(strict_types=1);

use Amp\Loop;
use Vajexal\AmpZookeeper\CreateMode;
use Vajexal\AmpZookeeper\Zookeeper;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield Zookeeper::connect();

    yield $zk->create('/foo', 'bar', CreateMode::EPHEMERAL);
    var_dump(yield $zk->getEphemerals());
    yield $zk->close();

    /** @var Zookeeper $zk */
    $zk = yield Zookeeper::connect();

    var_dump(yield $zk->exists('/foo'));
    $zk->close();
});
```
