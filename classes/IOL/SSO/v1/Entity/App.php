<?php

    declare(strict_types=1);

    namespace IOL\SSO\v1\Entity;

    use IOL\SSO\v1\DataSource\Database;
    use IOL\SSO\v1\Exceptions\EmptyObjectException;

    class App
    {
        public const DB_TABLE = 'app';

        private string $id;
        private string $title;
        private string $description;
        private string $baseUrl;

        /**
         * @throws \IOL\SSO\v1\Exceptions\EmptyObjectException
         */
        public function __construct(?string $id = null)
        {
            if(!is_null($id)){
                $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
            }
        }

        private function loadData(array $values){

            if(count($values) === 0){
                throw new EmptyObjectException('App could not be loaded');
            }

            $this->id = $values['id'];
            $this->title = $values['title'];
            $this->description = $values['description'];
            $this->baseUrl = $values['base_url'];
        }
    }
