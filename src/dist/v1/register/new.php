<?php
// logs in the user and returns an intermediate key
ini_set('display_errors','On');
error_reporting(E_ALL);
use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\Email;
use IOL\SSO\v1\DataType\PhoneNumber;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\IOLException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Tokens\IntermediateToken;
use IOL\SSO\v1\Tokens\LoginRequest;


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
        'errorCode' => 102002,
    ],
    [
        'name' => 'username',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 105101,
    ],
    [
        'name' => 'password',
        'types' => ['string'],
        'required' => true,
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
        'required' => false,
        'errorCode' => 105106,
    ],
    [
        'name' => 'zipCode',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105107,
    ],
    [
        'name' => 'city',
        'types' => ['string'],
        'required' => false,
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
        'required' => false,
        'errorCode' => 105110,
    ],
    [
        'name' => 'phone',
        'types' => ['string'],
        'required' => false,
        'errorCode' => 105111,
    ],
]);


// validate, that the given Login Request actually exists
try {
    $loginRequest = new LoginRequest($input['loginRequestId']);
} catch (NotFoundException | InvalidValueException $e) {
    APIResponse::getInstance()->addError(102002)->render();
}


$app = $loginRequest->getApp();
$user = new User();

// create a new gender object, and check, if the input value is allowed, else add an error to the response
try {
    $gender = new \IOL\SSO\v1\Enums\Gender($input['gender']);
} catch(IOLException){
    APIResponse::getInstance()->addError(105103);
}

// validate the given email address and add an error to the response, if it is invalid
try {
    $email = new Email($input['email']);
} catch (IOLException){
    APIResponse::getInstance()->addError(105110);
}



// validate the given phone number and add an error to the response, if it is invalid
try {
    $phone = new PhoneNumber($input['phone']);
} catch (IOLException){
    APIResponse::getInstance()->addError(105111);
}


// reassure, that the email address and the username aren't already taken, even though this already
// happens in the frontend. Better be safe than sorry.
if($user->emailIsTaken($email)){
    APIResponse::getInstance()->addError(105001);
}
if($user->usernameIsTaken($input['username'])){
    APIResponse::getInstance()->addError(105002);
}

// check if the given birthdate is actually valid
try {
    $birthDate = new Date($input['birthDate']);
} catch (Exception $e) {
    APIResponse::getInstance()->addError(105003);
}

if(APIResponse::getInstance()->hasErrors()){
    APIResponse::getInstance()->render();
}



$user->createNew(
    username: $input['username'],
    password: $input['password'],
    gender: $gender,
    foreName: $input['forename'],
    lastName: $input['lastname'],
    address: $input['address'],
    zipCode: $input['zipCode'],
    city: $input['city'],
    birthDate: $birthDate,
    email: $email,
    phone: $phone
);