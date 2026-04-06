# JSON-RPC 2.0

A library implementing the JSON-RPC 2.0 specification. Another one.

## Usage

### Server

On the server, a request should be read to ensure validity, dispatched, and a response produced.

```php
use Janderson\JsonRpc;

$json = '{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}';
$request = JsonRpc\Request::parse($json); // or ::read($object) with a parsed object or array

$response = is_array($request)
    ? array_filter(array_map(dispatch(...), $request))
    : dispatch($request);
    
echo json_encode($response);

function dispatch(JsonRpc\Request|JsonRpc\Response $request): JsonRpc\Response|null
{
    // Request error directly produces an error response
    if ($request->isError()) {
        return $request;
    }
    
    try {
        $result = call_your_function_of_choice($request->method, ...$request->params);
        return JsonRpc\Response::fromResult($result, $request); // Response (result) subtype
    } catch (\Exception $e) {
        return JsonRpc\Response::fromThrowable($e, $request); // Error subtype
    }
}
```

### Client

The client should only have to build a request and handle the response.

```php
use Janderson\JsonRpc;

$request = JsonRpc\Request::request('subtract', [2, 1]);
$response = JsonRpc\Response::parse($client->send($request)); // HTTP client, or some other transport
if ($response->isError()) {
    throw new Exception($response->error->message, $response->error->code);
}
echo "Result is {$response->result}\n";

```

## Structure

The structure of the library's classes attempts to follow the JSON-RPC 2.0 specification as closely as possible.
 - `Request` and `Response` are the main classes for requests and responses
 - `Request` has subtypes `Request\Request` and `Request\Response`
 - `Response` has subtypes `Response\Response` and `Response\Error`
 - The error field in `Response\Error` has its own type: `Response\Error\Error`