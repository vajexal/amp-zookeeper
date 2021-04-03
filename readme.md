Parody for [zookeeper](https://zookeeper.apache.org) client for [amphp](https://amphp.org)

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
use Vajexal\AmpZookeeper\ZookeeperConfig;

require_once 'vendor/autoload.php';

Loop::run(function () {
    /** @var Zookeeper $zk */
    $zk = yield Zookeeper::connect(new ZookeeperConfig);

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
