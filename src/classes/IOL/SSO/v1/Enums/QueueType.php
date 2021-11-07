<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Enums;

class QueueType extends Enum
{
    public const ALL_USER = 'iol.sso.user.*';
    public const NEW_USER = 'iol.sso.user.new';
}