<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use League\Uri\Uri;
use Vajexal\AmpZookeeper\Exception\ConnectStringParserException;

class ConnectStringParser
{
    private const DEFAULT_PORT = 2181;

    /**
     * @var Uri[]
     */
    private array  $serverAddresses = [];
    private string $chrootPath      = '';

    public function __construct(string $connectString)
    {
        if (!$connectString) {
            throw ConnectStringParserException::emptyConnectString();
        }

        $off = \mb_strpos($connectString, '/');

        if ($off !== false && $off >= 0) {
            $chrootPath = \mb_substr($connectString, $off);

            if (\mb_strlen($chrootPath) > 1) {
                PathUtils::validatePath($chrootPath);
                $this->chrootPath = $chrootPath;
            }

            $connectString = \mb_substr($connectString, 0, $off);
        }

        $hostsList = \mb_split(',', $connectString);

        foreach ($hostsList as $host) {
            if (\mb_strpos($host, '://') === false) {
                $host = \sprintf('tcp://%s', $host);
            }

            $uri = Uri::createFromString($host);

            if (!$uri->getPort()) {
                $uri = $uri->withPort(self::DEFAULT_PORT);
            }

            $this->serverAddresses[] = $uri;
        }

        if (!$this->serverAddresses) {
            throw ConnectStringParserException::emptyServersList();
        }
    }

    public function getChrootPath(): string
    {
        return $this->chrootPath;
    }

    public function getServerAddresses(): array
    {
        return $this->serverAddresses;
    }

    public function getRandomServer(): Uri
    {
        return $this->serverAddresses[\array_rand($this->serverAddresses)];
    }
}
