<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Tokens;

use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\File;
use IOL\SSO\v1\DataType\Base64;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\Entity\App;
use IOL\SSO\v1\Entity\User;
use IOL\SSO\v1\Exceptions\EncryptionException;
use IOL\SSO\v1\Exceptions\InvalidValueException;
use IOL\SSO\v1\Session\GlobalSession;

class IntermediateToken
{
    public const DB_TABLE = 'intermediate_token';

    private const TOKEN_LIFETIME = 60;

    private int $encryptionBlockSize = 200;
    private int $decryptionBlockSize = 256;

    private string $encryptionKeyPath;
    private string $encryptionKey;
    private string $decryptionKeyPath;
    private string $decryptionKey;

    private array $checksumAlphabet = [
        '+' => 62,
        '1' => 53,
        '0' => 52,
        '3' => 55,
        '2' => 54,
        '5' => 57,
        '4' => 56,
        '7' => 59,
        '6' => 58,
        '9' => 61,
        '8' => 60,
        'A' => 0,
        'C' => 2,
        'B' => 1,
        'E' => 4,
        'D' => 3,
        'G' => 6,
        'F' => 5,
        'I' => 8,
        'H' => 7,
        'K' => 10,
        'J' => 9,
        'M' => 12,
        'L' => 11,
        'O' => 14,
        'N' => 13,
        'Q' => 16,
        'P' => 15,
        'S' => 18,
        'R' => 17,
        'U' => 20,
        'T' => 19,
        'W' => 22,
        'V' => 21,
        'Y' => 24,
        'X' => 23,
        'Z' => 25,
        '/' => 63,
        'a' => 26,
        'c' => 28,
        'b' => 27,
        'e' => 30,
        'd' => 29,
        'g' => 32,
        'f' => 31,
        'i' => 34,
        'h' => 33,
        'k' => 36,
        'j' => 35,
        'm' => 38,
        'l' => 37,
        'o' => 40,
        'n' => 39,
        'q' => 42,
        'p' => 41,
        's' => 44,
        'r' => 43,
        'u' => 46,
        't' => 45,
        'w' => 48,
        'v' => 47,
        'y' => 50,
        'x' => 49,
        'z' => 51,
    ];


    public function __construct()
    {
        $this->encryptionKeyPath = File::getBasePath() . '/intermediatePrivate.pem';
        $this->encryptionKey = file_get_contents($this->encryptionKeyPath);
        $this->decryptionKeyPath = File::getBasePath() . '/intermediatePublic.pem';
        $this->decryptionKey = file_get_contents($this->decryptionKeyPath);
    }


    /**
     * @throws \IOL\SSO\v1\Exceptions\EncryptionException
     */
    public function createNew(App $app, GlobalSession $globalSession): string
    {
        $token = $this->generateToken($app, $globalSession);

        $expiration = new Date('now');

        try {
            $expiration->add(new \DateInterval('PT' . self::TOKEN_LIFETIME . 'S'));
        } catch (\Exception) {
            // do nothing
        }
        $database = Database::getInstance();
        $database->replace(self::DB_TABLE, [
            'app_id' => $app->getId(),
            'user_id' => $globalSession->getId(),
            'token' => $token,
            'expiration' => $expiration->sqldatetime()
        ]);

        return $token;
    }

    /**
     * @throws EncryptionException
     */
    public function generateToken(App $app, GlobalSession $globalSession): string
    {
        $data = ['appId' => $app->getId(), 'gsId' => $globalSession->getId(), 's' => time()];

        $encryptedData = '';
        $plainData = str_split(json_encode($data), $this->encryptionBlockSize);
        foreach ($plainData as $dataChunk) {
            $encryptedChunk = '';
            $encryptionOk = openssl_private_encrypt($dataChunk, $encryptedChunk, $this->encryptionKey);
            if (!$encryptionOk) {
                throw new EncryptionException(
                    'Encryption failed for a chunk of data with message: ' . openssl_error_string()
                );
            }
            $encryptedData .= $encryptedChunk;
        }

        $token = Base64::encode($encryptedData);

        $checksum = $this->calculateChecksum($token);

        return $token . '*' . str_replace('=', '', Base64::encode(dechex($checksum)));
    }

    private function calculateChecksum(string $token): int
    {
        $sum = 0;
        foreach (str_split($token) as $char) {
            $sum += $this->checksumAlphabet[$char] ?? 0;
        }
        return $sum;
    }

    /**
     * @throws InvalidValueException|EncryptionException
     */
    public function checkToken(string $token): array
    {
        [$token, $checksum] = explode('*', $token);

        if (Base64::encode(Base64::decode($token)) !== $token) {
            throw new InvalidValueException('Provided token is not of any valid format');
        }

        $checksum = hexdec(Base64::decode($checksum));

        if ($this->calculateChecksum($token) !== $checksum) {
            throw new InvalidValueException('Provided checksum is not valid');
        }

        $decryptedData = '';
        $encryptedData = str_split(Base64::decode($token), $this->decryptionBlockSize);

        foreach ($encryptedData as $dataChunk) {
            $decryptedChunk = '';

            $decryptionOk = openssl_public_decrypt($dataChunk, $decryptedChunk, $this->decryptionKey);
            if (!$decryptionOk) {
                throw new EncryptionException(
                    'Decryption failed for a chunk of data with message: ' . openssl_error_string()
                );
            }
            $decryptedData .= $decryptedChunk;
        }

        return json_decode($decryptedData, true);
    }
}
