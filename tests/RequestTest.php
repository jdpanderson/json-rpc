<?php

namespace Janderson\JsonRpc\Test;

use DomainException;
use Exception;
use InvalidArgumentException;
use Janderson\JsonRpc\JSONRPC;
use Janderson\JsonRpc\Request;
use Janderson\JsonRpc\Response\Error;
use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Request::class)]
#[UsesClass(Request\Notification::class)]
#[UsesClass(Request\Request::class)]
#[UsesClass(Error::class)]
#[UsesClass(Error\Error::class)]
class RequestTest extends TestCase
{
    public function testFactoryMethods()
    {
        $request = Request::request('testMethod', ['foo' => 'bar'], 123);
        $this->assertEquals(123, $request->getId());
        $this->assertSame('2.0', $request->jsonrpc);
        $this->assertSame('testMethod', $request->method);
        $this->assertEquals(['foo' => 'bar'], $request->params);

        $request = Request::notification('testMethod', ['foo' => 'bar']);
        $this->assertNull($request->getId());
        $this->assertSame('2.0', $request->jsonrpc);
        $this->assertSame('testMethod', $request->method);
        $this->assertEquals(['foo' => 'bar'], $request->params);
    }

    #[DataProvider('parseValidProvider')]
    public function testParseValid(string $json, string $expectedClass, ?string $method, array|object|null $params, $id): void
    {
        $result = Request::parse($json);
        $this->assertInstanceOf($expectedClass, $result);
        if ($result instanceof Request\Request || $result instanceof Request\Notification) {
            $this->assertSame($method, $result->method);
            $this->assertEquals($params, $result->params);
        }
        if ($result instanceof Request\Request) {
            $this->assertSame($id, $result->id);
        }
        $this->assertEquals($json, (string)$result);
    }

    public static function parseValidProvider(): array
    {
        return [
            'request positional params' => [
                '{"jsonrpc":"2.0","method":"subtract","params":[42,23],"id":1}',
                Request\Request::class, 'subtract', [42, 23], 1
            ],
            'request named params' => [
                '{"jsonrpc":"2.0","method":"subtract","params":{"subtrahend":23,"minuend":42},"id":3}',
                Request\Request::class, 'subtract', (object)['subtrahend' => 23, 'minuend' => 42], 3
            ],
            'notification' => [
                '{"jsonrpc":"2.0","method":"update","params":[1,2,3,4,5]}',
                Request\Notification::class, 'update', [1,2,3,4,5], null
            ],
            'request no params' => [
                '{"jsonrpc":"2.0","method":"foobar","id":"10"}',
                Request\Request::class, 'foobar', null, '10'
            ],
        ];
    }

    public function testReadArray(): void
    {
        $json = json_decode('{"jsonrpc": "2.0", "method": "sum", "params": [1,1], "id": "1"}', true);
        $result = Request::read($json);
        $this->assertInstanceOf(Request\Request::class, $result);
    }

    #[DataProvider('parseInvalidProvider')]
    public function testParseInvalid(string $json, ?int $expectedErrorCode): void
    {
        $result = Request::parse($json);
        if ($expectedErrorCode === null) {
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf(Error::class, $result);
            $this->assertSame($expectedErrorCode, $result->error->code);
        }
    }

    public static function parseInvalidProvider(): array
    {
        return [
            'invalid json' => ['{"jsonrpc": "2.0", "method": "foobar, "params": "bar", "baz"]', JSONRPC::PARSE_ERROR],
            'invalid request - missing jsonrpc' => ['{"method": "foobar", "id": 1}', JSONRPC::INVALID_REQUEST],
            'invalid request - wrong jsonrpc' => ['{"jsonrpc": "1.0", "method": "foobar", "id": 1}', JSONRPC::INVALID_REQUEST],
            'invalid request - invalid method type' => ['{"jsonrpc": "2.0", "method": 1, "id": 1}', JSONRPC::INVALID_REQUEST],
            'invalid request - empty array' => ['[]', JSONRPC::INVALID_REQUEST],
            'invalid request - not object/array' => ['123', JSONRPC::INVALID_REQUEST],
        ];
    }

    public function testParseArrayWithInvalidItem(): void
    {
        $json = '[1]';
        $result = Request::parse($json);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Error::class, $result[0]);
        $this->assertSame(JSONRPC::INVALID_REQUEST, $result[0]->error->code);
    }

    public function testParseBatch(): void
    {
        $json = '[
            {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
            {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]},
            {"jsonrpc": "2.0", "method": "subtract", "params": [42,23], "id": "2"},
            {"foo": "boo"},
            {"jsonrpc": "2.0", "method": "foo.get", "params": {"name": "myself"}, "id": "5"},
            {"jsonrpc": "2.0", "method": "get_data", "id": "9"} 
        ]';

        $result = Request::parse($json);
        $this->assertIsArray($result);
        $this->assertCount(6, $result);

        $this->assertInstanceOf(Request\Request::class, $result[0]);
        $this->assertInstanceOf(Request\Notification::class, $result[1]);
        $this->assertInstanceOf(Request\Request::class, $result[2]);
        $this->assertInstanceOf(Error::class, $result[3]);
        $this->assertInstanceOf(Request\Request::class, $result[4]);
        $this->assertInstanceOf(Request\Request::class, $result[5]);
    }

    #[DataProvider('stringifyProvider')]
    public function testStringify(array|Request $input, stdClass|array|\Exception $expected): void
    {
        if ($expected instanceof Exception) {
            $this->expectException($expected::class);
        }
        if ($input instanceof JsonSerializable) {
            $this->assertEquals($expected, (object)$input->jsonSerialize());
        }
        $this->assertJsonStringEqualsJsonString(json_encode($expected), Request::stringify($input));
    }

    public static function stringifyProvider(): array
    {
        $request = new Request\Request('testMethod', ['foo' => 'bar'], 123);
        $requestObj = (object)["jsonrpc" => "2.0", "method" => "testMethod", "params" => ["foo" => "bar"], "id" => 123];

        $requestNoParams = new Request\Request('testMethod', id: 234);
        $requestNoParamsObj = (object)["jsonrpc" => "2.0", "method" => "testMethod", "id" => 234];

        $notification = new Request\Notification('notify', ['foo' => 'bar']);
        $notificationObj = (object)["jsonrpc" => "2.0", "method" => "notify", "params" => ["foo" => "bar"]];

        $notificationNoParams = new Request\Notification('notify');
        $notificationNoParamsObj = (object)["jsonrpc" => "2.0", "method" => "notify"];

        return [
            'request' => [$request, $requestObj],
            'request no params' => [$requestNoParams, $requestNoParamsObj],
            'notification' => [$notification, $notificationObj],
            'notification no params' => [$notificationNoParams, $notificationNoParamsObj],
            'batch' => [[$request, $notification], [$requestObj, $notificationObj]],
            'invalid batch' => [[$request, "foo"], new InvalidArgumentException()],
        ];
    }

    public function testRequestNotInstantiable(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageMatches("/^Cannot instantiate abstract class.*/");
        $class = Request::class; // Gymnastics to work around inspection error
        new $class();
    }

    #[DataProvider('isMethodsProvider')]
    public function testIsMethods(Request|Error $object, bool $isRequest, bool $isResponse, bool $isError, bool $isNotification): void
    {
        $this->assertSame($isRequest, $object->isRequest());
        $this->assertSame($isResponse, $object->isResponse());
        $this->assertSame($isError, $object->isError());
        $this->assertSame($isNotification, $object->isNotification());
    }

    public static function isMethodsProvider(): array
    {
        $request = new Request\Request('method', null, 1);
        $notification = new Request\Notification('method', null);
        $error = new Error(Error\Error::internalError('error'), 1);

        return [
            'request object' => [$request, true, false, false, false],
            'notification object' => [$notification, true, false, false, true],
            'error object' => [$error, false, true, true, false],
        ];
    }
}