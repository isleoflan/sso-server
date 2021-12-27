<?php

declare(strict_types=1);

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
        'name' => 'resetId',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 501001,
    ],
]);

try {
    $reset = new Reset($input['resetId']);
} catch (IOLException) {
    $response->addError(501001)->render();
}
