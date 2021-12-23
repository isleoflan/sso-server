<?php

    namespace IOL\SSO\v1\Request;

    class IPAddress
    {
        private ?string $address;

        public function __construct(?string $address = null)
        {
            $this->address = $address ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        }

        public function getAddress(): string
        {
            return $this->address;
        }
    }
