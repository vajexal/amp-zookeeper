<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface OpCode
{
    public const NOTIFICATION            = 0;
    public const CREATE                  = 1;
    public const DELETE                  = 2;
    public const EXISTS                  = 3;
    public const GET_DATA                = 4;
    public const SET_DATA                = 5;
    public const GET_ACL                 = 6;
    public const SET_ACL                 = 7;
    public const GET_CHILDREN            = 8;
    public const SYNC                    = 9;
    public const PING                    = 11;
    public const GET_CHILDREN_2          = 12;
    public const CHECK                   = 13;
    public const MULTI                   = 14;
    public const CREATE_2                = 15;
    public const RECONFIG                = 16;
    public const CHECK_WATCHES           = 17;
    public const REMOVE_WATCHES          = 18;
    public const CREATE_CONTAINER        = 19;
    public const DELETE_CONTAINER        = 20;
    public const CREATE_TTL              = 21;
    public const MULTI_READ              = 22;
    public const AUTH                    = 100;
    public const SET_WATCHES             = 101;
    public const SASL                    = 102;
    public const GET_EPHEMERALS          = 103;
    public const GET_ALL_CHILDREN_NUMBER = 104;
    public const SET_WATCHES_2           = 105;
    public const ADD_WATCH               = 106;
    public const WHO_AM_I                = 107;
    public const CREATE_SESSION          = -10;
    public const CLOSE_SESSION           = -11;
    public const ERROR                   = -1;
}
