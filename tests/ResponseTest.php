<?php

namespace Janderson\JsonRpc\Test;

use Janderson\JsonRpc\Response;
use Janderson\JsonRpc\Response\Error;
use Janderson\JsonRpc\Request;
use Janderson\JsonRpc\JSONRPC;
use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
#[UsesClass(Request\Request::class)]
#[UsesClass(Response\Error::class)]
#[UsesClass(Error\Error::class)]
#[UsesClass(Response\Response::class)]
class ResponseTest extends TestCase
{
    #[DataProvider('stringifyProvider')]
    public function testStringify(array|Response $output, array|object $expected): void
    {
        $json = Response::stringify($output);
        if ($output instanceof JsonSerializable) {
            $this->assertEquals($expected, (object)$output->jsonSerialize());
        }

        // Test __toString auto-stringify
        if ($output instanceof Response) {
            $this->assertEquals(json_encode($expected), (string)$output);
        }
        $this->assertJsonStringEqualsJsonString(json_encode($expected), $json);
    }

    public static function stringifyProvider(): array
    {
        $response = new Response\Response(123, 234);
        $responseObj = (object)["jsonrpc" => "2.0", "result" => 123, "id" => 234];
        $error = new Error(Error\Error::methodNotFound("Method 'foo' not found"), 123);
        $errorObj = (object)["jsonrpc" => "2.0", "error" => $error->error, "id" => 123];
        $invalidResponse = new Error(Error\Error::internalError("Invalid response"), null);
        return [
            'response' => [$response, $responseObj],
            'error' => [$error, $errorObj],
            'batch' => [[$response, $error], [$responseObj, $errorObj]],
            'empty batch' => [[], []],
            'invalid entry in batch' => [["foo"], [$invalidResponse]],
        ];
    }

    #[DataProvider('readProvider')]
    public function testRead(mixed $incoming, string|array $expectedClass, ?int $expectedErrorCode = null): void
    {
        $result = Response::read($incoming);

        if (is_array($expectedClass)) {
            // Batch
            $this->assertIsArray($result);
            $this->assertCount(count($expectedClass), $result);
            foreach ($expectedClass as $i => $class) {
                $this->assertInstanceOf($class, $result[$i]);
            }
        } else {
            // Single
            $this->assertInstanceOf($expectedClass, $result);
            if ($result instanceof Error && $expectedErrorCode !== null) {
                $this->assertSame($expectedErrorCode, $result->error->code);
            }
        }
    }

    public static function readProvider(): array
    {
        return [
            'valid response' => [
                (object)['jsonrpc' => '2.0', 'result' => 123, 'id' => 1],
                Response\Response::class
            ],
            'valid response in array format' => [
                ['jsonrpc' => '2.0', 'result' => 123, 'id' => 1],
                Response\Response::class
            ],
            'valid error response' => [
                (object)['jsonrpc' => '2.0', 'error' => ['code' => -32601, 'message' => 'Method not found'], 'id' => 1],
                Error::class
            ],
            'valid batch' => [
                [
                    (object)['jsonrpc' => '2.0', 'result' => 7, 'id' => '1'],
                    (object)['jsonrpc' => '2.0', 'result' => 19, 'id' => '2'],
                ],
                [Response\Response::class, Response\Response::class]
            ],
            'mixed batch' => [
                [
                    (object)['jsonrpc' => '2.0', 'result' => 7, 'id' => '1'],
                    (object)['jsonrpc' => '2.0', 'error' => ['code' => -32601, 'message' => 'Method not found'], 'id' => '2'],
                ],
                [Response\Response::class, Error::class]
            ],
            'empty batch' => [
                [],
                []
            ],
            'invalid type (string)' => [
                'invalid',
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
            'invalid id type' => [
                (object)['jsonrpc' => '2.0', 'result' => 123, 'id' => []],
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
            'missing jsonrpc' => [
                (object)['result' => 123, 'id' => 1],
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
            'wrong jsonrpc version' => [
                (object)['jsonrpc' => '1.0', 'result' => 123, 'id' => 1],
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
            'neither result nor error' => [
                (object)['jsonrpc' => '2.0', 'id' => 1],
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
            'invalid error format (string)' => [
                (object)['jsonrpc' => '2.0', 'error' => 'not an object', 'id' => 1],
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
            'invalid error format (list)' => [
                (object)['jsonrpc' => '2.0', 'error' => [1, 2, 3], 'id' => 1],
                Error::class,
                JSONRPC::INTERNAL_ERROR
            ],
        ];
    }

    #[DataProvider('parseProvider')]
    public function testParse(string $json, string|array $expectedClass, ?int $expectedErrorCode = null): void
    {
        $result = Response::parse($json);

        if (is_array($expectedClass)) {
            // Batch
            $this->assertIsArray($result);
            $this->assertCount(count($expectedClass), $result);
            foreach ($expectedClass as $i => $class) {
                $this->assertInstanceOf($class, $result[$i]);
            }
        } else {
            // Single
            $this->assertInstanceOf($expectedClass, $result);
            if ($result instanceof Error && $expectedErrorCode !== null) {
                $this->assertSame($expectedErrorCode, $result->error->code);
            }
        }
    }

    public static function parseProvider(): array
    {
        return [
            'valid response' => [
                '{"jsonrpc": "2.0", "result": 123, "id": 1}',
                Response\Response::class
            ],
            'valid error response' => [
                '{"jsonrpc": "2.0", "error": {"code": -32601, "message": "Method not found"}, "id": 1}',
                Error::class
            ],
            'valid batch' => [
                '[{"jsonrpc": "2.0", "result": 7, "id": "1"}, {"jsonrpc": "2.0", "result": 19, "id": "2"}]',
                [Response\Response::class, Response\Response::class]
            ],
            'invalid json' => [
                '{"jsonrpc": "2.0", "result": 123, "id": 1',
                Error::class,
                JSONRPC::PARSE_ERROR
            ],
            'empty string' => [
                '',
                Error::class,
                JSONRPC::PARSE_ERROR
            ],
        ];
    }

    public function testRequestNotInstantiable(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageMatches("/^Cannot instantiate abstract class.*/");
        $class = Response::class; // Gymnastics to work around inspection error
        new $class();
    }

    #[DataProvider('isMethodsProvider')]
    public function testIsMethods(Response|Error|Request $object, bool $isRequest, bool $isResponse, bool $isError, bool $isNotification): void
    {
        $this->assertSame($isRequest, $object->isRequest());
        $this->assertSame($isResponse, $object->isResponse());
        $this->assertSame($isError, $object->isError());
        $this->assertSame($isNotification, $object->isNotification());
    }

    public static function isMethodsProvider(): array
    {
        $response = new Response\Response('result', 1);
        $error = new Error(Error\Error::internalError('error'), 1);

        return [
            'response object' => [$response, false, true, false, false],
            'error object' => [$error, false, true, true, false],
        ];
    }

    #[DataProvider('fromResultProvider')]
    public function testFromResult(mixed $result, Request $request, string|null $expectedClass): void
    {
        $response = Response::fromResult($result, $request);
        if ($expectedClass === null) {
            $this->assertNull($response);
        } else {
            $this->assertInstanceOf($expectedClass, $response);
        }
    }

    public static function fromResultProvider(): array
    {
        $request = Request\Request::parse('{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}');
        $notification = Request\Notification::parse('{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23]}');
        return [
            'valid response' => [42 - 23, $request, Response\Response::class],
            'invalid response' => [42 - 23, $notification, null],
        ];
    }

    public function testFromError(): void
    {
        $request = new Request\Request('testMethod', ['foo' => 'bar'], 123);
        $errorObject = new Error\Error(-32601, 'Method not found');

        $errorResponse = Response::fromError($errorObject, $request);

        $this->assertInstanceOf(Error::class, $errorResponse);
        $this->assertSame($errorObject, $errorResponse->error);
        $this->assertSame(123, $errorResponse->id);
        $this->assertSame('2.0', $errorResponse->jsonrpc);
    }

    #[DataProvider('fromThrowableProvider')]
    public function testFromThrowable(bool $trace): void
    {
        $request = new Request\Request('testMethod', ['foo' => 'bar'], 123);
        $exception = new \Exception('Method not found', -32601);

        $errorResponse = Response::fromThrowable($exception, $request, $trace);

        $this->assertInstanceOf(Error::class, $errorResponse);
        $this->assertSame($exception->getCode(), $errorResponse->error->code);
        $this->assertSame($exception->getMessage(), $errorResponse->error->message);
        $this->assertSame(123, $errorResponse->id);
        $this->assertSame('2.0', $errorResponse->jsonrpc);
        $trace ? $this->assertIsArray($errorResponse->error->data) : $this->assertEmpty($errorResponse->error->data);
    }

    public static function fromThrowableProvider(): array
    {
        return [
            'with trace' => [true],
            'without trace' => [false],
        ];
    }
}
