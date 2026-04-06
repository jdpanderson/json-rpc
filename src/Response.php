<?php declare(strict_types=1);

namespace Janderson\JsonRpc;

use DomainException;
use Janderson\JsonRpc\Response\Error;
use JsonException;
use Throwable;

/**
 * The response object represented by section 5 (5.0 & 5.1) of the JSON-RPC 2.0 specification
 *
 * This is the parent of the response and error subtypes
 *
 * When an RPC call is made, the Server MUST reply with a Response, except for
 * in the case of Notifications. The Response is expressed as a single JSON
 * Object, with the following members:
 *
 * `jsonrpc`
 *   A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0".
 *
 * `result`
 *   This member is REQUIRED on success.
 *   This member MUST NOT exist if there was an error invoking the method.
 *   The value of this member is determined by the method invoked on the Server.
 *
 * `id`
 *   This member is REQUIRED.
 *   It MUST be the same as the value of the id member in the Request Object.
 *   If there was an error in detecting the id in the Request object (e.g.
 *   Parse error/Invalid Request), it MUST be Null.
 *
 * Either the result member or error member MUST be included, but both members MUST NOT be included.
 */
abstract readonly class Response
{
    use Common;

    public static function fromResult(mixed $result, Request $request): Response\Response|null
    {
        return $request->isNotification()
            ? null
            : new Response\Response($result, $request->id, $request->jsonrpc);
    }

    public static function fromError(Error\Error $error, Request $request): Response\Error
    {
        return new Response\Error($error, $request->id, $request->jsonrpc);
    }

    public static function fromThrowable(Throwable $e, Request|null $request = null, bool $trace = false): self
    {
        return new Response\Error(new Error\Error($e->getCode(), $e->getMessage(), $trace ? $e->getTrace() : null), $request->getId());
    }

    /**
     * Parse a JSON string into a Response, Error, or array of responses (a batch)
     */
    public static function parse(string $json): Response|Error|array|null
    {
        try {
            return static::read(json_decode($json, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING));
        } catch (JsonException $e) {
            return new Error(Error\Error::parseError($e->getMessage()), null);
        }
    }

    /**
     * Read an incoming object as a Response
     */
    public static function read(mixed $incoming): Response|array
    {
        $return = static::sanitize($incoming);
        if ($return !== null) {
            return $return;
        }

        return new Response\Response($incoming->result, $incoming->id);
    }

    private static function sanitize(mixed &$incoming, bool $loose = false): mixed
    {
        if (is_array($incoming)) {
            if (array_is_list($incoming)) {
                return array_map(fn($item) => static::read($item), $incoming); // batch
            }
            $incoming = (object)$incoming;
        } elseif (!is_object($incoming)) {
            return new Error(Error\Error::internalError('Invalid type in response', $incoming), null);
        }

        $id = self::readId($incoming);
        $error = match (true) {
            (property_exists($incoming, 'error') && !$loose) || !empty($incoming->error ?? null) => Error\Error::read($incoming->error),
            ($id === false) => Error\Error::internalError('Invalid id in response', $incoming->id ?? 'undefined'),
            ($incoming->jsonrpc ?? null) !== '2.0' => Error\Error::internalError('Invalid JSON-RPC version in response', $incoming->jsonrpc ?? 'undefined'),
            (!property_exists($incoming, 'result')) => Error\Error::internalError('Neither result nor error are present in response', $incoming),
            default => null,
        };

        return $error ? new Error($error, $id ?: null) : null;
    }

    public static function stringify(Response|Error|array $response, bool $validate = true): string
    {
        if ($validate && is_array($response)) {
            foreach ($response as &$item) {
                if (!$item instanceof Response) {
                    $item = new Error(Error\Error::internalError('Invalid response'), null);
                }
            }
        }
        return json_encode($response, JSON_THROW_ON_ERROR);
    }
}