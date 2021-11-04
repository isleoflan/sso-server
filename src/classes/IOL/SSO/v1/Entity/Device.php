<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Entity;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\RandomString;
use IOL\SSO\v1\Request\APIResponse;

class Device
{
    public const DB_TABLE = 'known_devices';
    public const DEVICE_HEADER = 'Iol-Device-Id';

    private string $id;

    public function createNew(User $user): string
    {
        $this->id = RandomString::generate(64);

        $database = Database::getInstance();
        $database->insert(self::DB_TABLE, [
            'user_id' => $user->getId(),
            'device_id' => $this->id
        ]);

        return $this->id;
    }

    public function getCurrent()
    {
        $deviceId = APIResponse::getRequestHeader(self::DEVICE_HEADER);
        if(is_null($deviceId)){
            return false;
        }
        $database = Database::getInstance();
        $database->where('device_id', $deviceId);
    }


}