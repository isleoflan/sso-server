<?php

    declare(strict_types=1);

    namespace IOL\SSO\v1\Entity;

    use IOL\SSO\v1\DataSource\Database;
    use IOL\SSO\v1\Exceptions\NotFoundException;
    use IOL\SSO\v1\Request\APIResponse;

    class App
    {
        public const DB_TABLE = 'app';

        public const HEADER_NAME = 'Iol-App-Token';

        private string $id;
        private string $title;
        private string $description;
        private string $baseUrl;

        /**
         * @throws NotFoundException
         */
        public function __construct(?string $id = null)
        {
            if(!is_null($id)){
                $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
            }
        }

        public static function getCurrent(): App
        {
            try {
                return new App(APIResponse::getRequestHeader(self::HEADER_NAME));
            } catch(NotFoundException){
                APIResponse::getInstance()->addError(999107)->render();
            }
        }

        /**
         * @throws NotFoundException
         */
        private function loadData(array|false $values){

            if(!$values || count($values) === 0){
                throw new NotFoundException('App could not be loaded');
            }

            $this->id = $values['id'];
            $this->title = $values['title'];
            $this->description = $values['description'];
            $this->baseUrl = $values['base_url'];
        }

        /**
         * @return string
         */
        public function getId(): string
        {
            return $this->id;
        }

    }
