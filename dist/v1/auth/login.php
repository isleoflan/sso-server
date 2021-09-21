<?php
// logs in the user and returns an intermediate key

use IOL\SSO\v1\Entity\User;

$app = \IOL\SSO\v1\Entity\App::getCurrent();

$user = new User(username: $input['username']);
if($user->login(password: $input['password'])){
    $intermediateToken = new \IOL\SSO\v1\Tokens\IntermediateToken();

    $token = $intermediateToken->createNew(
        app: $app,
        user: $user
    );

    \IOL\SSO\v1\Request\APIResponse::getInstance()->addData('token', $token);
}