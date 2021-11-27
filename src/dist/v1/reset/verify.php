<?php

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\Reset;
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
        'name' => 'resetID',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 106003,
    ],
]);

try {
    $reset = new Reset($input['resetID']);
} catch (IOLException) {
    $response->addError(106003)->render();
}