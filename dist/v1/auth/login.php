<?php
    // logs in the user and returns an intermediate key

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Entity\User;
    use IOL\SSO\v1\Exceptions\InvalidValueException;
    use IOL\SSO\v1\Exceptions\NotFoundException;
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
                                               'name'      => 'loginRequestId',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 102002,
                                           ],
                                           [
                                               'name'      => 'username',
                                               'types'     => ['string'],
                                               'required'  => false,
                                               'errorCode' => 0,
                                           ],
                                           [
                                               'name'      => 'password',
                                               'types'     => ['string'],
                                               'required'  => false,
                                               'errorCode' => 0,
                                           ],
                                       ]);

    try {
        $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest($input['loginRequestId']);
    } catch (NotFoundException|InvalidValueException $e){
        APIResponse::getInstance()->addError(102002)->render();
    }


    $app = $loginRequest->getApp();

    try {
        $user = new User(username: $input['username']);
    } catch (NotFoundException|InvalidValueException $e){
        APIResponse::getInstance()->addError(100472)->render();
    }

    if ($user->login(password: $input['password'])) {
        $intermediateToken = new IntermediateToken();

        try {
            $token = $intermediateToken->createNew(
                app:  $app,
                user: $user
            );
        } catch (\IOL\SSO\v1\Exceptions\EncryptionException $e) {
            APIResponse::getInstance()->addError(999104)->render();
        }


        $redirectURL = $loginRequest->redeem();
        APIResponse::getInstance()->addData('redirect', $redirectURL . $token);
        APIResponse::getInstance()->addData('globalSessionId',$user->getGlobalSession()->getId());
    } else {
        APIResponse::getInstance()->addError(100472)->render();
    }
