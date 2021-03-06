<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Tokens\IntermediateToken;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::PATCH)
);
$response->needsAuth(false);
$response->isSSOFrontendOnly(true);

$response->check();
$input = $response->getRequestData([
    [
        'name' => 'hash',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105201,
    ],
]);

$user = new User();

try {
    $user->fetchByConfirmationHash($input['hash']);
} catch (NotFoundException) {
    $response->addError(105201)->render();
}
    $globalSession = new \IOL\SSO\v1\Session\GlobalSession();
    $globalSession->createNew($user);
    $user->setGlobalSession($globalSession);

$loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest();
try {
    $loginRequest->loadAllocation($user); // TODO: more sane shit
} catch (\IOL\SSO\v1\Exceptions\IOLException) {
    $response->addError(105202)->render();
}
$redirectURL = $loginRequest->redeem();

$user->activate();


$intermediateToken = new IntermediateToken();
try {
    $token = $intermediateToken->createNew(
        app: $loginRequest->getApp(),
        globalSession: $user->getGlobalSession()
    );
} catch (\IOL\SSO\v1\Exceptions\EncryptionException $e) {
    APIResponse::getInstance()->addData('err', $e->getMessage());
    APIResponse::getInstance()->addError(999104)->render();
}

$response->addData('redirect', $redirectURL . $token);
$response->addData('globalSessionId', $user->getGlobalSession()->getId());
