<?php declare(strict_types = 1);

namespace Janderson\JsonRpc;

use JsonException;

class JSONRPC
{
    /* Constants for JSON-RPC 2.0 Errors */
    const int PARSE_ERROR = -32700;
    const int INVALID_REQUEST = -32600;
    const int METHOD_NOT_FOUND = -32601;
    const int INVALID_PARAMS = -32602;
    const int INTERNAL_ERROR = -32603;
}