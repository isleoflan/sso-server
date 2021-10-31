<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Enums;

use IOL\SSO\v1\Request\APIResponse;

class Enum implements \JsonSerializable
{
    protected string|int $value;

    public function __construct(string|int $value)
    {
        $this->value = $value;
        $reflection = new \ReflectionClass(get_called_class());

        if (!in_array($this->value, array_values($reflection->getConstants()))) {
            APIResponse::getInstance()->addError(101001)->render();
        }
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
