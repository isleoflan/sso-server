<?php

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\DataType\Email;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\IOLException;
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
        'name' => 'email',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105110,
    ],
]);


$user = new User();

// validate the given email address and add an error to the response, if it is invalid
try {
    $email = new Email($input['email']);

    if ($user->emailIsTaken($email)) {
        APIResponse::getInstance()->addError(105001);
    }
} catch (IOLException) {
    APIResponse::getInstance()->addError(105110);
}
