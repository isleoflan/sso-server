<?php

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\Exceptions\EncryptionException;
use IOL\SSO\v1\Exceptions\InvalidObjectValueException;
use IOL\SSO\v1\ObjectTemplates\UserTemplate;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Request\Session;
use IOL\SSO\v1\Tokens\IntermediateToken;

class User
{
    public const DB_TABLE = 'user';

    private string $id;
    private string $username;
    private string $password;
    private ?Date $activated;
    private ?Date $blocked;

    /**
     * @throws EncryptionException
     */
    public function __construct(?string $id = null, ?string $username = null)
    {
        if(!is_null($id)){
            $this->loadData(Database::getRow('id',$id,self::DB_TABLE));
        } elseif(!is_null($username)){
            $this->loadData(Database::getRow('username',$username,self::DB_TABLE));
        }

        // of course, we still can instantiate an empty object, for instance to create a new user
    }

    /**
     * @throws EncryptionException
     */
    public function loadData(array $values): void
    {
        if(count($values) === 0){
            throw new EncryptionException('User could not be loaded');
        }

        $this->id = $values['id'];
        $this->username = $values['username'];
        $this->password = $values['password'];
        $this->activated = is_null($values['activated']) ? null :new Date($values['activated']);
        $this->blocked = is_null($values['blocked']) ? null : new Date($values['blocked']);

    }

    public function login(string $password): bool|string
    {
        if (password_verify($password, $this->password)) {
            if ($this->isActivated()) {
                if (!$this->isBlocked()) {
                    // user has entered correct email/password combination and also activated their account
                    // create a new IntermediateToken for the user, assigned to the App, that requested it
                    $token = new IntermediateToken(user: $this);

                    // and return it
                    return $session->create();
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
    // APIResponse::getInstance()->addError(100472)->render();

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
