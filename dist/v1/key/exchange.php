<?php
    // exchanges the intermediate key to a full JWT

    use IOL\SSO\v1\BitMasks\RequestMethod;
    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Exceptions\EncryptionException;
    use IOL\SSO\v1\Exceptions\InvalidValueException;
    use IOL\SSO\v1\Request\APIResponse;
    use IOL\SSO\v1\Tokens\IntermediateToken;

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
                                               'errorCode' => 0,
                                           ],
                                       ]);

    $app = App::getCurrent();

    $intermediateToken = new IntermediateToken();
    try {
        $tokenData = $intermediateToken->checkToken($input['token']);
    } catch (InvalidValueException $e) {
        // either the token or the checksum was in an invalid format
        // throw error with "invalid format of token"
        APIResponse::getInstance()->addError(202001)->render();
    } catch (EncryptionException $e) {
        // Something with the decryption of the token failed.
        // Throw an "unrecoverable error"
        APIResponse::getInstance()->addError(999104)->render();
    }


