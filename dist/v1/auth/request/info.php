 <?php
use IOL\SSO\v1\BitMasks\RequestMethod;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(false);

$response->check();
$input = $response->getRequestData([
    [
        'name'      => 'requestID',
        'types'     => ['string'],
        'required'  => false,
        'errorCode' => 102002,
    ],
]);

try {
    $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest($input['requestID']);
} catch (\IOL\SSO\v1\Exceptions\IOLException $e) {
    $response->addError(102002)->render();
}

$response->setData($loginRequest->getInfo());
