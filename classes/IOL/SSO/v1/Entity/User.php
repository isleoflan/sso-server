<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\Content\Mailer;
use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\Environment;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\Email;
use IOL\SSO\v1\DataType\PhoneNumber;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Enums\Gender;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Request\Error;
use IOL\SSO\v1\Request\Session;
use DateInterval;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

/**
 * Class User
 *
 * @package IOL\SSO\v1\Entity
 */
class User
{
    public const DB_TABLE = 'users';
    public const DB_TABLE_RESET = 'user_reset';
    public const USER_RESET_EXPIRATION = 1800;

    public const DOI_SALT = '...';
    public const ACCESS_SALT1 = '...';
    public const ACCESS_SALT2 = '...';
    public const ACCESS_SALT3 = '...';

    private int $id;
    private string $username;
    private Gender $gender;
    private string $firstName;
    private string $lastName;
    private PhoneNumber $phonePrivate;
    private PhoneNumber $phoneBusiness;
    private PhoneNumber $phoneMobile;
    private Email $email;
    private ?string $passwordRaw;
    private string $password;
    private bool $permissionNewsletter;
    private bool $permissionAdvertising;
    private string $type;
    private ?Date $activationDate;
    private string $USER_RESET_URL = '/auth/reset-password/';
    private string $REGISTER_DOI_URL = '/auth/register/';
    private string $REGISTER_ACCESS_URL = '/auth/grant-access/';


    /**
     * User constructor.
     *
     * @param string|null $username
     * @param string|null $doi
     */
    public function __construct(?string $username = null, ?string $doi = null, $id = null)
    {
        $mandate = Mandate::getInstance();
        $this->USER_RESET_URL = 'https://' . $mandate->getDomain() . '/auth/reset-password/';
        $this->REGISTER_DOI_URL = 'https://' . $mandate->getDomain() . '/auth/register/';
        $this->REGISTER_ACCESS_URL = 'https://' . $mandate->getDomain() . '/auth/grant-access/';

        $userProvider = UserProvider::getInstance();

        if (!is_null($username)) {
            $data = $userProvider->get($username, 'username');
        }
        if (!is_null($doi)) {
            $data = Database::getRow('MD5(CONCAT(username,"' . Environment::get('DOI_SALT') . '"))', $doi, self::DB_TABLE, true);
        }
        if (!is_null($id)) {
            $data = $userProvider->get($id);
        }
        if (isset($data)) {
            $this->loadValues($data);
        }
    }

    /**
     * @param string $value
     * @param string $field
     *
     * @return bool
     */
    public function fetchByField(string $value, string $field = 'email'): bool
    {
        $database = Database::getInstance();
        $database->where($field, $value);
        $data = $database->get(self::DB_TABLE);
        if (count($data) > 0) {
            $this->loadValues($data[0]);
            return true;
        }
        return false;
    }

    /**
     * @param array|null $data
     * @return bool
     * @throws Exception
     */
    public function loadValues(array|null $data): bool
    {
        if (is_null($data)) {
            return false;
        }
        $this->setId($data['id']);
        $this->setUsername($data['username']);
        $this->setGender(new Gender($data['gender']));
        $this->setFirstName($data['first_name']);
        $this->setLastName($data['last_name']);
        $this->setPhone(new PhoneNumber($data['phone_mobile']));
        $this->setEmail(new Email($data['email']));
        $this->setPasswordRaw($data['_passwordRaw'] ?? null);
        $this->setPassword($data['password'] ?? null);
        $this->setActivationDate(is_null($data['activation_date']) ? null : new Date($data['activation_date']));

        return true;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function login(string $username, string $password): bool|string
    {
        $userProvider = UserProvider::getInstance();

        if ($this->loadValues($userProvider->get($username, 'username'))) {
            if (password_verify($password, $this->getPassword())) {
                if ($this->isActivated()) {
                    if (!$this->isBlocked()) {
                        // user has entered correct email/password combination and also activated their account
                        // create a new session for the user
                        $session = new Session(user: $this);

                        // and return it
                        return $session->create();
                    }
                    // User has been blocked, throw respective error
                    APIResponse::getInstance()->addError(100474)->render();
                }
                // e-mail address has not been confirmed yet
                // first, resend the confirmation mail
                $this->sendConfirmMail();

                // then throw respective error
                APIResponse::getInstance()->addError(100473)->render();
            }
            // password does not match, throw error
            APIResponse::getInstance()->addError(100472)->render();
        }
        // the email address, the user using to try to log in, is not registered / can not be found in DB
        // throw error
        APIResponse::getInstance()->addError(100472)->render();
    }

    /**
     * @param Email $email
     *
     * @return bool
     */
    public function emailIsRegistered(Email $email): bool
    {
        $email = $email->getEmail();
        $database = Database::getInstance();
        $database->where('mandate_id', Mandate::getCurrentMandateID());
        $database->where('email', $email);
        $data = $database->get(self::DB_TABLE);

        return isset($data[0]['email']);
    }

    public
    function createNew(
        Gender      $gender,
        string      $firstName,
        string      $lastName,
        PhoneNumber $phone,
        Email       $email,
        string      $password,
    ): bool
    {
        $this->setId(Database::getNextId(self::DB_TABLE, 50001));
        $this->setGender($gender);
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
        $this->setPhoneBusiness($phone);
        $this->setUsername($email->getEmail());
        $this->setEmail($email);
        $this->setPasswordRaw($password);
        $this->setPermissionNewsletter($newsletter);
        $this->setPermissionAdvertising($sms);

        $database = Database::getInstance();

        $insertResult = $database->insert(
            self::DB_TABLE,
            [
                'id' => $this->getId(),
                'username' => $this->getUsername(),
                'gender' => $this->getGender()->getGender(),
                'first_name' => $this->getFirstName(),
                'last_name' => $this->getLastName(),
                'email' => $this->getEmail()->getEmail(),
                'password' => $this->hashPassword(),
                'type' => 'admin',
                'activation_date' => null,
            ]
        );

        $this->sendConfirmMail();

        return $insertResult;
    }

    public
    function sendConfirmMail(): void
    {
        $mail = new Mailer();
        $mail->setReceiver($this->getEmail()->getEmail());
        $mail->setSubject('Bestätigen Sie Ihre E-Mail Adresse');
        $mail->setTemplate('register');
        $mail->setPreheader('');
        $mail->addTemplateSetting('lastname', $this->getLastName());
        $mail->addTemplateSetting('salutation', $this->getSalutation());
        $mail->addTemplateSetting('confirmurl', $this->getDOIUrl());
        $mail->send();
    }

    public function getDOIUrl(): string
    {
        return $this->REGISTER_DOI_URL . md5($this->getUsername() . Environment::get('DOI_SALT'));
    }

    public function fetchByDOIString(string $doiString): bool
    {
        $database = Database::getInstance();
        $database->where('MD5(CONCAT(username,"' . Environment::get('DOI_SALT') . '"))', $doiString);
        $values = $database->get(self::DB_TABLE);

        if (isset($values[0]['email'])) {
            $this->loadValues($values[0]);

            return true;
        }
        return false;
    }

    public function doActivate(): bool
    {
        $database = Database::getInstance();
        $database->where('username', $this->getUsername());

        $now = new Date('u');
        $database->update(
            self::DB_TABLE,
            [
                'activation_date' => $now->sqldatetime(),
            ]
        );

        return true;
    }

    public function createResetRequest(): void
    {
        do {
            $resetHash = md5(UUID::v4()) . md5(UUID::v4());
        } while ($this->resetHashExists($resetHash));
        $exp = new Date('u');
        try {
            $exp->add(new DateInterval('PT' . self::USER_RESET_EXPIRATION . 'S'));
        } catch (Exception) {
        }

        $database = Database::getInstance();
        $database->insert(
            self::DB_TABLE_RESET,
            [
                'user_id' => $this->getId(),
                'hash' => $resetHash,
                'expiration' => $exp->sqldatetime(),
                'use_time' => null,
            ]
        );

        $mail = new Mailer();
        $mail->setReceiver($this->getEmail()->getEmail());
        $mail->setSubject('Passwort zurücksetzen');
        $mail->setTemplate('password');
        $mail->setPreheader('');
        $mail->addTemplateSetting('expiration', (self::USER_RESET_EXPIRATION / 60) . ' Minuten');
        $mail->addTemplateSetting('reseturl', $this->USER_RESET_URL . $resetHash);
        $mail->send();
    }

    public function resetHashExists(string $hash): Error|bool
    {
        $database = Database::getInstance();
        $database->where('hash', $hash);
        $data = $database->get(self::DB_TABLE_RESET);

        return isset($data[0]['hash']);
    }

    public function fetchByResetHash($hash): Error|bool
    {
        $database = Database::getInstance();
        $database->where('hash', $hash);
        $data = $database->get(self::DB_TABLE_RESET);
        if (isset($data[0]['user_id'])) {
            $now = new Date('u');
            try {
                $expiration = new Date($data[0]['expiration']);
                if ($now > $expiration) {
                    return new Error(103103);
                }
                if (!is_null($data[0]['use_time'])) {
                    return new Error(103104);
                }
                $this->loadValues(Database::getRow('id', $data[0]['user_id'], self::DB_TABLE, true));

                return true;
            } catch (Exception) {
                return new Error(103104);
            }
        }
        return new Error(103102);
    }

    public function updatePassword($newPassword): void
    {
        $this->setPasswordRaw($newPassword);
        $database = Database::getInstance();
        $database->where('username', $this->getUsername());
        $database->update(
            self::DB_TABLE,
            [
                'password' => $this->hashPassword(),
            ]
        );
    }

    public function invalidateResetHash($hash): void
    {
        $database = Database::getInstance();
        $database->where('hash', $hash);
        $now = new Date('u');
        $database->update(
            self::DB_TABLE_RESET,
            [
                'use_time' => $now->sqldatetime(),
            ]
        );
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return \IOL\SSO\v1\Enums\Gender
     */
    public function getGender(): Gender
    {
        return $this->gender;
    }

    /**
     * @param \IOL\SSO\v1\Enums\Gender $gender
     */
    public function setGender(Gender $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return \IOL\SSO\v1\DataType\PhoneNumber
     */
    public function getPhonePrivate(): PhoneNumber
    {
        return $this->phonePrivate;
    }

    /**
     * @param \IOL\SSO\v1\DataType\PhoneNumber $phonePrivate
     */
    public function setPhonePrivate(PhoneNumber $phonePrivate): void
    {
        $this->phonePrivate = $phonePrivate;
    }

    /**
     * @return \IOL\SSO\v1\DataType\PhoneNumber
     */
    public function getPhoneBusiness(): PhoneNumber
    {
        return $this->phoneBusiness;
    }

    /**
     * @param \IOL\SSO\v1\DataType\PhoneNumber $phoneBusiness
     */
    public function setPhoneBusiness(PhoneNumber $phoneBusiness): void
    {
        $this->phoneBusiness = $phoneBusiness;
    }

    /**
     * @return \IOL\SSO\v1\DataType\PhoneNumber
     */
    public function getPhoneMobile(): PhoneNumber
    {
        return $this->phoneMobile;
    }

    /**
     * @param \IOL\SSO\v1\DataType\PhoneNumber $phoneMobile
     */
    public function setPhoneMobile(PhoneNumber $phoneMobile): void
    {
        $this->phoneMobile = $phoneMobile;
    }

    /**
     * @return \IOL\SSO\v1\DataType\Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @param \IOL\SSO\v1\DataType\Email $email
     */
    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @return bool|string
     */
    public function getPasswordRaw(): bool|string
    {
        return $this->passwordRaw;
    }

    /**
     * @param string|null $passwordRaw
     */
    public function setPasswordRaw(?string $passwordRaw): void
    {
        $this->passwordRaw = $passwordRaw;
    }

    public function hashPassword(): string
    {
        return password_hash($this->getPasswordRaw(), PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return bool
     */
    public function isPermissionNewsletter(): bool
    {
        return $this->permissionNewsletter;
    }

    /**
     * @param bool $permissionNewsletter
     */
    public function setPermissionNewsletter(bool $permissionNewsletter): void
    {
        $this->permissionNewsletter = $permissionNewsletter;
    }

    /**
     * @return bool
     */
    public function isPermissionAdvertising(): bool
    {
        return $this->permissionAdvertising;
    }

    /**
     * @param bool $permissionAdvertising
     */
    public function setPermissionAdvertising(bool $permissionAdvertising): void
    {
        $this->permissionAdvertising = $permissionAdvertising;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return \IOL\SSO\v1\DataType\Date|null
     */
    public function getActivationDate(): ?Date
    {
        return $this->activationDate;
    }

    /**
     * @param \IOL\SSO\v1\DataType\Date|null $activationDate
     */
    public function setActivationDate(?Date $activationDate): void
    {
        $this->activationDate = $activationDate;
    }

    #[Pure] public function isActivated(): bool
    {
        return !is_null($this->getActivationDate());
    }

    #[Pure] public function getSalutation(bool $complete = false): string
    {
        switch ($complete) {
            case true:
                return match ($this->getGender()->getGender()) {
                    Gender::MALE => 'Sehr geehrter Herr',
                    Gender::FEMALE => 'Sehr geehrte Frau',
                    default => 'Sehr geehrte Damen und Herren'
                };
            case false:
                return match ($this->getGender()->getGender()) {
                    Gender::MALE => 'Herr',
                    Gender::FEMALE => 'Frau',
                    default => ''
                };
        }

        return '';
    }

    public static function loadData(int|string $id, ?string $route = null): array|false
    {
        return Database::getRow($route ?? 'id', $id, self::DB_TABLE);
    }


    #[ArrayShape(['id' => 'int', 'username' => 'string', 'email' => 'string', 'phone' => 'null|string'])]
    public function serializeData(): array
    {
        return [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'email' => $this->getEmail()->getEmail(),
            'phone' => $this->getPhoneBusiness()->international()
        ];
    }
}
