<?php

declare(strict_types=1);

use IOL\SSO\v1\DataSource\Queue;
use IOL\SSO\v1\Enums\QueueType;

$basePath = __DIR__;
for ($returnDirs = 0; $returnDirs < 1; $returnDirs++) {
    $basePath = substr($basePath, 0, strrpos($basePath, '/'));
}


require_once $basePath . '/_loader.php';

$userQueue = new Queue(new QueueType(QueueType::ALL_USER));
$userQueue->addConsumer(
    callback: static function (\PhpAmqpLib\Message\AMQPMessage $message): void {
        echo '[o] New Message on queue "' . QueueType::NEW_USER . '": ' . $message->body . "\r\n";
        try {
            $user = new \IOL\SSO\v1\Entity\User(id: $message->body);
        } catch (Exception $e) {
            // User can not be found. Reject the message and if this happens the first time, requeue it.
            $message->reject(!$message->isRedelivered());
            echo '[!] Got error: ' . $e->getMessage() . "\r\n";
            return;
        }
        $user->sendConfirmationMail();
        echo '[x] Sent Confirmation Mail for message ' . $message->body . "\r\n\r\n";
        $message->ack();
    },
    type: new QueueType(QueueType::NEW_USER)
);

$userQueue->addConsumer(
    callback: static function (\PhpAmqpLib\Message\AMQPMessage $message): void {
        echo '[o] New Message on queue "' . QueueType::RESET_USER . '": ' . $message->body . "\r\n";
        try {
            $reset = new \IOL\SSO\v1\Entity\Reset(id: $message->body);
        } catch (Exception $e) {
            // User can not be found. Reject the message and if this happens the first time, requeue it.
            $message->reject(!$message->isRedelivered());
            echo '[!] Got error: ' . $e->getMessage() . "\r\n";
            return;
        }

        $reset->sendResetMail();

        echo '[x] Sent Reset Mail for message ' . $message->body . "\r\n\r\n";
        $message->ack();
    },
    type: new QueueType(QueueType::RESET_USER)
);

while ($userQueue->getChannel()->is_open()) {
    $userQueue->getChannel()->wait();
}