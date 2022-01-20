<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Request;

use IOL\SSO\v1\DataSource\File;
use Exception;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\IOLException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Nowakowskir\JWT\JWT;
use Nowakowskir\JWT\TokenDecoded;
use Nowakowskir\JWT\TokenEncoded;

class Authentication
{
    public const JWT_SESSION_KEY = 'ses';
    public const JWT_ISSUE_KEY = 'iat';
    private const JWT_PUBLIC_KEY = '/authPublic.pem';
    private const JWT_PRIVATE_KEY = '/authPrivate.pem';
    private const JWT_ALGORITHM = JWT::ALGORITHM_RS256;

    private static User $user;
    private static ?Session $session = null;
    private static bool $authResult;

    public static function authenticate(): User
    {
        $session = self::getSessionFromRequest();

        if (!$session->isExpired()) {
            // The session is valid and still in time.
            // do not renew the session for further usage
            // $session->renew();
            $user = $session->getGlobalSession()->getUser();

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

        try {
            // check if given Auth header is a valid JWT token
            $authToken = new TokenEncoded($authToken);
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
            try {
                $session = new Session(id: $session_id);
            } catch (IOLException) {
                APIResponse::getInstance()->addError(100002)->render();
            }

            if ($session->sessionExists()) {
                self::$session = $session;

                return $session;
            }
            // The provided session id is not stored in DB, therefore is not valid
            APIResponse::getInstance()->addError(100002)->render();
        }
        // no session key is found in payload
        APIResponse::getInstance()->addError(100002)->render();
    }

    #[Pure]
    public static function getSessionId(): ?string
    {
        if (isset(self::$session)) {
            return self::$session->getId();
        }
        return '';
    }

    public static function createNewToken(Session $session): string
    {
        $data = [
            self::JWT_ISSUE_KEY => time(),
            self::JWT_SESSION_KEY => $session->getId()
        ];

        $rawToken = new TokenDecoded($data);
        $encodedToken = $rawToken->encode(
            key: file_get_contents(File::getBasePath() . self::JWT_PRIVATE_KEY),
            algorithm: self::JWT_ALGORITHM
        );

        return $encodedToken->toString();
    }

    public static function getCurrentUser(): ?User
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
