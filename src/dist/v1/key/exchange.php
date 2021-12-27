<?php
    // exchanges the intermediate key to a full JWT

    declare(strict_types=1);

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\DataType\Date;
    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Request\APIResponse;
    use IOL\SSO\v1\Request\Authentication;
    use IOL\SSO\v1\Session\GlobalSession;
    use IOL\SSO\v1\Tokens\RefreshToken;

    $response = APIResponse::getInstance();

    $response->setAllowedRequestMethods(
        new RequestMethod(RequestMethod::POST)
    );
    $response->needsAuth(false);

    $response->check();
    $input = $response->getRequestData([
                                           [
                                               'name'      => 'token',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 301001,
                                           ],
                                       ]);

    $intermediateToken = new \IOL\SSO\v1\Tokens\IntermediateToken();
    try {
        $decryptedToken = $intermediateToken->checkToken($input['token']);
    } catch (\IOL\SSO\v1\Exceptions\IOLException) {
        $response->addError(301001)->render();
    }

    try {
        $app = new App($decryptedToken['appId']);
    } catch (\IOL\SSO\v1\Exceptions\IOLException) {
        $response->addError(201001)->render();
    }

    try {
        $globalSession = new GlobalSession($decryptedToken['gsId']);
    } catch (\IOL\SSO\v1\Exceptions\IOLException) {
        $response->addError(102001)->render();
    }


    $session = new \IOL\SSO\v1\Request\Session();
    $session->create(globalSession: $globalSession, app: $app);

    $accessToken = Authentication::createNewToken($session);

    $refreshToken = new RefreshToken();
    $refreshToken = $refreshToken->createNew($session);

    $response->addData('accessToken', $accessToken);
    $response->addData('refreshToken', $refreshToken);
    $response->addData('expiration', $session->getExpiry()->format(Date::DATE_FORMAT_ISO));
