<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\Content\Mailer;
use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\Environment;
use IOL\SSO\v1\DataSource\Queue;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\Email;
use IOL\SSO\v1\DataType\PhoneNumber;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Enums\Gender;
use IOL\SSO\v1\Enums\QueueType;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Session\GlobalSession;
use IOL\SSO\v1\Tokens\LoginRequest;

class User
{
    public const DB_TABLE = 'user';

    private string $id;
    private string $username;
    private string $password;
    private ?Date $activated;
    private ?Date $blocked;

    private Gender $gender;
    private string $foreName;
    private string $lastName;
    private string $address;
    private string $zipCode;
    private string $city;
    private Date $birthDate;
    private Email $email;
    private PhoneNumber $phone;

    private ?GlobalSession $globalSession = null;

    private /*readonly*/
    string $USER_RESET_URL;
    private /*readonly*/
    string $REGISTER_DOI_URL;

    /**
     * @throws NotFoundException
     * @throws \IOL\SSO\v1\Exceptions\InvalidValueException
     */
    public function __construct(?string $id = null, ?string $username = null)
    {
        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Login Request ID');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        } elseif (!is_null($username)) {
            $this->loadData(Database::getRow('username', $username, self::DB_TABLE));
        }

        // of course, we still can instantiate an empty object, for instance to create a new user


        // hydrate the readonly URL variables
        $this->USER_RESET_URL = Environment::get('FRONTEND_BASE_URL') . '/auth/reset-password/';
        $this->REGISTER_DOI_URL = Environment::get('FRONTEND_BASE_URL') . '/auth/register/';
    }

    /**
     * @throws NotFoundException
     */
    public function loadData(array|false $values): void
    {
        if (!$values || count($values) === 0) {
            throw new NotFoundException('User could not be loaded');
        }

        $this->id = $values['id'];
        $this->username = $values['username'];
        $this->password = $values['password'];
        $this->activated = is_null($values['activated']) ? null : new Date($values['activated']);
        $this->blocked = is_null($values['blocked']) ? null : new Date($values['blocked']);


        $this->gender = new Gender($values['gender']);
        $this->foreName = $values['forename'];
        $this->lastName = $values['lastname'];
        $this->address = $values['address'];
        $this->zipCode = $values['zip_code'];
        $this->city = $values['city'];
        $this->birthDate = new Date($values['birth_date']);
        $this->email = new Email($values['email']);
        $this->phone = new PhoneNumber($values['phone']);

    }

    public function login(string $password): bool
    {
        if (password_verify($password, $this->password)) {
            if ($this->isActivated()) {
                if (!$this->isBlocked()) {
                    // user has entered correct email/password combination and also activated their account
                    // create a new global session for the user
                    $this->globalSession = $this->createNewGlobalSession();


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

    public function usernameIsTaken(string $username): bool
    {
        return $this->valueExists($username, 'username');
    }

    public function emailIsTaken(Email $email): bool
    {
        return $this->valueExists($email->getEmail(), 'email');
    }

    private function valueExists(string $value, string $field): bool
    {
        $database = Database::getInstance();
        $database->where($field, $value);
        $data = $database->get(self::DB_TABLE);

        return isset($data[0][$field]);
    }

    public function createNew(
        LoginRequest $loginRequest,
        string       $username,
        string       $password,
        Gender       $gender,
        string       $foreName,
        string       $lastName,
        string       $address,
        string       $zipCode,
        string       $city,
        Date         $birthDate,
        Email        $email,
        PhoneNumber  $phone
    )
    {
        $this->id = UUID::newId('user');

        $this->username = $username;
        $this->password = $this->getPasswordHash($password);
        $this->gender = $gender;
        $this->foreName = $foreName;
        $this->lastName = $lastName;
        $this->address = $address;
        $this->zipCode = $zipCode;
        $this->city = $city;
        $this->birthDate = $birthDate;
        $this->email = $email;
        $this->phone = $phone;


        $database = Database::getInstance();
        $database->insert(self::DB_TABLE, [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
            'activated' => null,
            'blocked' => null,
            'gender' => $this->gender->getValue(),
            'forename' => $this->foreName,
            'lastname' => $this->lastName,
            'address' => $this->address,
            'zip_code' => $this->zipCode,
            'city' => $this->city,
            'birth_date' => $this->birthDate->format(Date::DATE_FORMAT_STD),
            'email' => $this->email->getEmail(),
            'phone' => $this->phone->international()
        ]);

        $loginRequest->allocate($this);

        $newUserQueue = new Queue(new QueueType(QueueType::ALL_USER));
        $newUserQueue->publishMessage($this->id, new QueueType(QueueType::NEW_USER));
    }

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

    /**
     * @return GlobalSession|null
     * @throws NotFoundException
     */
    public function getGlobalSession(): ?GlobalSession
    {
        if (is_null($this->globalSession)) {
            throw new NotFoundException('No Global Session has been defined yet.');
        }
        return $this->globalSession;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    private function getPasswordHash($password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public function sendConfirmationMail()
    {
        $mail = new Mailer();
        $mail->setReceiver($this->email->getEmail());
        $mail->setSubject('Bestätige deine E-Mail Adresse');
        $mail->setTemplate('register');
        $mail->setPreheader('Aktiviere jetzt deinen Account, um dir ein Ticket für die nächste Isle of LAN zu sichern.');
        $mail->addTemplateSetting('firstname', $this->foreName);
        $mail->addTemplateSetting('activatelink', $this->getDOIUrl());
        $mail->send();
    }

    public function getDOIUrl(): string
    {
        return $this->REGISTER_DOI_URL . $this->getConfirmationHash();
    }

    public function getConfirmationHash(): string
    {
        return md5($this->getUsername() . Environment::get('DOI_SALT'));
    }

    /**
     * @throws NotFoundException
     */
    public function fetchByConfirmationHash(string $hash): void
    {
        $data = Database::getRow('MD5(CONCAT(username, "' . Environment::get('DOI_SALT') . '"))', $hash, self::DB_TABLE);
        $this->loadData($data);
    }

    public function activate(): void
    {
        $database = Database::getInstance();
        $database->where('id', $this->getId());
        $database->update(self::DB_TABLE, [
            'activated' => Date::now(Date::DATETIME_FORMAT_MICRO)
        ]);

        $this->createNewGlobalSession();
    }

    private function createNewGlobalSession(): GlobalSession
    {
        $globalSession = new GlobalSession();
        $globalSession->createNew($this);

        return $globalSession;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

}
