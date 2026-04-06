<?php

namespace Janderson\JsonRpc\Response\Error;

use Janderson\JsonRpc\JSONRPC;

/**
 * The error property within an error response
 *
 * When an RPC call encounters an error, the Response Object MUST contain the
 * error member with a value that is an Object with the following members:
 *
 * `code`
 *   A Number that indicates the error type that occurred.
 *   This MUST be an integer.
 *
 * `message`
 *   A String providing a short description of the error.
 *   The message SHOULD be limited to a concise single sentence.
 *
 * `data`
 *   A Primitive or Structured value that contains additional information about the error.
 *   This may be omitted.
 *   The value of this member is defined by the Server (e.g. detailed error information, nested errors etc.).
 *
 * The error codes from and including -32768 to -32000 are reserved for pre-
 * defined errors. Any code within this range, but not defined explicitly below
 * is reserved for future use. The error codes are nearly the same as those
 * suggested for XML-RPC at the following url:
 * http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php
 *
 * | code             | message          | meaning |
 * |------------------|------------------|---------|
 * | -32700           | Parse error      | Invalid JSON was received by the server. <br />An error occurred on the server while parsing the JSON text.  |
 * | -32600           | Invalid Request  | The JSON sent is not a valid Request object. |
 * | -32601           | Method not found | The method does not exist / is not available. |
 * | -32602           | Invalid params   | Invalid method parameter(s). |
 * | -32603           | Internal error   | Internal JSON-RPC error. |
 * | -32000 to -32099 | Server error     | Reserved for implementation-defined server-errors. |
 */
class Error
{
    public function __construct(
        public int $code,
        public string $message,
        public mixed $data = null,
    ) {}

    public static function read(mixed $incoming): static
    {
        if (is_array($incoming) && !array_is_list($incoming)) {
            $incoming = (object)$incoming;
        };

        return match(true) {
            (!is_object($incoming)) => static::internalError('Invalid error structure in response', $incoming),
            (!property_exists($incoming, 'code') || !is_int($incoming->code)) => static::internalError('Missing or invalid code in response error', $incoming),
            (!property_exists($incoming, 'message') || !is_string($incoming->message)) => static::internalError('Missing or invalid message in response error', $incoming),
            default => new static($incoming->code, $incoming->message, $incoming->data ?? null),
        };
    }

    public static function parseError(string $message, mixed $data = null): self { return new self(JSONRPC::PARSE_ERROR, $message, $data); }
    public static function invalidRequest(string $message, mixed $data = null): self { return new self(JSONRPC::INVALID_REQUEST, $message, $data); }
    public static function methodNotFound(string $message, mixed $data = null): self { return new self(JSONRPC::METHOD_NOT_FOUND, $message, $data); }
    public static function invalidParams(string $message, mixed $data = null): self { return new self(JSONRPC::INVALID_PARAMS, $message, $data); }
    public static function internalError(string $message, mixed $data = null): self { return new self(JSONRPC::INTERNAL_ERROR, $message, $data); }
}