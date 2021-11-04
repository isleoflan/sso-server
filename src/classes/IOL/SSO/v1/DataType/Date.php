<?php

declare(strict_types=1);

namespace IOL\SSO\v1\DataType;

use DateTime;
use DateTimeZone;
use Exception;
use IOL\SSO\v1\Request\APIResponse;

class Date extends DateTime
{
    public const DATETIME_FORMAT_MICRO = 'Y-m-d H:i:s.u';
    public const DATETIME_FORMAT_STD = 'Y-m-d H:i:s';
    public const DATETIME_FORMAT_SQL = 'Y-m-d H:i:s';
    public const DATE_FORMAT_STD = 'Y-m-d';
    public const DATE_FORMAT_HUMAN = 'd.m.Y';
    public const DATE_FORMAT_HUMAN_LONG = 'd.m.Y H:i:s';
    public const DATE_FORMAT_ISO = 'c';

    public const TIMEZONE_SWISS = 'Europe/Zurich';

    public function __construct(?string $time = null)
    {
        if ($time === 'u') {
            try {
                $date = parent::createFromFormat('0.u00 U', microtime());
                $date->setTimezone(new DateTimeZone(self::TIMEZONE_SWISS));
                parent::__construct(
                    $date->format(self::DATETIME_FORMAT_MICRO),
                    new DateTimeZone(self::TIMEZONE_SWISS)
                );
            } catch (Exception) {
                APIResponse::getInstance()->addError(999901)->render();
            }
        } else {
            try {
                $timezone = new DateTimeZone(self::TIMEZONE_SWISS);
                parent::__construct($time, $timezone);
            } catch (Exception) {
                APIResponse::getInstance()->addError(999901)->render();
            }
        }

        return $this;
    }

    public static function now($format): string
    {
        try {
            $now = new Date('u');

            return $now->format($format);
        } catch (Exception) {
            return date($format);
        }
    }

    public function micro(): string
    {
        return $this->format(self::DATETIME_FORMAT_MICRO);
    }

    public function sqldate(): string
    {
        return $this->format(self::DATE_FORMAT_STD);
    }

    public function sqldatetime(): string
    {
        return $this->format(self::DATETIME_FORMAT_STD);
    }

    public function human(): string
    {
        return $this->format(self::DATE_FORMAT_HUMAN);
    }

    public function iso(): string
    {
        return $this->format(self::DATE_FORMAT_ISO);
    }
}
