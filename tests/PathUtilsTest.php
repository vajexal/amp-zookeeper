<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Tests;

use PHPUnit\Framework\TestCase;
use Vajexal\AmpZookeeper\Exception\PathUtilsException;
use Vajexal\AmpZookeeper\PathUtils;

class PathUtilsTest extends TestCase
{
    public function validPaths()
    {
        return [
            ['/this is / a valid/path'],
            ['/name/with.period.'],
            ["/test\u{0020}"],
            ["/test\u{007e}"],
            ["/test\u{ffef}"],
        ];
    }

    public function invalidPaths()
    {
        return [
            [''],
            ['not/valid'],
            ['/ends/with/slash/'],
            ["/test\u{0000}"],
            ['/double//slash'],
            ['/single/./period'],
            ['/double/../period'],
            ["/test\u{0001}"],
            ["/test\u{001F}"],
            ["/test\u{007f}"],
            ["/test\u{009f}"],
            ["/test\u{d800}"],
            ["/test\u{f8ff}"],
            ["/test\u{fff0}"],
        ];
    }

    /**
     * @dataProvider validPaths
     */
    public function testValidPath(string $path)
    {
        $this->expectNotToPerformAssertions();

        PathUtils::validatePath($path);
    }

    /**
     * @dataProvider invalidPaths
     */
    public function testInvalidPath(string $path)
    {
        $this->expectException(PathUtilsException::class);

        PathUtils::validatePath($path);
    }
}
