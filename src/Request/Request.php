<?php declare(strict_types=1);

namespace Janderson\JsonRpc\Request;

use Janderson\JsonRpc\Request as ParentRequest;
use JsonSerializable;

/**
 * The request object represented by section 4.0 of the JSON-RPC 2.0 specification
 *
 * This is the implementation of the request object subtype that does not have an error property
 */
readonly class Request extends ParentRequest implements JsonSerializable
{
    /** A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0". */
    public string $jsonrpc;

    public function __construct(
        /** A String containing the name of the method to be invoked. Method names that begin with the word rpc followed by a period character (U+002E or ASCII 46) are reserved for rpc-internal methods and extensions and MUST NOT be used for anything else. */
        public string $method,

        /** A Structured value that holds the parameter values to be used during the invocation of the method. This member MAY be omitted. */
        public array|object|null $params = null,

        /** @var string|int|float|null An identifier established by the Client that MUST contain a String, Number, or NULL value */
        public string|int|float|null $id = null,

        /** JSON-RPC version string. MUST be exactly "2.0". */
        string $jsonrpc = '2.0',
    ) {
        $this->jsonrpc = $jsonrpc;
    }

    public function getId(): string|int|float|null
    {
        return $this->id;
    }

    public function jsonSerialize(): array
    {
        return ($this->params === null)
            ? ['jsonrpc' => $this->jsonrpc, 'method' => $this->method, 'id' => $this->id]
            : ['jsonrpc' => $this->jsonrpc, 'method' => $this->method, 'params' => $this->params, 'id' => $this->id];
    }
}