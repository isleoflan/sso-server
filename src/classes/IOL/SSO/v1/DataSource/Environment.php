<?php

namespace IOL\SSO\v1\DataSource;

use Dotenv\Dotenv;
use JetBrains\PhpStorm\NoReturn;

class Environment
{
    private static ?Environment $instance = null;

    #[NoReturn] protected function __construct()
    {
        $dotenv = Dotenv::createImmutable(File::getBasePath());
        $dotenv->load();
    }

    protected function __clone()
    {
    }

    public static function getInstance(): Environment
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get(string $key): string|int|null|bool
    {
        Environment::getInstance();
        return $_ENV[$key] ?? null;
    }
}
