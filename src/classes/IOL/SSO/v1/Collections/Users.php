<?php

namespace IOL\SSO\v1\Collections;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\NotFoundException;

class Users extends Collection
{

    /** @var array<User> $contents */

    /**
     * @param User $user
     */
    public function add(User $user): void
    {
        $this->contents[$this->key()] = $user;
        $this->next();
    }

    /**
     * @return void
     */
    public function fetchAll(): void
    {
        $db = Database::getInstance();
        $data = $db->get(User::DB_TABLE);
        foreach ($data as $userData){
            $user = new User();
            try {
                $user->loadData($userData);
                $this->add($user);
            } catch (NotFoundException) {}

        }
    }

    public function getList(bool $minimal): array
    {
        $return = [];
        /** @var User $user */
        foreach($this->contents as $user){
            $return[$user->getId()] = $user->serialize($minimal);
        }
        return $return;
    }
}