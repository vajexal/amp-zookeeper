<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use InvalidArgumentException;

class PathUtils
{
    public static function validatePath(string $path): void
    {
        if (!$path) {
            throw new InvalidArgumentException('Path length must be > 0');
        }

        if (\mb_substr($path, 0, 1) !== '/') {
            throw new InvalidArgumentException('Path must start with / character');
        }

        if (\mb_strlen($path) === 1) {
            return;
        }

        if (\mb_substr($path, -1) === '/') {
            throw new InvalidArgumentException('Path must not end with / character');
        }

        $chars = \preg_split('//u', $path, -1, PREG_SPLIT_NO_EMPTY);

        if (!$chars) {
            throw new InvalidArgumentException('Invalid characters in path');
        }

        $reason = null;

        for ($i = 1, $lastc = '/'; $i < \count($chars); $lastc = $chars[$i], $i++) {
            $c = $chars[$i];

            if ($c === "\0") {
                $reason = \sprintf('null character not allowed @%d', $i);
                break;
            } elseif ($c === '/' && $lastc === '/') {
                $reason = \sprintf('empty node name specified @%d', $i);
                break;
            } elseif ($c === '.' && $lastc === '.') {
                if ($chars[$i - 2] === '/' && (($i + 1 === \count($chars)) || $chars[$i + 1] === '/')) {
                    $reason = \sprintf('relative paths not allowed @%d', $i);
                    break;
                }
            } elseif ($c === '.') {
                if ($chars[$i - 1] === '/' && (($i + 1 === \count($chars)) || $chars[$i + 1] === '/')) {
                    $reason = \sprintf('relative paths not allowed @%d', $i);
                    break;
                }
            } elseif (
                $c > "\u{0000}" && $c <= "\u{001f}"
                || $c >= "\u{007f}" && $c <= "\u{009F}"
                || $c >= "\u{d800}" && $c <= "\u{f8ff}"
                || $c >= "\u{fff0}" && $c <= "\u{ffff}"
            ) {
                $reason = \sprintf('invalid character @%d', $i);
                break;
            }
        }

        if ($reason !== null) {
            throw new InvalidArgumentException(\sprintf('Invalid path string "%s" caused by %s', $path, $reason));
        }
    }
}
