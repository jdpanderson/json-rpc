<?php

namespace Janderson\JsonRpc;

use Janderson\JsonRpc\Request\Notification;
use Janderson\JsonRpc\Response\Error;
use JsonException;

trait Common
{
    public function isRequest(): bool
    {
        return $this instanceof Request;
    }

    public function isResponse(): bool
    {
        return $this instanceof Response;
    }

    public function isError(): bool
    {
        return $this instanceof Error;
    }

    public function isNotification(): bool{
        return $this instanceof Notification;
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return self::stringify($this);
    }

    /**
     * @return int|float|string|bool|null Returns false if an ID couldn't be found
     */
    private static function readId(object $incoming): int|float|string|null|bool
    {
        return ((property_exists($incoming, 'id')) && in_array(gettype($incoming->id), ["integer", "double", "string", "NULL"])) ? $incoming->id : false;
    }
}
