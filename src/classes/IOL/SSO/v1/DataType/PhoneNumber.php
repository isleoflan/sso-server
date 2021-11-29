<?php

declare(strict_types=1);

namespace IOL\SSO\v1\DataType;

use JetBrains\PhpStorm\Pure;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumber
{
    private bool $valid;
    private PhoneNumberUtil $phoneUtil;
    private ?\libphonenumber\PhoneNumber $numberProto;

    public function __construct(
        private ?string $number
    )
    {
        if (!is_null($this->number)) {
            $this->phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $this->numberProto = $this->phoneUtil->parse($number, 'CH');
            } catch (NumberParseException) {
                $this->valid = false;
            }
        }
    }

    public function __toString(): string
    {
        return $this->international() ?? '';
    }

    public function isValid(): bool
    {
        if (isset($this->valid) && !$this->valid) {
            return false;
        }
        $this->valid = $this->phoneUtil->isValidNumber($this->numberProto);

        return $this->valid;
    }

    public function international(): ?string
    {
        return isset($this->phoneUtil) ? $this->phoneUtil->format(
            $this->numberProto,
            PhoneNumberFormat::E164
        ) : null;
    }

    public function extendedSwiss(): ?string
    {
        return isset($this->phoneUtil) ? $this->phoneUtil->format(
            $this->numberProto,
            PhoneNumberFormat::INTERNATIONAL
        ) : null;
    }
}