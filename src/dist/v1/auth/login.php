<?php
// logs in the user and returns an intermediate key

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\InvalidValueException;
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
        'name' => 'loginRequestId',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 101002,
    ],
    [
        'name' => 'globalSessionId',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 102001,
    ],
    [
        'name' => 'username',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 103001,
    ],
    [
        'name' => 'password',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 103002,
    ],
]);

try {
    $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest($input['loginRequestId']);
} catch (NotFoundException | InvalidValueException $e) {
    APIResponse::getInstance()->addError(101002)->render();
}


$app = $loginRequest->getApp();


$addGlobalSessionToResponse = false;

if (isset($input['username']) && $input['username'] !== '' && isset($input['password']) && $input['password'] !== '') {
    try {
        $user = new User();
        $user->loadByUsernameOrEmail($input['username']);
    } catch (NotFoundException | InvalidValueException $e) {
        APIResponse::getInstance()->addError(901001)->render();
    }
    if ($user->login(password: $input['password'])) {
        $addGlobalSessionToResponse = true;
    } else {
        APIResponse::getInstance()->addError(901001)->render();
    }
} elseif (isset($input['globalSessionId'])) {
    try {
        $globalSession = new \IOL\SSO\v1\Session\GlobalSession($input['globalSessionId']);
    } catch (InvalidValueException $e) {
        APIResponse::getInstance()->addError(102001)->render();
    }
    if (!$globalSession->isValid()) {
        APIResponse::getInstance()->addError(102002)->render();
    }

    $user = $globalSession->getUser();
    $user->setGlobalSession($globalSession);
} else {
    APIResponse::getInstance()->addError(103003)->render();
}


$intermediateToken = new IntermediateToken();

try {
    $token = $intermediateToken->createNew(
        app: $app,
        globalSession: $user->getGlobalSession()
    );
} catch (\IOL\SSO\v1\Exceptions\EncryptionException $e) {
    APIResponse::getInstance()->addData('err', $e->getMessage());
    APIResponse::getInstance()->addError(999104)->render();
}

$redirectURL = $loginRequest->redeem();


APIResponse::getInstance()->addData('redirect', $redirectURL . $token);

if ($addGlobalSessionToResponse) {
    APIResponse::getInstance()->addData('globalSessionId', $user->getGlobalSession()->getId());
}
