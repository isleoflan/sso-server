<?php

declare(strict_types=1);

namespace IOL\SSO\v1\DataSource;

use JetBrains\PhpStorm\NoReturn;

class File
{
    private /*readonly*/ string $basePath;
    private static ?File $instance = null;

    #[NoReturn] protected function __construct()
    {
        $basePath = __DIR__;
        for ($returnDirs = 0; $returnDirs < 5; $returnDirs++) {
            $basePath = substr($basePath, 0, strrpos($basePath, '/'));
        }
        $this->basePath = $basePath;
    }

    protected function __clone()
    {
    }

    public static function getInstance(): File
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getBasePath(): string
    {
        $file = self::getInstance();

        return $file->basePath;
    }
}
