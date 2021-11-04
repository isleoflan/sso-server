<?php

    declare(strict_types=1);

    namespace IOL\SSO\v1\DataType;

    use IOL\SSO\v1\DataSource\Database;

    /**
     * UUID class
     *
     * The following class generates VALID RFC 4122 COMPLIANT
     * Universally Unique Identifiers (UUID) version 3, 4 and 5.
     *
     * UUIDs generated validates using OSSP UUID Tool, and output
     * for named-based UUIDs are exactly the same. This is a pure
     * PHP implementation.
     *
     * @author Andrew Moore
     *
     * @link   http://www.php.net/manual/en/function.uniqid.php#94959
     */
    class UUID
    {
        /**
         * Generate v4 UUID
         *
         * Version 4 UUIDs are pseudo-random.
         *
         * @return string
         */
        public static function v4(): string
        {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

                // 32 bits for "time_low"
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),

                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),

                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,

                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,

                // 48 bits for "node"
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
        }

        public static function isValid($uuid): bool
        {
            return preg_match(
                    '/^{?[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}}?$/i',
                    $uuid
                ) === 1;
        }

        public static function idExists(string $uuid, string $table): bool
        {
            $database = Database::getInstance();
            $database->where('id', $uuid);
            $data = $database->get($table);

            return isset($data[0]['id']);
        }

        public static function newId(string $table): string
        {
            do {
                $uuid = UUID::v4();
            } while (UUID::idExists($uuid, $table));

            return $uuid;
        }

    }
