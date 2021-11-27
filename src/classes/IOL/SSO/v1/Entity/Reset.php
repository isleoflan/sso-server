<?php

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\Queue;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Enums\QueueType;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Tokens\LoginRequest;

class Reset
{
    public const DB_TABLE = 'user_reset';

    public const EXPIRATION = 1800;

    private string $id;
    private User $user;
    private Date $created;
    private LoginRequest $loginRequest;

    /**
     * @throws InvalidValueException
     */
    public function __construct(string $id = null)
    {

        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Reset ID');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        }
    }

    /**
     * @throws NotFoundException
     */
    private function loadData(array|false $values)
    {

        if (!$values || count($values) === 0) {
            throw new NotFoundException('Login Request could not be loaded');
        }

        $this->id = $values['id'];
        $this->user = new User($values['user_id']);
        $this->created = new Date($values['created']);
    }

    public function createNew(User $user, LoginRequest $loginRequest)
    {
        $this->id = UUID::newId(self::DB_TABLE);
        $this->user = $user;
        $this->created = new Date();
        $this->loginRequest = $loginRequest;

        $database = Database::getInstance();
        $database->insert(self::DB_TABLE, [
            'id'                => $this->id,
            'user_id'           => $this->user->getId(),
            'created'           => $this->created->format(Date::DATETIME_FORMAT_MICRO),
            'login_request_id'  => $this->loginRequest->getId()
        ]);


        $resetQueue = new Queue(new QueueType(QueueType::ALL_USER));
        $resetQueue->publishMessage($this->id, new QueueType(QueueType::RESET_USER));
    }
}