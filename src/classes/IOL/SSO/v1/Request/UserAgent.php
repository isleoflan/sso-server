<?php

    namespace IOL\SSO\v1\Request;

    class UserAgent
    {
        private ?string $agent;

        public function __construct(?string $agent = null)
        {
            $this->agent = $agent ?? $_SERVER['HTTP_USER_AGENT'];
        }

        public function getAgent(): string
        {
            return $this->agent;
        }
    }
