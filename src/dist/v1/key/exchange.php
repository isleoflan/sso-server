<?php
// exchanges the intermediate key to a full JWT

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
        'name' => 'token',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 0,
    ],
]);

$app = App::getCurrent();

    
