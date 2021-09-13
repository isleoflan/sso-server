<?php

declare(strict_types=1);

namespace IOL\SSO\v1\ObjectTemplates;

class UserTemplate extends ObjectTemplate
{
    protected array $template = [
        'id' => 'string',
        'username' => 'string',
        'password' => 'string',
        'activated' => 'string',
        'blocked' => 'string',
    ];
}