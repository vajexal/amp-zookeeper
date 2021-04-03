<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Vajexal\AmpZookeeper\Data\ACL;
use Vajexal\AmpZookeeper\Data\Id;

class Ids
{
    public static function anyoneIdUnsafe(): Id
    {
        return new Id('world', 'anyone');
    }

    public static function authIds(): Id
    {
        return new Id('auth', '');
    }

    /**
     * @return ACL[]
     */
    public static function openACLUnsafe(): array
    {
        return [new ACL(Perms::ALL, self::anyoneIdUnsafe())];
    }

    /**
     * @return ACL[]
     */
    public static function creatorAllACL(): array
    {
        return [new ACL(Perms::ALL, self::authIds())];
    }

    /**
     * @return ACL[]
     */
    public static function readACLUnsafe(): array
    {
        return [new ACL(Perms::READ, self::anyoneIdUnsafe())];
    }
}
