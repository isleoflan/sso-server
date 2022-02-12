<?php

declare(strict_types=1);

use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\Email;
use IOL\SSO\v1\DataType\PhoneNumber;
use IOL\SSO\v1\Exceptions\IOLException;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::PATCH)
);
$response->needsAuth(true);
$response->isSSOFrontendOnly(false);

$user = $response->check();

$input = $response->getRequestData([
    [
        'name' => 'username',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105101,
    ],
    [
        'name' => 'password',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105102,
    ],
    [
        'name' => 'gender',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105103,
    ],
    [
        'name' => 'forename',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105104,
    ],
    [
        'name' => 'lastname',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105105,
    ],
    [
        'name' => 'address',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105106,
    ],
    [
        'name' => 'zipCode',
        'types' => ['integer'],
        'required' => true,
        'errorCode' => 105107,
    ],
    [
        'name' => 'city',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105108,
    ],
    [
        'name' => 'birthDate',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105109,
    ],
    [
        'name' => 'email',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105110,
    ],
    [
        'name' => 'phone',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105111,
    ],
    [
        'name' => 'vegetarian',
        'types' => ['boolean'],
        'required' => true,
        'errorCode' => 105112,
    ],
]);

// create a new gender object, and check, if the input value is allowed, else add an error to the response
try {
    $gender = new \IOL\SSO\v1\Enums\Gender($input['gender']);
} catch (IOLException) {
    APIResponse::getInstance()->addError(105103);
}

// validate the given email address and add an error to the response, if it is invalid
try {
    $email = new Email($input['email']);
} catch (IOLException) {
    APIResponse::getInstance()->addError(105110);
}


// validate the given phone number and add an error to the response, if it is invalid
$phone = null;
if(isset($input['phone'])) {
    try {
        $phone = new PhoneNumber($input['phone']);
    } catch (IOLException) {
        APIResponse::getInstance()->addError(105111);
    }
}

if (APIResponse::getInstance()->hasErrors()) {
    APIResponse::getInstance()->render();
}

// reassure, that the email address and the username aren't already taken, even though this already
// happens in the frontend. Better be safe than sorry.
if ($user->emailIsTaken($email)) {
    APIResponse::getInstance()->addError(105001);
}
if(isset($input['username'])) {
    if ($user->usernameIsTaken($input['username'])) {
        APIResponse::getInstance()->addError(105002);
    }
}

// check if the given birthdate is actually valid
$birthDate = null;
if(isset($input['birthDate'])) {
    try {
        $birthDate = new Date($input['birthDate']);
    } catch (Exception $e) {
        APIResponse::getInstance()->addError(105109);
    }
}

if (APIResponse::getInstance()->hasErrors()) {
    APIResponse::getInstance()->render();
}


$user->update(
    username: $input['username'] ?? null,
    gender: $gender,
    foreName: $input['forename'],
    lastName: $input['lastname'],
    address: $input['address'],
    zipCode: $input['zipCode'],
    city: $input['city'],
    birthDate: $birthDate,
    email: $email,
    phone: $phone,
    password: $input['password'] ?? null
);
