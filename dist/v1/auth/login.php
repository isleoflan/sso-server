<?php
    // logs in the user and returns an intermediate key

    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Entity\User;
    use IOL\SSO\v1\Exceptions\NotFoundException;
    use IOL\SSO\v1\Request\APIResponse;
    use IOL\SSO\v1\Tokens\IntermediateToken;


    $response = APIResponse::getInstance();

    $response->setAllowedRequestMethods(
        new \IOL\SSO\v1\BitMasks\RequestMethod(\IOL\SSO\v1\BitMasks\RequestMethod::POST)
    );
    $response->needsAuth(false);

    $response->check();
    $input = $response->getRequestData([
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

    $app = App::getCurrent();

    try {
        $user = new User(username: $input['username']);
    } catch (NotFoundException $e) {
        APIResponse::getInstance()->addError(100472)->render();
    }

    if ($user->login(password: $input['password'])) {
        $intermediateToken = new IntermediateToken();

        $token = $intermediateToken->createNew(
            app:  $app,
            user: $user
        );

        APIResponse::getInstance()->addData('token', $token);
    }
