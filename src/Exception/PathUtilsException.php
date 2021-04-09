<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Exception;

class PathUtilsException extends ZookeeperException
{
    public const EMPTY_PATH_CODE                   = 0;
    public const PATH_MUST_START_WITH_SLASH_CODE   = 1;
    public const PATH_MUST_NOT_END_WITH_SLASH_CODE = 2;
    public const INVALID_CHAR_IN_PATH_CODE         = 3;
    public const INVALID_PATH_CODE                 = 4;

    public static function emptyPath(): self
    {
        return new self('Path length must be > 0', self::EMPTY_PATH_CODE);
    }

    public static function pathMustStartWithSlash(): self
    {
        return new self('Path must start with / character', self::PATH_MUST_START_WITH_SLASH_CODE);
    }

    public static function pathMustNotEndWithSlash(): self
    {
        return new self('Path must not end with / character', self::PATH_MUST_NOT_END_WITH_SLASH_CODE);
    }

    public static function invalidCharInPath(): self
    {
        return new self('Invalid characters in path', self::INVALID_CHAR_IN_PATH_CODE);
    }

    public static function invalidPath(string $path, string $reason): self
    {
        return new self(\sprintf('Invalid path string "%s" caused by %s', $path, $reason), self::INVALID_PATH_CODE);
    }
}
