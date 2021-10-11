<?php

    namespace IOL\SSO\v1\Tokens;

    use IOL\SSO\v1\BitMasks\Scope;
    use IOL\SSO\v1\DataSource\Database;
    use IOL\SSO\v1\DataType\UUID;
    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Exceptions\InvalidValueException;
    use IOL\SSO\v1\Exceptions\NotFoundException;

    class LoginRequest
    {
        public const DB_TABLE = 'login_request';

        private string $id;
        private App $app;
        private string $redirectURL;
        private Scope $scope;

        /**
         * @throws \IOL\SSO\v1\Exceptions\NotFoundException
         * @throws \IOL\SSO\v1\Exceptions\InvalidValueException
         */
        public function __construct(?string $id = null)
        {
            if(!is_null($id)){
                if(!UUID::isValid($id)){
                    throw new InvalidValueException('Invalid Login Request ID');
                }
                $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
            }
        }

        /**
         * @throws \IOL\SSO\v1\Exceptions\NotFoundException|\IOL\SSO\v1\Exceptions\InvalidValueException
         */
        private function loadData(array|false $values){

            if(!$values || count($values) === 0){
                throw new NotFoundException('Login Request could not be loaded');
            }

            $this->id = $values['id'];
            $this->app = new App($values['app_id']);
            $this->redirectURL = $values['redirect_url'];
            $this->scope = new Scope($values['scope']);
        }

        public function createNew(App $app, string $redirectURL, Scope $scope) : string
        {
            $this->app = $app;
            $this->redirectURL = $redirectURL;
            $this->scope = $scope;

            $this->id = UUID::newId(self::DB_TABLE);

            $database = Database::getInstance();
            $database->insert(self::DB_TABLE, [
                'id'            => $this->id,
                'app_id'        => $this->app->getId(),
                'redirect_url'  => $this->redirectURL,
                'scope'         => $this->scope->getIntegerValue(),
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
    }
