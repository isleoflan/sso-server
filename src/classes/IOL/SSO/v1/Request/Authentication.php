<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Request;

use IOL\SSO\v1\DataSource\File;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Entity\oldUser;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Nowakowskir\JWT\JWT;
use Nowakowskir\JWT\TokenDecoded;
use Nowakowskir\JWT\TokenEncoded;

class Authentication
{
    public const JWT_SESSION_KEY = 'ses';
    private const JWT_PUBLIC_KEY = '/public.pub';
    private const JWT_PRIVATE_KEY = '/private.key';
    private const JWT_ALGORITHM = JWT::ALGORITHM_RS256;

    private static oldUser $user;
    private static ?Session $session = null;
    private static bool $authResult;

    #[ArrayShape([
        'success' => 'bool',
        'object' => 'oldUser|Error',
    ])]
    public static function authenticate(): oldUser
    {
        $session = self::getSessionFromRequest();

        if (!$session->isExpired()) {
            // The session is valid and still in time.
            // renew the session for further usage
            $session->renew();
            $user = $session->getUser();

            // if no user is attached to said session, cancel execution and throw an error
            if (is_null($user)) {
                self::$authResult = false;

                APIResponse::getInstance()->addError(100002)->render();
            }

            // save auth result and user locally for later usage, so the token does not have to be decrypted yet again
            self::$user = $user;
            self::$authResult = true;

            return $user;
        }
        // The provided session is expired (leeway is considered)
        self::$authResult = false;

        APIResponse::getInstance()->addError(100001)->render();
    }

    public static function getSessionFromRequest(): Session
    {
        // check, if Authorization header is present
        $authToken = false;
        $authHeader = APIResponse::getRequestHeader('Authorization');
        if (!is_null($authHeader)) {
            if (str_starts_with($authHeader, 'Bearer ')) {
                $authToken = substr($authHeader, 7);
            }
        }
        if (!$authToken) {
            // no actual token has been transmitted. Abort execution and send request to the gulag
            APIResponse::getInstance()->addError(100003)->render();
        }

        // check if given Auth header is a valid JWT token
        $authToken = new TokenEncoded($authToken);
        try {
            $authToken->validate(file_get_contents(File::getBasePath() . self::JWT_PUBLIC_KEY), self::JWT_ALGORITHM);
        } catch (Exception) { // TODO: sometimes InvalidStructureExceptions don't get caught, check why
            // Token validation failed.
            APIResponse::getInstance()->addError(100002)->render();
        } /*
            // we're not expiring tokens, handling session expiry separately

            catch (TokenExpiredException $e){
            // token is expired
            return ['success' => false,'object' => new Error(100001)];
        }*/

        // get payload from token and check, if token is still valid
        $payload = $authToken->decode()->getPayload();

        if (isset($payload[self::JWT_SESSION_KEY])) {
            $session_id = $payload[self::JWT_SESSION_KEY];
            if (UUID::isValid($session_id)) {
                $session = new Session(sessionId: $session_id);

                if ($session->sessionExists()) {
                    self::$session = $session;

                    return $session;
                }
                // The provided session id is not stored in DB, therefore is not valid
                APIResponse::getInstance()->addError(100002)->render();
            }
            // the provided session id is not a valid UUID
            APIResponse::getInstance()->addError(100002)->render();
        }
        // no session key is found in payload
        APIResponse::getInstance()->addError(100002)->render();
    }

    #[Pure]
    public static function getSessionId(): ?string
    {
        if (isset(self::$session)) {
            return self::$session->getSessionId();
        }
        return '';
    }

    public static function createNewToken(array $data): string
    {
        $rawToken = new TokenDecoded($data);
        $encodedToken = $rawToken->encode(
            key: file_get_contents(File::getBasePath() . self::JWT_PRIVATE_KEY),
            algorithm: self::JWT_ALGORITHM
        );

        return $encodedToken->toString();
    }

    public static function getCurrentUser(): ?oldUser
    {
        if (!self::isAuthenticated()) {
            self::authenticate();
        }

        return self::isAuthenticated() ? self::$user : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::$authResult;
    }
}
