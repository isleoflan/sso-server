<?php

class SsoCest
{
    public $loginRequest = '';
    private mixed $globalSessionId;

    public function _before(ApiTester $I)
    {
    }

    // tests

/*
    public function createRequest(ApiTester $I)
    {
        $I->haveHttpHeader('IOL-App-Token','e9fca7d0-b02d-40bd-bad8-3fb3c76b9096');
        $I->sendPost('auth/request', json_encode([
            'redirectURL' => 'https://sso.isleoflan.ch',
            'scope' => 7
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson(['errors' => []]);

        $requestId = $I->grabDataFromResponseByJsonPath('$.data.redirect');

        $this->loginRequest = str_replace(['http://localhost/', 'https://staging.api.sso.isleoflan.ch/', 'https://api.sso.isleoflan.ch/', 'https://sso.isleoflan.ch/'], '', $requestId[0]);
    }

    public function signInWithWrongPassword(ApiTester $I)
    {
        $I->sendPost('auth/login', json_encode([
            'username' => 'stui',
            'password' => '1234',
            'loginRequestId' => $this->loginRequest
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['errorCode' => '100472']]]);
    }

    public function signInSuccessful(ApiTester $I)
    {
        $I->sendPost('auth/login', json_encode([
            'username' => 'stui',
            'password' => '123',
            'loginRequestId' => $this->loginRequest
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson(['errors' => []]);

        $globalSessionId = $I->grabDataFromResponseByJsonPath('$.data.globalSessionId');

        $this->globalSessionId = $globalSessionId[0];
    }

    public function createAnotherRequest(ApiTester $I)
    {
        $I->haveHttpHeader('IOL-App-Token','e9fca7d0-b02d-40bd-bad8-3fb3c76b9096');
        $I->sendPost('auth/request', json_encode([
            'redirectURL' => 'https://sso.isleoflan.ch',
            'scope' => 7
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson(['errors' => []]);

        $requestId = $I->grabDataFromResponseByJsonPath('$.data.redirect');

        $this->loginRequest = str_replace(['http://localhost/', 'https://staging.api.sso.isleoflan.ch/', 'https://api.sso.isleoflan.ch/', 'https://sso.isleoflan.ch/'], '', $requestId[0]);
    }
/*
    public function signInSuccessfulWithGSID(ApiTester $I)
    {
        $I->sendPost('auth/login', json_encode([
            'globalSessionId' => $this->globalSessionId,
            'loginRequestId' => $this->loginRequest
        ]));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson(['errors' => []]);
    }
*/
/*
    public function signInWithUnregisteredEmail(ApiTester $I)
    {globalSessionId
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
