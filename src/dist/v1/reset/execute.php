<?php

    declare(strict_types=1);

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\Request\APIResponse;
    use IOL\SSO\v1\Tokens\IntermediateToken;

    $response = APIResponse::getInstance();

    $response->setAllowedRequestMethods(
        new RequestMethod(RequestMethod::POST)
    );
    $response->needsAuth(false);
    $response->isSSOFrontendOnly(true);

    $response->check();
    $input = $response->getRequestData([
                                           [
                                               'name'      => 'resetId',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 501001,
                                           ],
                                           [
                                               'name'      => 'password',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 105102,
                                           ],
                                       ]);

    try {
        $reset = new \IOL\SSO\v1\Entity\Reset(hash: $input['resetId']);
    } catch (\IOL\SSO\v1\Exceptions\IOLException) {
        $response->addError(501001)->render();
    }

    // TODO: check expiration

    $user = $reset->getUser();
    $user->changePassword($input['password']);

    $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest();
    try {
        $loginRequest->loadAllocation($user); // TODO: more sane
    } catch (\IOL\SSO\v1\Exceptions\IOLException) {
        $response->addError(105202)->render();
    }
    $redirectURL = $loginRequest->redeem();



    $intermediateToken = new IntermediateToken();
    try {
        $token = $intermediateToken->createNew(
            app:           $loginRequest->getApp(),
            globalSession: $user->getGlobalSession()
        );
    } catch (\IOL\SSO\v1\Exceptions\EncryptionException $e) {
        APIResponse::getInstance()->addData('err', $e->getMessage());
        APIResponse::getInstance()->addError(999104)->render();
    }

    $response->addData('redirect', $redirectURL.$token);
    $response->addData('globalSessionId', $user->getGlobalSession()->getId());
