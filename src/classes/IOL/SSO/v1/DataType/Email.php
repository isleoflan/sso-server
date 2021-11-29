<?php

declare(strict_types=1);

namespace IOL\SSO\v1\DataType;

use IOL\SSO\v1\Exceptions\InvalidValueException;
use JetBrains\PhpStorm\Pure;

class Email implements \JsonSerializable
{
    /**
     * @throws InvalidValueException
     */
    public function __construct(private string $email)
    {
        if (!$this->isValid()) {
            throw new InvalidValueException('Email is not valid', 5491);
        }
    }

    #[Pure]
    public function __toString(): string
    {
        return $this->getEmail();
    }

    #[Pure]
    public function isValid(): bool
    {
        return (bool)filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL);
    }

    public static function isValidStat(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    #[Pure]
    public function jsonSerialize(): string
    {
        return $this->getEmail();
    }
}