<?php

class SsoCest
{
    public function _before(ApiTester $I)
    {
    }

    // tests
    public function signInSuccessful(ApiTester $I)
    {
        $I->sendPost('user/login', json_encode([
            'username' => 'stui',
            'password' => '123'
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson(['errors' => []]);
    }


    public function signInWithWrongPassword(ApiTester $I)
    {
        $I->sendPost('user/login', json_encode([
            'username' => 'stui',
            'password' => '1234'
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['errorCode' => 100472]]]);
    }
/*
    public function signInWithUnregisteredEmail(ApiTester $I)
    {
        $I->sendPost('user/auth/login', json_encode([
            'username' => 'unregistered@account.com',
            'password' => '12345678'
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['errorCode' => 100472]]]);
    }


    public function signInWithUnactivatedAccount(ApiTester $I)
    {
        $I->sendPost('user/auth/login', json_encode([
            'username' => '02c5fabb3315f0138677@stui.ch',
            'password' => '123'
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['errorCode' => 100473]]]);
    }


    public function signInWithDisallowedAccount(ApiTester $I)
    {
        $I->sendPost('user/auth/login', json_encode([
            'username' => '12e50827245e65731ce2@stui.ch',
            'password' => '123'
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['errorCode' => 100475]]]);
    }


    public function signInWithBlockedAccount(ApiTester $I)
    {
        $I->sendPost('user/auth/login', json_encode([
            'username' => '3f62ada632b88608999e@stui.ch',
            'password' => '123'
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['errorCode' => 100474]]]);
    }
*/
}