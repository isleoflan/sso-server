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
                                               'name'      => 'loginRequest',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 0,
                                           ],
                                           [
                                               'name'      => 'username',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 0,
                                           ],
                                           [
                                               'name'      => 'password',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 0,
                                           ],
                                       ]);

    try {
        $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest($input['loginRequest']);
    } catch (NotFoundException|InvalidValueException $e){
        APIResponse::getInstance()->addError(102002)->render();
    }


    $app = App::getCurrent();

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
    }
