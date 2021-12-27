<?php

    declare(strict_types=1);

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\DataType\Date;
    use IOL\SSO\v1\Exceptions\IOLException;
    use IOL\SSO\v1\Request\APIResponse;
    use IOL\SSO\v1\Request\Authentication;
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
                                               'errorCode' => 401001,
                                           ],
                                       ]);

    try {
        $refreshToken = new RefreshToken($input['token']);
    } catch (IOLException $e) {
        $response->addError(401001)->render();
    }

    $session = $refreshToken->getSession();

    $globalSession = $session->getGlobalSession();

    if (!$globalSession->isValid()) {
        $response->addError(102002)->render();
    }

    $session->renew();
    $accessToken = Authentication::createNewToken($session);

    $response->addData('accessToken', $accessToken);
    $response->addData('refreshToken', $input['token']);
    $response->addData('expiration', $session->getExpiry()->format(Date::DATE_FORMAT_ISO));
