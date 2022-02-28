<?php

declare(strict_types=1);

use IOL\SSO\v1\Request\APIResponse;

$basePath = __DIR__;
for ($returnDirs = 0; $returnDirs < 2; $returnDirs++) {
    $basePath = substr($basePath, 0, strrpos($basePath, '/'));
}


require_once $basePath . '/_loader.php';

\Sentry\init(['dsn' => \IOL\SSO\v1\DataSource\Environment::get('SENTRY_URL') ]);

$requestedFile = $_SERVER['REQUEST_URI'];
$requestedFile = str_contains($requestedFile, '?') ? substr(
    $requestedFile,
    0,
    strpos($requestedFile, '?')
) : $requestedFile;

$endpointUrl = $basePath . '/dist' . $requestedFile . '.php';

if (file_exists($endpointUrl)) {
    $env = \IOL\SSO\v1\DataSource\Environment::getInstance();
    require_once $endpointUrl;
} else {
    $response = APIResponse::getInstance();
    $response->addError(999999)->render();
}