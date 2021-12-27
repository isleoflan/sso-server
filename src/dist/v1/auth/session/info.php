<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(false);
$response->isSsoFrontendOnly(true);


$response->check();
$input = $response->getRequestData([
    [
        'name' => 'globalSessionId',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 102001,
    ],
]);

try {
    $globalSession = new \IOL\SSO\v1\Session\GlobalSession($input['globalSessionId']);
} catch (\IOL\SSO\v1\Exceptions\IOLException $e) {
    $response->addError(102001)->render();
}
if (!$globalSession->isValid()) {
    APIResponse::getInstance()->addError(102002)->render();
}

$response->setData($globalSession->getInfo());
