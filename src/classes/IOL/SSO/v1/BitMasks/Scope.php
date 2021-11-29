<?php

declare(strict_types=1);

namespace IOL\SSO\v1\BitMasks;

class Scope extends BitMask
{
    public const BASIC_USER = 0b00000001;
    public const CLERK_USER = 0b00000010;
    public const CHECKIN_USER = 0b00000100;
    public const ADMIN_USER = 0b00001000;
}
