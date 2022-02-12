<?php

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Exceptions\NotFoundException;
use JetBrains\PhpStorm\ArrayShape;

class Squad implements \JsonSerializable
{
    public const DB_TABLE = 'squads';

    private string $id;
    private string $short;
    private string $name;


    /**
     * @throws InvalidValueException|NotFoundException
     */
    public function __construct(?string $id = null)
    {
        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Squad ID');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        }
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
        $this->short = $values['short'];
        $this->name = $values['name'];
    }

    public function createNew(string $short, string $name): void
    {
        $this->id = UUID::newId(self::DB_TABLE);
        $this->short = $short;
        $this->name = $name;

        $database = Database::getInstance();
        $database->insert(self::DB_TABLE, [
            'id' => $this->id,
            'short' => $this->short,
            'name' => $this->name
        ]);
    }

    #[ArrayShape(['id' => "string", 'short' => "string", 'name' => "string"])]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'short' => $this->short,
            'name' => $this->name
        ];
    }
}