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
        'name' => 'loginRequestId',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 101002,
    ],
]);

try {
    $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest($input['loginRequestId']);
} catch (\IOL\SSO\v1\Exceptions\IOLException $e) {
    $response->addError(101002)->render();
}

$response->setData($loginRequest->getInfo());
