<?php

    namespace IOL\SSO\v1\Tokens;

    use IOL\SSO\v1\DataSource\Database;
    use IOL\SSO\v1\DataSource\RandomString;
    use IOL\SSO\v1\Exceptions\InvalidValueException;
    use IOL\SSO\v1\Exceptions\NotFoundException;
    use IOL\SSO\v1\Request\Session;

    class RefreshToken
    {
        public const DB_TABLE = 'refresh_token';

        private string $token;
        private Session $session;


        /**
         * @throws NotFoundException
         * @throws InvalidValueException
         */
        public function __construct(?string $token = null)
        {
            if (!is_null($token)) {
                if (!RandomString::isValid($token)) {
                    throw new InvalidValueException('Invalid Refresh Token');
                }
                $this->loadData(Database::getRow('token', $token, self::DB_TABLE));
            }
        }

        /**
         * @throws NotFoundException
         */
        private function loadData(array|false $values)
        {

            if (!$values || count($values) === 0) {
                throw new NotFoundException('Refresh Token could not be found');
            }

            $this->token = $values['token'];
            $this->session = new Session($values['session_id']);
        }

        public function createNew(Session $session): string
        {
            $this->session = $session;
            $this->token = RandomString::newId(self::DB_TABLE, 'token', 80);

            $database = Database::getInstance();
            $database->insert(self::DB_TABLE, [
                'token' => $this->token,
                'session_id' => $this->session->getId(),
            ]);

            return $this->token;
        }
    }
