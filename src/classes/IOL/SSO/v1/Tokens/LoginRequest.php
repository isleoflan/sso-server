<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Tokens;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use JetBrains\PhpStorm\Pure;

class LoginRequest
{
    public const DB_TABLE = 'login_request';
    public const DB_TABLE_ALLOC = 'login_request_allocations';

    private string $id;
    private App $app;
    private string $redirectURL;

    /**
     * @throws NotFoundException
     * @throws InvalidValueException
     */
    public function __construct(?string $id = null)
    {
        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Login Request ID');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        }
    }

    /**
     * @throws NotFoundException|InvalidValueException
     */
    private function loadData(array|false $values)
    {

        if (!$values || count($values) === 0) {
            throw new NotFoundException('Login Request could not be loaded');
        }

        $this->id = $values['id'];
        $this->app = new App($values['app_id']);
        $this->redirectURL = $values['redirect_url'];
    }

    public function createNew(App $app, string $redirectURL): string
    {
        $this->app = $app;
        $this->redirectURL = $redirectURL;

        $this->id = UUID::newId(self::DB_TABLE);

        $database = Database::getInstance();
        $database->insert(self::DB_TABLE, [
            'id' => $this->id,
            'app_id' => $this->app->getId(),
            'redirect_url' => $this->redirectURL,
            'created' => Date::now(Date::DATETIME_FORMAT_MICRO),
        ]);

        return $this->id;
    }

    public function redeem(): string
    {
        $database = Database::getInstance();
        $database->where('id', $this->id);
        $database->delete(self::DB_TABLE);
        return $this->redirectURL;
    }

    public function allocate(User $user): void
    {
        $database = Database::getInstance();
        $database->replace(self::DB_TABLE_ALLOC, [
            'login_request_id' => $this->id,
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidValueException
     */
    public function loadAllocation(User $user): void
    {
        $database = Database::getInstance();
        $data = $database->query('SELECT r.* FROM '.self::DB_TABLE_ALLOC.' a INNER JOIN '.self::DB_TABLE.' r ON r.id = a.login_request_id WHERE a.user_id = "'.$user->getId().'"');
        $this->loadData($data[0]);
    }

    #[Pure]
    public function getInfo(): array
    {
        return $this->app->jsonSerialize();
    }

    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}
