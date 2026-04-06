<?php declare(strict_types=1);

namespace Janderson\JsonRpc\Response;

/**
 * The response object represented by section 5.0 of the JSON-RPC 2.0 specification
 */
readonly class Response extends \Janderson\JsonRpc\Response
{
    public string $jsonrpc;

    public function __construct(
        public mixed $result,
        public string|int|float|null $id,
        string $jsonrpc = '2.0',
    ) {
        $this->jsonrpc = $jsonrpc;
    }

    public function equals(Response|Error|null $response)
    {
        return !((
            !$response instanceof Response
            || $this->jsonrpc !== $response->jsonrpc
            || $this->id !== $response->id
            || (is_scalar($this->result) && $this->result !== $response->result)
            || json_encode($this->result) !== json_encode($response->result)
        ));
    }
}