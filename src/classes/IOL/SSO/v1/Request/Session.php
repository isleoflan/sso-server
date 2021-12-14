<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Request;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Entity\App;
use DateInterval;
use Exception;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Session\GlobalSession;

class Session
{
    public const DB_TABLE = 'session';

    /**
     * Defines after which amount of seconds, without being renewed, the session is being invalidated.
     *
     * @see Session::EXPIRATION_LEEWAY
     */
    public const EXPIRATION_INTERVAL = 1800;

    /**
     * Defines the "wiggle room" in seconds, before the invalidation of the session is really taking place.
     * The thought behind this is, that in the frontend, an automatic logout is performed, after
     * Session::EXPIRATION_INTERVAL seconds. If this logout does not happen in time or the client system clock does
     * not match the servers, the leeway is being added.
     *
     * Another possibility is, that the request might take longer than anticipated, so if the request is sent on the
     * last possible second, but because of a slow or interrupted connection, the request is given a chance to
     * actually prolong the session, even if it theoretically already expired.
     *
     * Any session is always being invalidated after Session::EXPIRATION_INTERVAL + Session::EXPIRATION_LEEWAY
     * seconds.
     *
     * @see Session::EXPIRATION_INTERVAL
     */
    public const EXPIRATION_LEEWAY = 10;


    private string $id;
    private Date $creation;
    private Date $expiry;
    private GlobalSession $globalSession;
    private App $app;


    /**
     * @throws NotFoundException
     * @throws InvalidValueException
     */
    public function __construct(?string $id = null)
    {
        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Refresh Token');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        }
    }

    /**
     * @throws NotFoundException
     * @throws \IOL\SSO\v1\Exceptions\InvalidValueException
     * @throws \Exception
     */
    private function loadData(array|false $values)
    {

        if (!$values || count($values) === 0) {
            throw new NotFoundException('Refresh Token could not be found');
        }

        $this->id = $values['id'];
        $this->globalSession = new GlobalSession($values['global_session_id']);
        $this->app = new App($values['app_id']);
        $this->creation = new Date($values['created']);
        $this->expiry = new Date($values['expiration']);
    }


    /**
     * @return string|bool
     *
     * create a new session for a given user.
     */
    public function create(GlobalSession $globalSession, App $app): string|bool
    {
        // don't reuse already existing session ids
        // oh no, we might eventually run out of ids
        // future bugfix: @ me in 10790 years, if you need to serve 1 octillion sessions per second 24/7
        $this->id = UUID::newId(self::DB_TABLE);
        $this->app = $app;

        $this->globalSession = $globalSession;

        $this->creation = new Date('u');

        $expiry = clone $this->creation;
        try {
            $expiry->add(new DateInterval('PT' . self::EXPIRATION_INTERVAL . 'S'));
        } catch (Exception) {
        }
        $this->expiry = $expiry;

        $database = Database::getInstance();
        $database->insert(
            self::DB_TABLE,
            [
                'id' => $this->id,
                'global_session_id' => $this->globalSession->getId(),
                'app_id' => $this->app->getId(),
                'created' => $this->creation->micro(),
                'expiration' => $this->expiry->micro(),
            ]
        );

        return $this->id;
    }

    public function sessionExists(): bool
    {
        if (is_null($this->getId())) {
            return false;
        }
        $database = Database::getInstance();
        $database->where('id', $this->getId());
        $data = $database->get(self::DB_TABLE);

        return isset($data[0]['id']);
    }

    public function renew(): void
    {
        $now = new Date('now');
        try {
            $now->add(new DateInterval('PT' . self::EXPIRATION_INTERVAL . 'S'));
        } catch (Exception) {
        }
        $this->expiry = $now;

        $database = Database::getInstance();
        $database->where('id', $this->getId());
        $database->update(
            'sessions',
            [
                'expiration' => $now->sqldatetime(),
            ]
        );
    }

    /**
     * revoke the given session immediately.
     * this is mainly used to log a user out.
     * the session is expired by setting the expiration date before (or at) the current time
     */
    public function revoke(): void
    {
        $now = new Date('now');
        try {
            // to be safe, subtract a certain timeframe from the current time, so it already is in the past
            $now->sub(new DateInterval('PT' . (Session::EXPIRATION_LEEWAY + 10) . 'S'));
        } catch (Exception) {
            // if the subtraction fails for some reason, don't do anything
            // this MAY enable a concurrent prolonging request to be fulfilled.
            // but this actually happening is beyond any reason (don't quote me on that)
        }
        $this->expiry = $now;

        $database = Database::getInstance();
        $database->where('id', $this->getId());
        $database->update(
            'sessions',
            [
                'expiration' => $now->sqldatetime(),
            ]
        );
    }

    /**
     * @return bool
     *
     * checks if the given session has expired. Leeway is taken in account
     *
     * @see Session::EXPIRATION_LEEWAY
     */
    public function isExpired(): bool
    {
        $now = new Date('now');
        $expiry = clone $this->expiry;
        try {
            $expiry->add(new DateInterval('PT' . self::EXPIRATION_LEEWAY . 'S'));
        } catch (Exception) {
            // no exception handling necessary. if the addition of the leeway fails, we work without it
        }
        if ($now > $expiry) {
            return true;
        }

        return false;
    }

    /**
     * @return Date
     */
    public function getCreation(): Date
    {
        return $this->creation;
    }

    /**
     * @return Date
     */
    public function getExpiry(): Date
    {
        return $this->expiry;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return \IOL\SSO\v1\Session\GlobalSession
     */
    public function getGlobalSession(): GlobalSession
    {
        return $this->globalSession;
    }



}
