<?php

    declare(strict_types=1);

    use IOL\SSO\v1\Request\APIResponse;

    $basePath = __DIR__;
    foreach (['/vendor/autoload.php'] as $file)
    {
        require_once $basePath.$file;
    }

    spl_autoload_register(static function ($className): void
    {
        $basePath = __DIR__;
        $className = str_replace('\\', '/', $className);
        $filePath = false;

        if (str_contains($className, 'IOL\SSO')) {
            $filePath = $basePath.'/classes/'.$className.'.php';
        }
        if ($filePath && file_exists($filePath)) {
            include_once $filePath;
        } else {
            die('Dependency not found. ('.$filePath.' / '.$className.')');
        }
    });

    register_shutdown_function(static function (): void
    {
        APIResponse::getInstance()->render();
    });
