<?php

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Request\APIResponse;

    $response = APIResponse::getInstance();

    $response->setAllowedRequestMethods(
        new RequestMethod(RequestMethod::POST)
    );
    $response->needsAuth(false);

    $response->check();
    $input = $response->getRequestData([
                                           [
                                               'name'      => 'redirectURL',
                                               'types'     => ['string'],
                                               'required'  => true,
                                               'errorCode' => 0,
                                           ],
                                           [
                                               'name'      => 'scope',
                                               'types'     => ['integer'],
                                               'required'  => true,
                                               'errorCode' => 0,
                                           ],
                                       ]);

    $app = App::getCurrent();
    if(!$app->checkRedirectURL($input['redirectURL'])){
        $response->addError(102001)->render();
    }

    $scope = new \IOL\SSO\v1\BitMasks\Scope($input['scope']);

    $loginRequest = new \IOL\SSO\v1\Tokens\LoginRequest();
    $requestId = $loginRequest->createNew(
        app: $app,
        redirectURL: $input['redirectURL'],
        scope: $scope
    );

    $response->addData('redirect', $_ENV['FRONTEND_BASE_URL'] . $requestId);
