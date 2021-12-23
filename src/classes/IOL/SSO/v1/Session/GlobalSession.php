<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Session;

use DateInterval;
use DateTime;
use Exception;
use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\Request\IPAddress;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\UserAgent;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class GlobalSession
{
    public const DB_TABLE = 'global_session';

    /**
     * Defines after which amount of days, without being renewed, the session is being invalidated.
     * This is set to 30 days
     *
     * @see Session::EXPIRATION_LEEWAY
     */
    public const EXPIRATION_INTERVAL = 30;

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
    private User $user;
    private Date $created;
    private Date $lastSeen;
    private Date $expiration;

    private IPAddress $IPAddress;
    private UserAgent $userAgent;

    /**
     * @throws InvalidValueException
     */
    public function __construct(?string $id = null)
    {
        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Global Session ID');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        }
    }

    /**
     * @throws \IOL\SSO\v1\Exceptions\NotFoundException|\IOL\SSO\v1\Exceptions\InvalidValueException
     */
    private function loadData(array|false $values)
    {

        if (!$values || count($values) === 0) {
            throw new NotFoundException('Login Request could not be loaded');
        }

        $this->id = $values['id'];
        $this->user = new User($values['user_id']);
        $this->created = new Date($values['created']);
        $this->lastSeen = new Date($values['last_seen']);
        $this->expiration = new Date($values['expiration']);
        $this->IPAddress = new IPAddress($values['ip_address']);
        $this->userAgent = new UserAgent($values['user_agent']);
    }

    public function createNew(User $user): string
    {
        $database = Database::getInstance();

        $this->id = UUID::newId(self::DB_TABLE);
        $this->user = $user;

        $this->created = new Date('now');
        $this->lastSeen = clone $this->created;
        $this->expiration = clone $this->created;
        $this->expiration->add(new \DateInterval('P' . self::EXPIRATION_INTERVAL . 'D'));
        $this->IPAddress = new IPAddress();
        $this->userAgent = new UserAgent();

        $database->insert(self::DB_TABLE, [
            'id' => $this->id,
            'user_id' => $this->user->getId(),
            'created' => $this->created->format(Date::DATETIME_FORMAT_MICRO),
            'last_seen' => $this->lastSeen->format(Date::DATETIME_FORMAT_MICRO),
            'expiration' => $this->expiration->format(Date::DATETIME_FORMAT_MICRO),
            'ip_address' => $this->IPAddress->getAddress(),
            'user_agent' => $this->userAgent->getAgent(),
        ]);

        return $this->id;
    }

    /**
     * @return bool
     *
     * checks if the given session has expired. Leeway is taken in account
     *
     * @see Session::EXPIRATION_LEEWAY
     */
    public function isValid(): bool
    {
        $now = new Date('now');
        $expiry = clone $this->expiration;
        try {
            $expiry->add(new DateInterval('PT' . self::EXPIRATION_LEEWAY . 'S'));
        } catch (Exception) {
            // no exception handling necessary. if the addition of the leeway fails, we work without it
        }
        if ($now > $expiry) {
            return false;
        }

        return true;
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
            $now->sub(new DateInterval('PT' . (self::EXPIRATION_LEEWAY + 10) . 'S'));
        } catch (Exception) {
            // if the subtraction fails for some reason, don't do anything
            // this MAY enable a concurrent prolonging request to be fulfilled.
            // but this actually happening is beyond any reason (don't quote me on that)
        }
        $this->expiration = $now;

        $database = Database::getInstance();
        $database->where('id', $this->id);
        $database->update(
            'global_session',
            [
                'expiration' => $now->sqldatetime(),
            ]
        );
    }


    public function refresh(): void
    {
        $now = new Date('now');
        try {
            $now->add(new DateInterval('P' . self::EXPIRATION_INTERVAL . 'D'));
        } catch (Exception) {
        }
        $this->expiration = $now;

        $database = Database::getInstance();
        $database->where('id', $this->id);
        $database->update(
            'global_session',
            [
                'expiration' => $now->sqldatetime(),
            ]
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }


    #[Pure]
    #[ArrayShape(['username' => "string", 'avatar' => "string", 'email' => "string"])]
    public function getInfo(): array
    {
        return [
            'username' => $this->user->getUsername(),
            'avatar' => 'https://avatars.dicebear.com/api/gridy/'.$this->user->getUsername().'.svg', // TODO: link account api / implement avatar & CDN service
            'email' => $this->user->getEmail(), // TODO: link account api
        ];
    }

}
