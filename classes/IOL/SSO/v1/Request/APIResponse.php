<?php

    declare(strict_types=1);

    namespace IOL\SSO\v1\Request;

    use IOL\SSO\v1\DataSource\Database;
    use IOL\SSO\v1\DataType\Date;
    use IOL\SSO\v1\DataType\UUID;
    use IOL\SSO\v1\Entity\User;
    use IOL\SSO\v1\Enums\RequestMethod;
    use JetBrains\PhpStorm\ArrayShape;
    use JetBrains\PhpStorm\NoReturn;

    class APIResponse
    {
        /**
         * defines the database table, in which the requests are being saved
         *
         * @see \IOL\SSO\v1\Request\APIResponse::$saveRequest
         * @see \IOL\SSO\v1\Request\APIResponse::$saveResponse
         */
        public const DB_TABLE = 'api_requests';

        /**
         * @var \IOL\SSO\v1\Request\APIResponse|null $instance
         *
         * the APIResponse object is handled as a singleton
         * the singleton instance is saved in this property
         */
        protected static ?APIResponse $instance = null;

        /**
         * @var \IOL\SSO\v1\Enums\RequestMethod $allowedRequestMethods
         *
         * this object defines, which HTTP request methods are allowed on the endpoint
         * @see RequestMethod
         */
        private RequestMethod $allowedRequestMethods;

        /**
         * @var bool $authRequired
         *
         * determines, if the endpoint requires authentication beforehand.
         * e.g. the login endpoint does not need authentication, the user detail endpoint does
         */
        private bool $authRequired = true;

        /**
         * @var string $returnType
         *
         * the here set value is used in the "Content-Type" header.
         * this is set to 'application/json', so JS/TS frontends like Angular / Vue / etc. don't need
         * to parse the data into objects
         */
        private /*readonly*/ string $returnType = 'application/json';

        /**
         * @var bool $saveRequest
         *
         * determines, if an image of the request should be saved in the DB
         * this should be set to false in productive environments
         * set this to true, if you need it for debugging
         */
        private bool $saveRequest = true;

        /**
         * @var bool $saveResponse
         *
         * if this is set to true, the data, which gets output to the enduser, is also
         * saved alongside the request data
         *
         * @see \IOL\SSO\v1\Request\APIResponse::$saveRequest needs to be set to true
         */
        private bool $saveResponse = true;


        /**
         * @var string $id
         *
         * ID of the request. this is sent out with the response, so a debugging is possible
         */
        private string $id;

        /**
         * @var \IOL\SSO\v1\DataType\Date $startTime
         *
         * gets set on script startup, is used for saving the request to the database, so the execution time
         * can be calculated and long-lasting requests can be optimized.
         *
         * @see \IOL\SSO\v1\Request\APIResponse::$saveRequest
         */
        private Date $startTime;

        /**
         * @var bool $responseSent
         *
         * this property holds, if the response has already been sent
         * this is important, because there are two possible way, in which the response is sent
         * first is, by manually triggering the render() method
         * the second possibility is, that the render() method is automatically triggered on script shutdown
         * if the render() method has triggered manually, the shutdown function would send out the response a second
         * time
         *
         * @see \IOL\SSO\v1\Request\APIResponse::render()
         * @see _loader.php:29
         */
        private bool $responseSent = false;

        /**
         * @var int $responseCode
         *
         * holds the HTTP response code to be sent with the response
         */
        private int $responseCode = 200;

        /**
         * @var array<Error> $errors
         *
         * array to hold all errors that happen on the way.
         * shall only be filled with Error objects
         */
        private array $errors = [];

        /**
         * @var array|null $data
         *
         * array that holds the data to be sent as the response
         */
        private ?array $data = null;

        /**
         * sets the start time and searches for a new, unused UUID to use as the request
         * ID. If you don't intend to use the request saving as a debug tool, you can safely disable this.
         * Also sends a CORS header as the first thing
         *
         * @see \IOL\SSO\v1\Request\APIResponse::getInstance() Actual instantiation happens here
         */
        protected function __construct()
        {
            $this->startTime = new Date('u');
            $this->setId(UUID::newId('api_requests'));
            $this->sendCORSHeader();
        }

        /**
         * As this object is handled as a singleton, we must prevent the cloning
         */
        protected function __clone()
        {
        }

        /**
         * @return \IOL\SSO\v1\Request\APIResponse
         *
         * returns the actual APIResponse object. If none has been instantiated yet, create a new object and
         * save it in APIResponse::$instance
         */
        public static function getInstance(): APIResponse
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }


        public function needsAuth(bool $needsAuth): void
        {
            $this->authRequired = $needsAuth;
        }

        public function check(): ?User
        {
            $this->checkForOptionsMethod();

            if (!$this->getAllowedRequestMethods()->isAllowed(self::getRequestMethod())) {
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
        ): array {
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
                'GET' => RequestMethod::GET,
                'POST' => RequestMethod::POST,
                'DELETE' => RequestMethod::DELETE,
                'PUT' => RequestMethod::PUT,
                'PATCH' => RequestMethod::PATCH,
                'OPTIONS' => RequestMethod::OPTIONS,
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
            $file = __DIR__.'/../VERSION.vsf';
            if (!file_exists($file)) {
                return 'undef';
            }

            return trim(file_get_contents($file));
        }

        private function sendHeaders(int $httpCode): void
        {
            header('Content-Type: '.$this->returnType.'; charset=utf-8');
            $this->sendCORSHeader();
            http_response_code($httpCode);
        }

        private function getRawRequestData(): array
        {
            if (in_array(self::getRequestMethod(), [
                RequestMethod::GET,
                RequestMethod::DELETE,
            ])) {
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
            if (self::getRequestMethod() === RequestMethod::OPTIONS) {
                $methods = implode(',', array_values($this->getAllowedRequestMethods()->getValues()));
                http_response_code(204);
                header('Allow: OPTIONS'.$methods);
                header('Content-Type: '.$this->returnType.'; charset=utf-8');
                $this->sendCORSHeader();
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
                $database->insert('api_requests', [
                    'id'            => $this->getId(),
                    'method'        => $_SERVER['REQUEST_METHOD'],
                    'endpoint'      => $_SERVER['REQUEST_URI'],
                    'input'         => $requestData,
                    'output_data'   => $this->saveResponse ? json_encode(
                        $this->data
                    ) : '- TRUNCATED -',
                    'output_errors' => json_encode($response['errors'] ?? []),
                    'request_time'  => $this->startTime->format(
                        Date::DATETIME_FORMAT_MICRO
                    ),
                    'response_time' => Date::now(Date::DATETIME_FORMAT_MICRO),
                    'sql_count'     => $database->getQueryCount(),
                    'session_id'    => Authentication::getSessionId(),
                    'url'           => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
                ]);
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
            if (in_array(self::getRequestMethod(), [
                RequestMethod::GET,
                RequestMethod::DELETE,
            ])) {
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

        /**
         * @return \IOL\SSO\v1\Enums\RequestMethod
         */
        public function getAllowedRequestMethods(): RequestMethod
        {
            return $this->allowedRequestMethods;
        }

        /**
         * @param \IOL\SSO\v1\Enums\RequestMethod $allowedRequestMethods
         */
        public function setAllowedRequestMethods(RequestMethod $allowedRequestMethods): void
        {
            $this->allowedRequestMethods = $allowedRequestMethods;
        }

        private function sendCORSHeader(): void
        {
            header('Access-Control-Allow-Origin: '.($_SERVER['HTTP_ORIGIN'] ?? '*')); // TODO: sane CORS
        }

    }
