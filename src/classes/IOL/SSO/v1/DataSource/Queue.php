<?php

namespace IOL\SSO\v1\DataSource;

use IOL\SSO\v1\Enums\QueueType;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Queue
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private QueueType $type;

    public function __construct(QueueType $type)
    {
        $this->type = $type;

        $this->connection = new AMQPStreamConnection(
            host: Environment::get('RMQ_HOST'),
            port: Environment::get('RMQ_PORT'),
            user: Environment::get('RMQ_USER'),
            password: Environment::get('RMQ_PASSWORD')
        );

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->type->getValue(),false, true, false, false);
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    public function closeConnection()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function publishMessage(string|array $message, QueueType $type)
    {
        if(is_array($message)) {
            $message = json_encode($message);
        }
        $message = new AMQPMessage($message);
        $this->channel->basic_publish($message, '', $type->getValue());
    }

    public function addConsumer(callable $callback, QueueType $type)
    {
        $this->channel->basic_consume($type->getValue(), '',false, false, false, false, $callback);
    }

    /**
     * @return \PhpAmqpLib\Channel\AbstractChannel|AMQPChannel
     */
    public function getChannel(): AMQPChannel|\PhpAmqpLib\Channel\AbstractChannel
    {
        return $this->channel;
    }


}