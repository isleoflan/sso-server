<?php

    declare(strict_types=1);

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\Request\APIResponse;

    $response = APIResponse::getInstance();

    $response->setAllowedRequestMethods(
        new RequestMethod(RequestMethod::GET)
    );
    $response->needsAuth(true);
    $response->isSsoFrontendOnly(false);

    $user = $response->check();

    $response->addData('userId', $user->getId());
