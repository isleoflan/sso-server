<?php

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\APIResponse;

class User
{
    public const DB_TABLE = 'user';

    private string $id;
    private string $username;
    private string $password;
    private ?Date $activated;
    private ?Date $blocked;

    /**
     * @throws NotFoundException
     * @throws \IOL\SSO\v1\Exceptions\InvalidValueException
     */
    public function __construct(?string $id = null, ?string $username = null)
    {
        if(!is_null($id)){
            if(!UUID::isValid($id)){
                throw new InvalidValueException('Invalid Login Request ID');
            }
            $this->loadData(Database::getRow('id', $id,self::DB_TABLE));
        } elseif(!is_null($username)){
            $this->loadData(Database::getRow('username', $username,self::DB_TABLE));
        }

        // of course, we still can instantiate an empty object, for instance to create a new user
    }

    /**
     * @throws NotFoundException
     */
    public function loadData(array|false $values): void
    {
        if(!$values || count($values) === 0){
            throw new NotFoundException('User could not be loaded');
        }

        $this->id = $values['id'];
        $this->username = $values['username'];
        $this->password = $values['password'];
        $this->activated = is_null($values['activated']) ? null :new Date($values['activated']);
        $this->blocked = is_null($values['blocked']) ? null : new Date($values['blocked']);

    }

    public function login(string $password): bool
    {
        if (password_verify($password, $this->password)) {
            if ($this->isActivated()) {
                if (!$this->isBlocked()) {
                    // user has entered correct email/password combination and also activated their account
                    // create a new global session for the user


                    // and return that the login succeeded
                    return true;
                }
                // oldUser has been blocked, throw respective error
                APIResponse::getInstance()->addError(100474)->render();
            }
            // e-mail address has not been confirmed yet
            // first, resend the confirmation mail
            //$this->sendConfirmMail();

            // then throw respective error
            APIResponse::getInstance()->addError(100473)->render();
        }
        // password does not match, throw error
        APIResponse::getInstance()->addError(100472)->render();
    }
    // the email address, the user using to try to log in, is not registered / can not be found in DB
    // throw error
    //APIResponse::getInstance()->addError(100472)->render();

    private function isActivated(): bool
    {
        return !is_null($this->activated);
    }

    private function isBlocked(): bool
    {
        return !is_null($this->blocked);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }




}
