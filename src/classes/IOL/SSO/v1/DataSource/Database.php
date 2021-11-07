<?php

declare(strict_types=1);

namespace IOL\SSO\v1\DataSource;

use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\Request\APIResponse;
use IOL\SSO\v1\Request\Error;
use Exception;
use JetBrains\PhpStorm\NoReturn;

class Database extends MysqliDb
{
    private string $SQLERROR_LOG_PATH;
    protected static ?Database $instance = null;
    private int $queryCount = 0;

    protected function __construct()
    {
        parent::__construct(
            host: Environment::get('DB_HOST'),
            username: Environment::get('DB_USER'),
            password: Environment::get('DB_PASSWORD'),
            database: Environment::get('DB_DATABASE')
        );
        $basePath = __DIR__;
        for ($returnDirs = 0; $returnDirs < 4; $returnDirs++) {
            $basePath = substr($basePath, 0, strrpos($basePath, '/'));
        }
        $this->SQLERROR_LOG_PATH = $basePath . '/log/sql/';
    }

    protected function __clone()
    {
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $tableName
     * @param array|int|null $numRows
     * @param string $columns
     *
     * @return Database|array|string
     */
    public function get(string $tableName, array|int|null $numRows = null, string $columns = '*'): Database|array|string
    {
        $this->queryCount++;
        try {
            $queryResult = parent::get($tableName, $numRows, $columns);
            // $this->logQuery();
            return $queryResult;
        } catch (Exception $e) {
            self::handleException($e);
        }
    }

    public static function getRow(
        string $columnName,
               $columnValue,
        string $table
    ): array|false
    {
        $database = Database::getInstance();
        $database->where($columnName, $columnValue);
        $data = $database->get($table, [0, 1]);

        return isset($data[0][$columnName]) ? $data[0] : false;
    }

    public function insert($tableName, $insertData): bool
    {
        $this->queryCount++;
        try {
            return parent::insert($tableName, $insertData);
        } catch (Exception $e) {
            self::handleException($e);
        }
    }

    public function update($tableName, $tableData, $numRows = null): bool
    {
        $this->queryCount++;
        try {
            return parent::update($tableName, $tableData, $numRows);
        } catch (Exception $e) {
            self::handleException($e);
        }
    }

    public function query($query, $numRows = null): array|string
    {
        $this->queryCount++;

        return parent::query($query, $numRows);
    }

    public static function getNextId(string $table, int $start): int
    {
        $database = Database::getInstance();
        /** @noinspection SqlResolve */
        $id = $database->query('SELECT id FROM ' . $table . ' ORDER BY id DESC LIMIT 1');
        if (isset($id[0]['id'])) {
            return $id[0]['id'] + 1;
        }
        return $start;
    }

    /**
     * @return int
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    #[NoReturn] private function handleException(Exception $e): void
    {
        $now = new Date('u');
        $data = "\r\n\r\n[ " . $now->micro() . ' ] EXM: ' . $e->getMessage() . "\r\n";
        $data .= '# SLE: ' . self::getLastError() . "\r\n";
        $data .= '# SLQ: ' . self::getLastQuery();

        if (!file_exists($this->SQLERROR_LOG_PATH . $now->sqldate() . '.log')) {
            touch($this->SQLERROR_LOG_PATH . $now->sqldate() . '.log');
        }
        file_put_contents($this->SQLERROR_LOG_PATH . $now->sqldate() . '.log', $data, FILE_APPEND);

        APIResponse::getInstance()->addData('err', self::getLastError());
        APIResponse::getInstance()->addError(999102)->render();
    }
}
