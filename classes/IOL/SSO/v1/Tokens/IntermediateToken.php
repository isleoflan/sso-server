<?php

    namespace IOL\SSO\v1\Tokens;

    use IOL\SSO\v1\DataSource\File;
    use IOL\SSO\v1\Entity\App;
    use IOL\SSO\v1\Entity\User;
    use IOL\SSO\v1\Exceptions\EncryptionException;

    class IntermediateToken
    {
        public const DB_TABLE = 'intermediate_token';

        private int $encryptionBlockSize = 200;
        private int $decryptionBlockSize = 256;

        private string $encryptionKeyPath;
        private string $encryptionKey;


        public function __construct()
        {
            $this->encryptionKeyPath = File::getBasePath().'/intermediateTokenKey.pem';
            $this->encryptionKey = file_get_contents($this->encryptionKeyPath);
        }

        /**
         * @throws EncryptionException
         */
        public function createNew(App $app, User $user)
        {
            $token = $this->generateToken($app, $user);
        }

        /**
         * @throws EncryptionException
         */
        public function generateToken(App $app, User $user): string
        {
            $data = ['appId' => $app->getId(), 'userId' => $user->getId()];

            $encryptedData = '';
            $plainData = str_split(json_encode($data), $this->encryptionBlockSize);
            foreach($plainData as $dataChunk)
            {
                $encryptedChunk = '';
                $encryptionOk = openssl_private_encrypt($dataChunk, $encryptedChunk, $this->encryptionKey);
                if(!$encryptionOk) { throw new EncryptionException('Encryption failed for a chunk of data with message: '.openssl_error_string()); }
                $encryptedData .= $encryptedChunk;
            }

            return base64_encode($encryptedData);
        }
    }
