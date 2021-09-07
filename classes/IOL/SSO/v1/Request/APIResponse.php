<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Request;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use IOL\SSO\v1\Entity\User;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

class APIResponse
{
    public const API_METHOD_GET = 1;
    public const API_METHOD_POST = 2;
    public const API_METHOD_DELETE = 4;
    public const API_METHOD_PUT = 8;
    public const API_METHOD_PATCH = 16;
    public const API_METHOD_OPTIONS = 32;

    protected static ?APIResponse $instance = null;

    private int $allowedMethods = 0;
    private bool $authRequired = true;
    private string $returnType = 'application/json';
    private bool $saveResponse = true;
    private bool $saveRequest = true;

    private string $id;
    private Date $startTime;

    private bool $responseSent = false;
    private int $responseCode = 200;

    /** @var array<Error> */
    private array $errors = [];

    private array|null $data = null;

    protected function __construct()
    {
        $this->startTime = new Date('u');
        $this->setId(UUID::newId('api_requests'));
        header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*')); // TODO
    }

    protected function __clone()
    {
    }

    public static function getInstance(): APIResponse
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addRequestMethod(int $method): void
    {
        if (($method & ($method - 1)) === 0 && $method > 0) {
            if (($this->allowedMethods & $method) === 0) {
                $this->allowedMethods += $method;
            }
        }
    }

    public function getRequestMethods(): array
    {
        $methods = [];
        $methodsInt = $this->allowedMethods;

        foreach (
            [
                self::API_METHOD_OPTIONS,
                self::API_METHOD_PATCH,
                self::API_METHOD_PUT,
                self::API_METHOD_DELETE,
                self::API_METHOD_POST,
                self::API_METHOD_GET,
            ] as $method
        ) {
            if ($methodsInt - $method >= 0) {
                $methodsInt -= $method;
                $methods[$method] = true;
            }
        }

        return $methods;
    }

    public function needsAuth(bool $needsAuth): void
    {
        $this->authRequired = $needsAuth;
    }

    public function check(): ?User
    {
        $this->checkForOptionsMethod();

        if (!in_array(self::getRequestMethod(), array_keys(self::getRequestMethods()))) {
            $this->addError(100004)->render();
        }

        $authResult = Authentication::authenticate();
        if ($this->authRequired) {
            if (!$authResult['success']) {
                $this->addError($authResult['object'])->render();
            }

            return $authResult['object'];
        }

        return null;
    }

    public function addError(int $errorCode): APIResponse
    {
        $this->errors[] = new Error($errorCode);

        return $this;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    #[NoReturn]
    public function render(): void//never
    {
        if ($this->responseSent) {
            die;
        }
        $response = ['data' => null, 'rId' => $this->getId(), 'v' => $this->getAPIVersion()];

        $returnCode = $this->getResponseCode();

        if ($this->hasErrors()) {
            $response['errors'] = [];
            foreach ($this->errors as $error) {
                $response['errors'][] = $error->render();
                $returnCode = $error->getHttpCode();
            }
            $returnCode = count($this->errors) > 1 || $returnCode === 200 ? 400 : $returnCode;
        }

        $response['data'] = $this->data;

        $this->sendHeaders($returnCode);
        $this->sendResponse($response);

        $this->doSaveRequest($response);

        die;
    }

    public function getRequestData(
        #[ArrayShape(['name' => 'string', 'types' => 'array', 'required' => 'bool', 'errorCode' => 'int'])]
        array $parseInfo = []
    ): array
    {
        $requestBody = $this->getRequestBody();

        foreach ($parseInfo as $parseElement) {
            $this->parseElement($parseElement, $requestBody);
        }
        if ($this->hasErrors()) {
            $this->render();
        }

        return $requestBody;
    }

    public static function getRequestMethod(): int
    {
        return match ($_SERVER['REQUEST_METHOD']) {
            'GET' => self::API_METHOD_GET,
            'POST' => self::API_METHOD_POST,
            'DELETE' => self::API_METHOD_DELETE,
            'PUT' => self::API_METHOD_PUT,
            'PATCH' => self::API_METHOD_PATCH,
            'OPTIONS' => self::API_METHOD_OPTIONS,
        };
    }

    public static function getRequestHeader(string $needle): string|null
    {
        foreach (apache_request_headers() as $header => $value) {
            if ($header === $needle) {
                return $value;
            }
        }

        return null;
    }

    public function addData(string $key, $data): void
    {
        if (!is_array($this->data)) {
            $this->data = [];
        }
        $this->data[$key] = $data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isSaveResponse(): bool
    {
        return $this->saveResponse;
    }

    /**
     * @param bool $saveResponse
     */
    public function setSaveResponse(bool $saveResponse): void
    {
        $this->saveResponse = $saveResponse;
    }

    /**
     * @return bool
     */
    public function isSaveRequest(): bool
    {
        return $this->saveRequest;
    }

    /**
     * @param bool $saveRequest
     */
    public function setSaveRequest(bool $saveRequest): void
    {
        $this->saveRequest = $saveRequest;
    }

    private function getAPIVersion(): string
    {
        $file = __DIR__ . '/../VERSION.vsf';
        if (!file_exists($file)) {
            return 'undef';
        }

        return trim(file_get_contents($file));
    }

    private function sendHeaders(int $httpCode): void
    {
        header('Content-Type: ' . $this->returnType . '; charset=utf-8');
        header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
        http_response_code($httpCode);
    }

    private function getRawRequestData(): array
    {
        if (in_array(
            self::getRequestMethod(),
            [
                self::API_METHOD_GET,
                self::API_METHOD_DELETE,
            ]
        )) {
            $requestBody = $_GET;
        } else {
            $requestBody = json_decode(file_get_contents('php://input'), true);
            if (is_null($requestBody)) {
                $requestBody = $_REQUEST;
            }
        }

        return $requestBody;
    }

    private function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function checkForOptionsMethod()
    {
        if (self::getRequestMethod() === self::API_METHOD_OPTIONS) {
            $methods = '';
            foreach (array_keys(self::getRequestMethods()) as $method) {
                $methods .= match ($method) {
                    self::API_METHOD_GET => ',GET',
                    self::API_METHOD_POST => ',POST',
                    self::API_METHOD_DELETE => ',DELETE',
                    self::API_METHOD_PUT => ',PUT',
                    self::API_METHOD_PATCH => ',PATCH',
                    self::API_METHOD_OPTIONS => ',OPTIONS',
                };
            }
            header('HTTP/1.1 204 No Content');
            header('Allow: OPTIONS' . $methods);
            header('Content-Type: ' . $this->returnType . '; charset=utf-8');
            header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
            $this->responseSent = true;
            die;
        }
    }

    private function sendResponse(array $response): void
    {
        echo json_encode($response);
        $this->responseSent = true;
    }

    private function doSaveRequest(array $response)
    {
        if ($this->isSaveRequest()) {
            $requestData = $this->getRawRequestData();
            $this->censorPassword($requestData);
            $requestData = json_encode($requestData);
            $database = Database::getInstance();
            $database->insert(
                'api_requests',
                [
                    'id' => $this->getId(),
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'endpoint' => $_SERVER['REQUEST_URI'],
                    'input' => $requestData,
                    'output_data' => $this->saveResponse ? json_encode($this->data) : '- TRUNCATED -',
                    'output_errors' => json_encode($response['errors'] ?? []),
                    'request_time' => $this->startTime->format(Date::DATETIME_FORMAT_MICRO),
                    'response_time' => Date::now(Date::DATETIME_FORMAT_MICRO),
                    'sql_count' => $database->getQueryCount(),
                    'session_id' => Authentication::getSessionId(),
                    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                ]
            );
        }
    }

    private function censorPassword(array &$requestData): void
    {
        if (isset($requestData['password'])) {
            $requestData['password'] = '** CENSORED **';
        }
    }

    private function getRequestBody(): array
    {
        if (in_array(
            self::getRequestMethod(),
            [
                self::API_METHOD_GET,
                self::API_METHOD_DELETE,
            ]
        )) {
            $requestBody = $_GET;
        } else {
            $requestBody = file_get_contents('php://input');
            if (!$this->isJson($requestBody)) {
                $this->addError(999105)->render();
            }
            $requestBody = json_decode($requestBody, true);
        }

        return $requestBody;
    }

    private function parseElement(mixed $parseElement, array $requestBody)
    {
        if ($parseElement['required'] && !isset($requestBody[$parseElement['name']])) {
            $this->addError($parseElement['errorCode']);
        } elseif (isset($requestBody[$parseElement['name']]) && !in_array(
                gettype($requestBody[$parseElement['name']]),
                $parseElement['types']
            )) {
            $this->addError($parseElement['errorCode']);
        }
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @param int $responseCode
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

}
