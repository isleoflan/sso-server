<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::POST)
);
$response->needsAuth(false);

$response->check();
$input = $response->getRequestData([
    [
        'name' => 'redirectURL',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 101001,
    ],

]);

$app = App::getCurrent();
if (!$app->checkRedirectURL($input['redirectURL'])) {
    $response->addError(101001)->render();
}


$loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest();
$requestId = $loginRequest->createNew(
    app: $app,
    redirectURL: $input['redirectURL'],
);

$response->addData('redirect', $_ENV['FRONTEND_BASE_URL'] . 'request/' . $requestId);
