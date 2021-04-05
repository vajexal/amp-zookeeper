<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Tests;

use PHPUnit\Framework\TestCase;
use Vajexal\AmpZookeeper\ConnectStringParser;

class ConnectStringParserTest extends TestCase
{
    private const DEFAULT_PORT = 2181;

    public function testSingleServerChrootPath()
    {
        $chrootPath = '/hallo/welt';
        $servers    = '10.10.10.1';
        $this->assertEquals($chrootPath, (new ConnectStringParser($servers . $chrootPath))->getChrootPath());

        $servers = '[2001:db8:1::242:ac11:2]';
        $this->assertEquals($chrootPath, (new ConnectStringParser($servers . $chrootPath))->getChrootPath());
    }

    public function testMultipleServersChrootPath()
    {
        $chrootPath = "/hallo/welt";
        $servers    = "10.10.10.1,10.10.10.2";
        $this->assertEquals($chrootPath, (new ConnectStringParser($servers . $chrootPath))->getChrootPath());

        $servers = "[2001:db8:1::242:ac11:2]:2181,[2001:db8:85a3:8d3:1319:8a2e:370:7348]:5678";
        $this->assertEquals($chrootPath, (new ConnectStringParser($servers . $chrootPath))->getChrootPath());
    }

    public function testParseServersWithoutPort()
    {
        $servers = "10.10.10.1,10.10.10.2";
        $parser  = new ConnectStringParser($servers);
        $this->assertEquals("10.10.10.1", $parser->getServerAddresses()[0]->getHost());
        $this->assertEquals(self::DEFAULT_PORT, $parser->getServerAddresses()[0]->getPort());
        $this->assertEquals("10.10.10.2", $parser->getServerAddresses()[1]->getHost());
        $this->assertEquals(self::DEFAULT_PORT, $parser->getServerAddresses()[1]->getPort());

        $servers = "[2001:db8:1::242:ac11:2],[2001:db8:85a3:8d3:1319:8a2e:370:7348]";
        $parser  = new ConnectStringParser($servers);
        $this->assertEquals("[2001:db8:1::242:ac11:2]", $parser->getServerAddresses()[0]->getHost());
        $this->assertEquals(self::DEFAULT_PORT, $parser->getServerAddresses()[0]->getPort());
        $this->assertEquals("[2001:db8:85a3:8d3:1319:8a2e:370:7348]", $parser->getServerAddresses()[1]->getHost());
        $this->assertEquals(self::DEFAULT_PORT, $parser->getServerAddresses()[1]->getPort());
    }

    public function testParseServersWithPort()
    {
        $servers = "10.10.10.1:112,10.10.10.2:110";
        $parser  = new ConnectStringParser($servers);
        $this->assertEquals("10.10.10.1", $parser->getServerAddresses()[0]->getHost());
        $this->assertEquals("10.10.10.2", $parser->getServerAddresses()[1]->getHost());
        $this->assertEquals(112, $parser->getServerAddresses()[0]->getPort());
        $this->assertEquals(110, $parser->getServerAddresses()[1]->getPort());

        $servers = "[2001:db8:1::242:ac11:2]:1234,[2001:db8:85a3:8d3:1319:8a2e:370:7348]:5678";
        $parser  = new ConnectStringParser($servers);
        $this->assertEquals("[2001:db8:1::242:ac11:2]", $parser->getServerAddresses()[0]->getHost());
        $this->assertEquals("[2001:db8:85a3:8d3:1319:8a2e:370:7348]", $parser->getServerAddresses()[1]->getHost());
        $this->assertEquals(1234, $parser->getServerAddresses()[0]->getPort());
        $this->assertEquals(5678, $parser->getServerAddresses()[1]->getPort());
    }

    public function testParseIPV6ConnectionString()
    {
        $servers = "[127::1],127.0.10.2";
        $parser  = new ConnectStringParser($servers);
        $this->assertEquals("[127::1]", $parser->getServerAddresses()[0]->getHost());
        $this->assertEquals("127.0.10.2", $parser->getServerAddresses()[1]->getHost());
        $this->assertEquals(2181, $parser->getServerAddresses()[0]->getPort());
        $this->assertEquals(2181, $parser->getServerAddresses()[1]->getPort());

        $servers = "[127::1]:2181,[127::2]:2182,[127::3]:2183";
        $parser  = new ConnectStringParser($servers);
        $this->assertEquals("[127::1]", $parser->getServerAddresses()[0]->getHost());
        $this->assertEquals("[127::2]", $parser->getServerAddresses()[1]->getHost());
        $this->assertEquals("[127::3]", $parser->getServerAddresses()[2]->getHost());
        $this->assertEquals(2181, $parser->getServerAddresses()[0]->getPort());
        $this->assertEquals(2182, $parser->getServerAddresses()[1]->getPort());
        $this->assertEquals(2183, $parser->getServerAddresses()[2]->getPort());
    }
}
