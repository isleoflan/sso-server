<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(false);
$response->isSSOFrontendOnly(false);

$user = $response->check();
$input = $response->getRequestData([
    [
        'name' => 'userId',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105101,
    ]
]);

if(!isset($input['userId'])){
    $response->needsAuth(false);
    $user = $response->check();
    $response->setData($user->serialize());

    $apiRequest = curl_init('https://api.shop.isleoflan.ch/v1/user/orderinfo?userId='.$user->getId());

    $headers = [
        'Iol-App-Token: '.APIResponse::getRequestHeader('Iol-App-Token'),
        'Content-Type: application/json',
        'Authorization: '.APIResponse::getRequestHeader('Authorization'),
    ];

    curl_setopt($apiRequest, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($apiRequest, CURLOPT_RETURNTRANSFER, 1);
    $shopResponse = curl_exec($apiRequest);
    $responseCode = curl_getinfo($apiRequest, CURLINFO_HTTP_CODE);

    $hasOrder = false;
    if($responseCode === 200){
        $shopResponse = json_decode($shopResponse, true);

        if(!isset($shopResponse['error'])) {
            $hasOrder = $shopResponse['data']['hasOrder'];
        }
    }

    $response->addData('hasOrder', $hasOrder);
} else {
    try {
        $user = new \IOL\SSO\v1\Entity\User($input['userId']);

        $response->setData($user->serialize(true));
    } catch (\IOL\SSO\v1\Exceptions\IOLException) {
        APIResponse::getInstance()->addError(901004)->render();
    }
}