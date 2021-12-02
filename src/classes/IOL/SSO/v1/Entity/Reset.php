<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\Content\Mail;
use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\Environment;
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

    private /*readonly*/ string $USER_RESET_URL;

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
            $this->USER_RESET_URL = Environment::get('FRONTEND_BASE_URL') . '/auth/reset-password/';
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
            'id' => $this->id,
            'user_id' => $this->user->getId(),
            'created' => $this->created->format(Date::DATETIME_FORMAT_MICRO),
            'login_request_id' => $this->loginRequest->getId()
        ]);


        $resetQueue = new Queue(new QueueType(QueueType::ALL_USER));
        $resetQueue->publishMessage($this->id, new QueueType(QueueType::RESET_USER));
    }

    public function sendResetMail()
    {
        $mail = new Mail();
        $mail->setReceiver($this->user->getEmail());
        $mail->setSubject('Passwort zurücksetzen');
        $mail->setTemplate('register');
        $mail->addVariable('preheader', '');
        $mail->addVariable('expiration', (self::EXPIRATION / 60) . ' Minuten');
        $mail->addVariable('reseturl', $this->USER_RESET_URL . $this->getHash());

        $mailerQueue = new Queue(new QueueType(QueueType::MAILER));
        $mailerQueue->publishMessage(json_encode($mail), new QueueType(QueueType::MAILER));
    }

    public function getHash(): string
    {
        return md5(Environment::get('RESET_HASH') . $this->id);
    }
}
