<?php

namespace Janderson\JsonRpc\Test\Error;

use Janderson\JsonRpc\JSONRPC;
use Janderson\JsonRpc\Response\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Error\Error::class)]
class ErrorTest extends TestCase
{
    public function testConstructor(): void
    {
        $error = new Error\Error(-32000, 'Server error', ['foo' => 'bar']);
        $this->assertSame(-32000, $error->code);
        $this->assertSame('Server error', $error->message);
        $this->assertSame(['foo' => 'bar'], $error->data);
    }

    public function testConstructorWithNullData(): void
    {
        $error = new Error\Error(-32001, 'Another error');
        $this->assertSame(-32001, $error->code);
        $this->assertSame('Another error', $error->message);
        $this->assertNull($error->data);
    }

    #[DataProvider('staticFactoryProvider')]
    public function testStaticFactories(string $method, int $expectedCode, string $message, mixed $data): void
    {
        /** @var Error\Error $error */
        $error = Error\Error::$method($message, $data);
        $this->assertInstanceOf(Error\Error::class, $error);
        $this->assertSame($expectedCode, $error->code);
        $this->assertSame($message, $error->message);
        $this->assertSame($data, $error->data);
    }

    public static function staticFactoryProvider(): array
    {
        return [
            'parseError' => ['parseError', JSONRPC::PARSE_ERROR, 'Parse error', 'some data'],
            'invalidRequest' => ['invalidRequest', JSONRPC::INVALID_REQUEST, 'Invalid request', null],
            'methodNotFound' => ['methodNotFound', JSONRPC::METHOD_NOT_FOUND, 'Method not found', ['method' => 'foo']],
            'invalidParams' => ['invalidParams', JSONRPC::INVALID_PARAMS, 'Invalid params', null],
            'internalError' => ['internalError', JSONRPC::INTERNAL_ERROR, 'Internal error', 123],
        ];
    }

    #[DataProvider('readProvider')]
    public function testRead(mixed $incoming, int $expectedCode, string $expectedMessage, mixed $expectedData): void
    {
        $error = Error\Error::read($incoming);
        $this->assertSame($expectedCode, $error->code);
        $this->assertSame($expectedMessage, $error->message);
        $this->assertSame($expectedData === true ? $incoming : $expectedData, $error->data);
    }

    public static function readProvider(): array
    {
        return [
            'valid object' => [
                (object)['code' => -32601, 'message' => 'Method not found', 'data' => 'test'],
                -32601, 'Method not found', 'test'
            ],
            'valid array' => [
                ['code' => -32602, 'message' => 'Invalid params'],
                -32602, 'Invalid params', null
            ],
            'missing code' => [
                (object)['message' => 'Oops'],
                JSONRPC::INTERNAL_ERROR, 'Missing or invalid code in response error', true
            ],
            'wrong code type (string)' => [
                (object)['code' => '-32601', 'message' => 'Oops'],
                JSONRPC::INTERNAL_ERROR, 'Missing or invalid code in response error', true
            ],
            'missing message' => [
                (object)['code' => -32601],
                JSONRPC::INTERNAL_ERROR, 'Missing or invalid message in response error', true
            ],
            'wrong message type (int)' => [
                (object)['code' => -32601, 'message' => 123],
                JSONRPC::INTERNAL_ERROR, 'Missing or invalid message in response error', true
            ],
        ];
    }
}
