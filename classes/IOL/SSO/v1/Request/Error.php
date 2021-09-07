<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Request;

use IOL\SSO\v1\DataSource\File;
use JetBrains\PhpStorm\ArrayShape;

class Error
{
    public function __construct(
        private int $errorCode,
        private ?string $message = null,
        private ?int $httpCode = null
    ) {
        if (is_null($message) || is_null($httpCode)) {
            $this->lookup();
        }
    }

    #[ArrayShape(['errorCode' => 'int', 'message' => 'string'])]
    public function render(): array
    {
        return [
            'errorCode' => $this->errorCode,
            'message' => $this->message,
        ];
    }

    /**
     * @return int|null
     */
    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    private function lookup(): void
    {
        $errorFileBase = File::getBasePath().'/i18n/errors/';
        $errorLanguage = 'en';
        $errorFile = $errorFileBase.$errorLanguage.'.json';
        $lookupTable = json_decode(file_get_contents($errorFile), true);

        if (isset($lookupTable[$this->errorCode])) {
            $this->message = $this->message ?? $lookupTable[$this->errorCode]['message'];
            $this->httpCode = $this->httpCode ?? $lookupTable[$this->errorCode]['httpCode'];
        }
    }
}
