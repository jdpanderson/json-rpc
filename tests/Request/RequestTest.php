<?php

namespace Janderson\JsonRpc\Test\Request;

use Janderson\JsonRpc\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request\Request::class)]
class RequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $request = new Request\Request('testMethod', ['foo' => 'bar'], 123);
        $this->assertSame('testMethod', $request->method);
        $this->assertSame(['foo' => 'bar'], $request->params);
        $this->assertSame(123, $request->id);
        $this->assertEquals(123, $request->getId());
        $this->assertSame('2.0', $request->jsonrpc);
    }

    public function testConstructorWithNullId(): void
    {
        $request = new Request\Request('testMethod', null, null);
        $this->assertSame('testMethod', $request->method);
        $this->assertNull($request->params);
        $this->assertNull($request->id);
        $this->assertSame('2.0', $request->jsonrpc);
    }

    public function testConstructorWithStringId(): void
    {
        $request = new Request\Request('testMethod', null, 'abc');
        $this->assertSame('abc', $request->id);
    }

    public function testJsonSerialize(): void
    {
        $request = new Request\Request('testMethod', ['foo' => 'bar'], 123);
        $this->assertSame(['jsonrpc' => '2.0', 'method' => 'testMethod', 'params' => ['foo' => 'bar'], 'id' => 123], $request->jsonSerialize());
        $requestNoParams = new Request\Request('testMethod', null, 123);
        $this->assertSame(['jsonrpc' => '2.0', 'method' => 'testMethod', 'id' => 123], $requestNoParams->jsonSerialize());
    }
}
