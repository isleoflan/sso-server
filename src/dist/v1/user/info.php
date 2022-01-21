<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(true);
$response->isSSOFrontendOnly(false);

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
$response = curl_exec($apiRequest);
$responseCode = curl_getinfo($apiRequest, CURLINFO_HTTP_CODE);

$hasOrder = false;
if($responseCode == 200){
    $response = json_decode($response, true);

    if(!isset($response['error'])) {
        $hasOrder = $response['data']['hasOrder'];
    }
}

$response->addData('hasOrder', $hasOrder);