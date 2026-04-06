<?php declare(strict_types=1);

namespace Janderson\JsonRpc;

use InvalidArgumentException;
use Janderson\JsonRpc\Response\Error;
use JsonException;
use JsonSerializable;

/**
 * The request object represented by section 4 (4.0 & 4.1) of the JSON-RPC 2.0 specification
 *
 * This is the parent of the request and notification subtypes
 *
 *  The Request object has the following members:
 *
 *  `jsonrpc`
 *    A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0".
 *
 *  `method`
 *    A String containing the name of the method to be invoked.
 *
 *  `params`
 *    A Structured value that holds the parameter values to be used during the
 *    invocation of the method. This member MAY be omitted.
 *
 *  `id`
 *    An identifier established by the Client that MUST contain a String, Number,
 *    or NULL value if included. If it is not included it is assumed to be a
 *    notification.
 * /
 */
abstract readonly class Request implements JsonSerializable
{
    use Common;

    abstract public function getId(): string|int|float|null;
    abstract public function jsonSerialize(): array;

    public static function notification(string $method, mixed $params = null): Request\Notification
    {
        return new Request\Notification($method, $params);
    }

    public static function request(string $method, mixed $params = null, string|int|float|null $id = null): Request\Request
    {
        return new Request\Request($method, $params, $id);
    }

    /**
     * Parse a JSON string into a Request, Notification, Error, or array of requests (a batch)
     */
    public static function parse(string $json): Request|Error|array
    {
        try {
            return static::read(json_decode($json, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING));
        } catch (JsonException $e) {
            return new Error(Error\Error::parseError($e->getMessage()), null);
        }
    }

    /**
     * Read an incoming object or array as a Request, Notification, or batch
     *
     * @param array|object $incoming The incoming object or array. Other types are not JSON-RPC thus return an Error
     */
    public static function read(mixed $incoming): Request|Error|array
    {
        $return = self::sanitize($incoming, $id);
        if ($return !== null) {
            return $return;
        }

        return $id !== false
            ? new Request\Request($incoming->method, $incoming->params ?? null, $id)
            : new Request\Notification($incoming->method, $incoming->params ?? null);
    }

    private static function sanitize(mixed &$incoming, &$id): null|array|Error
    {
        if (is_array($incoming)) {
            if (empty($incoming)) {
                return new Error(Error\Error::invalidRequest('Empty batch', $incoming), null);
            } elseif (array_is_list($incoming)) {
                return array_map(fn($item) => static::read($item), $incoming);
            }
            $incoming = (object)$incoming;
        } elseif (!is_object($incoming)) {
            return new Error(Error\Error::invalidRequest('Invalid request', $incoming), null);
        }

        $id = self::readId($incoming);
        $error = match (true) {
            ($incoming->jsonrpc ?? null) !== '2.0' => Error\Error::invalidRequest('Invalid JSON-RPC version', $incoming->jsonrpc ?? 'undefined'),
            $id === false && (property_exists($incoming, 'id')) => Error\Error::invalidRequest('Invalid id', $id),
            gettype($incoming->method ?? null) !== 'string' => Error\Error::invalidRequest('Invalid method', $incoming->method ?? 'undefined'),
            (property_exists($incoming, 'params')) && !in_array(gettype($incoming->params ?? null), ['array', 'object']) => Error\Error::invalidParams('Invalid params', $incoming->params ?? 'undefined'),
            default => false,
        };

        if ($error) {
            return new Error($error, $id !== false ? $id : null);
        }

        return null;
    }

    public static function stringify(Request|array $response, bool $validate = true): string
    {
        if ($validate && is_array($response)) {
            foreach ($response as $item) {
                if (!$item instanceof Request) {
                    $foundType = is_object($item) ? $item::class : gettype($item);
                    throw new InvalidArgumentException("Invalid request type in batch: {$foundType}");
                }
            }
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    }
}