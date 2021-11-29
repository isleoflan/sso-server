<?php

declare(strict_types=1);

namespace IOL\SSO\v1\DataType;

class Base64
{

    public static function encode(string $input): string
    {
        $output = base64_encode($input);
        return str_replace(['/', '+'], ['-', '_'], $output);
    }

    public static function decode(string $input): string
    {
        $output = str_replace(['-', '_'], ['/', '+'], $input);
        return base64_decode($output);
    }
}
