<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
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
]);

$user = new User();
if ($user->usernameIsTaken($input['username'])) {
    APIResponse::getInstance()->addError(105002);
}
