<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\Reset;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\IOLException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Tokens\LoginRequest;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::POST)
);
$response->needsAuth(false);
$response->isSSOFrontendOnly(true);

$response->check();
$input = $response->getRequestData([
    [
        'name' => 'username',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105101,
    ],
    [
        'name' => 'loginRequestId',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 101002,
    ],
]);

try {
    $loginRequest = new LoginRequest($input['loginRequestId']);
} catch (NotFoundException | InvalidValueException $e) {
    APIResponse::getInstance()->addError(101002)->render();
}

try {
    $user = new User();
    $user->loadByUsernameOrEmail($input['username']);
} catch (IOLException) {
    $response->render();
}

$reset = new Reset();
$reset->createNew($user, $loginRequest);
