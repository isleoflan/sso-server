<?php

    namespace IOL\SSO\v1\Content;

    use IOL\SSO\v1\DataType\Email;
    use JetBrains\PhpStorm\ArrayShape;
    use JetBrains\PhpStorm\Pure;

    class Mail implements \JsonSerializable
    {
        private Email $receiver;
        private string $subject;
        private array $attachments = [];
        private string $template;
        private array $variables = [];

        /**
         * @param \IOL\SSO\v1\DataType\Email $receiver
         */
        public function setReceiver(Email $receiver): void
        {
            $this->receiver = $receiver;
        }

        /**
         * @param string $subject
         */
        public function setSubject(string $subject): void
        {
            $this->subject = $subject;
        }

        /**
         * @param array $attachments
         */
        public function setAttachments(array $attachments): void
        {
            $this->attachments = $attachments;
        }

        /**
         * @param string $attachment
         */
        public function addAttachment(string $attachment): void
        {
            $this->attachments[] = $attachment;
        }

        /**
         * @param string $template
         */
        public function setTemplate(string $template): void
        {
            $this->template = $template;
        }

        /**
         * @param array $variables
         */
        public function setVariables(array $variables): void
        {
            $this->variables = $variables;
        }

        /**
         * @param string $key
         * @param string $value
         */
        public function addVariable(string $key, string $value): void
        {
            $this->variables[$key] = $value;
        }

        #[ArrayShape([
            'receiver'    => "\IOL\SSO\v1\DataType\Email",
            'subject'     => "string",
            'attachments' => "array",
            'template'    => "string",
            'variables'   => "array",
        ])]
        public function serialize(): array
        {
            return [
                'receiver'    => $this->receiver,
                'subject'     => $this->subject,
                'attachments' => $this->attachments,
                'template'    => $this->template,
                'variables'   => $this->variables,
            ];
        }

        #[Pure] #[ArrayShape([
            'receiver'    => "\IOL\SSO\v1\DataType\Email",
            'subject'     => "string",
            'attachments' => "array",
            'template'    => "string",
            'variables'   => "array",
        ])]
        public function jsonSerialize()
        {
            return $this->serialize();
        }
    }
