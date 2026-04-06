<?php declare(strict_types=1);

namespace Janderson\JsonRpc\Response;

use Janderson\JsonRpc\Response as ParentResponse;

/**
 * The error object represented by section 5.1 of the JSON-RPC 2.0 specification
 */
readonly class Error extends ParentResponse
{
    /** A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0". */
    public string $jsonrpc;

    public function __construct(
        public Error\Error $error,

        /** An identifier established by the client that MUST contain a String, Number, or NULL value */
        public string|int|float|null $id,

        /** JSON-RPC version string. MUST be exactly "2.0". */
        string $jsonrpc = '2.0',
    ) {
        $this->jsonrpc = $jsonrpc;
    }

    public function equals(Response|Error|null $response)
    {
        return !((
            !$response instanceof Error
            || $this->jsonrpc !== $response->jsonrpc
            || $this->id !== $response->id
            || json_encode($this->error) !== json_encode($response->error)
        ));
    }
}