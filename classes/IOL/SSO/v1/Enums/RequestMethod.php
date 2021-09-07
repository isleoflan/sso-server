<?php

    declare(strict_types=1);

    namespace IOL\SSO\v1\Enums;

    class RequestMethod extends BitMask
    {
        public const GET        = 0b000000001;
        public const POST       = 0b000000010;
        public const DELETE     = 0b000000100;
        public const PUT        = 0b000001000;
        public const PATCH      = 0b000010000;
        public const OPTIONS    = 0b000100000;

        /* mostly unused methods */
        public const HEAD       = 0b001000000;
        public const CONNECT    = 0b010000000;
        public const TRACE      = 0b100000000;
    }
