<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Tokens\IntermediateToken;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::POST)
);
$response->needsAuth(false);
$response->isSSOFrontendOnly(true);

$response->check();
$input = $response->getRequestData([
    [
        'name' => 'doi',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105201,
    ],
]);

$user = new User();

try {
    $user->fetchByConfirmationHash($input['doi']);
} catch (NotFoundException) {
    $response->addError(105201)->render();
}

$loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest();
try {
    $loginRequest->loadAllocation($user);
} catch (\IOL\SSO\v1\Exceptions\IOLException) {
    $response->addError(105202)->render();
}
$redirectURL = $loginRequest->redeem();

$user->activate();


$intermediateToken = new IntermediateToken();
try {
    $token = $intermediateToken->createNew(
        app: $loginRequest->getApp(),
        user: $user
    );
} catch (\IOL\SSO\v1\Exceptions\EncryptionException $e) {
    APIResponse::getInstance()->addData('err', $e->getMessage());
    APIResponse::getInstance()->addError(999104)->render();
}

$response->addData('redirect', $redirectURL . $token);
$response->addData('globalSessionId', $user->getGlobalSession()->getId());