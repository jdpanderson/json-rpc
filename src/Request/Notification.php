<?php declare(strict_types=1);

namespace Janderson\JsonRpc\Request;

use Janderson\JsonRpc\Request as ParentRequest;
use JsonSerializable;

/**
 * The notification object represented by section 4.1 of the JSON-RPC 2.0 specification
 *
 * This is the same as a request with no id property expecting no response.
 */
readonly class Notification extends ParentRequest implements JsonSerializable
{
    /** A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0". */
    public string $jsonrpc;

    public function __construct(
        /** A String containing the name of the method to be invoked. Method names that begin with the word rpc followed by a period character (U+002E or ASCII 46) are reserved for rpc-internal methods and extensions and MUST NOT be used for anything else. */
        public string $method,

        /** A Structured value that holds the parameter values to be used during the invocation of the method. This member MAY be omitted. */
        public array|object|null $params = null,

        /** JSON-RPC version string. MUST be exactly "2.0". */
        string $jsonrpc = '2.0',
    ) {
        $this->jsonrpc = $jsonrpc;
    }

    public function getId(): string|int|float|null
    {
        return null;
    }

    public function jsonSerialize(): array
    {
        return ($this->params === null)
            ? ['jsonrpc' => $this->jsonrpc, 'method' => $this->method]
            : ['jsonrpc' => $this->jsonrpc, 'method' => $this->method, 'params' => $this->params];
    }
}