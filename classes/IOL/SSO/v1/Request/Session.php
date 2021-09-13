<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Request;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Entity\oldUser;
use DateInterval;
use Exception;

class Session
{
    /**
     * Defines after which amount of seconds, without being renewed, the session is being invalidated.
     *
     * @see Session::EXPIRATION_LEEWAY
     */
    public const EXPIRATION_INTERVAL = 300;

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

    private Date $creation;
    private Date $expiry;

    public function __construct(
        private ?oldUser $user = null,
        private ?string  $sessionId = null
    )
    {
        if (!is_null($this->sessionId)) {
            $this->load($this->sessionId);
        }
    }

    private function load(string $sessionId): void
    {
        $database = Database::getInstance();
        $database->where('id', $sessionId);
        $data = $database->get('sessions');
        if (isset($data[0]['id'])) {
            $this->setSessionId($data[0]['id']);
            try {
                $this->setCreation(new Date($data[0]['created']));
            } catch (Exception) {
            }
            try {
                $this->setExpiry(new Date($data[0]['expiration']));
            } catch (Exception) {
            }
            $this->setUser(new oldUser(username: $data[0]['username']));
        } else {
            $this->setSessionId(null);
        }
    }

    /**
     * @return string|bool
     *
     * create a new session for a given user. oldUser object has to be set beforehand, either manually or by injecting it
     * into the constructor of the session object
     */
    public function create(): string|bool
    {
        if (is_null($this->getUser())) {
            // if no user is assigned to the session object, no session can be created
            return false;
        }

        // don't reuse already existing session ids
        // oh no, we might eventually run out of ids
        // future bugfix: @ me in 10790 years, if you need to serve 1 octillion sessions per second 24/7
        do {
            $this->setSessionId(UUID::v4());
        } while ($this->sessionExists());

        $this->setCreation(new Date('u'));

        $expiry = clone $this->creation;
        try {
            $expiry->add(new DateInterval('PT' . self::EXPIRATION_INTERVAL . 'S'));
        } catch (Exception) {
        }
        $this->setExpiry($expiry);

        $database = Database::getInstance();
        $database->insert(
            'sessions',
            [
                'id' => $this->getSessionId(),
                'username' => $this->getUser()->getUsername(),
                'created' => $this->getCreation()->micro(),
                'expiration' => $this->getExpiry()->micro(),
            ]
        );

        return $this->getSessionId();
    }

    public function sessionExists(): bool
    {
        if (is_null($this->getSessionId())) {
            return false;
        }
        $database = Database::getInstance();
        $database->where('id', $this->getSessionId());
        $data = $database->get('sessions');

        return isset($data[0]['id']);
    }

    public function renew(): void
    {
        $now = new Date('now');
        try {
            $now->add(new DateInterval('PT' . self::EXPIRATION_INTERVAL . 'S'));
        } catch (Exception) {
        }
        $this->setExpiry($now);

        $database = Database::getInstance();
        $database->where('id', $this->getSessionId());
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
        $this->setExpiry($now);

        $database = Database::getInstance();
        $database->where('id', $this->getSessionId());
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
        $expiry = clone $this->getExpiry();
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
     * @param Date $creation
     */
    public function setCreation(Date $creation): void
    {
        $this->creation = $creation;
    }

    /**
     * @param Date $expiry
     */
    public function setExpiry(Date $expiry): void
    {
        $this->expiry = $expiry;
    }

    /**
     * @param string|null $sessionId
     */
    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @param oldUser|null $user
     */
    public function setUser(?oldUser $user): void
    {
        $this->user = $user;
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
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * @return oldUser|null
     */
    public function getUser(): ?oldUser
    {
        return $this->user;
    }

}
