<?php

namespace Janderson\JsonRpc\Test;

use Janderson\JsonRpc\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request\Notification::class)]
class NotificationTest extends TestCase
{
    public function testConstructor(): void
    {
        $notification = new Request\Notification('testMethod', ['foo' => 'bar']);
        $this->assertSame('testMethod', $notification->method);
        $this->assertSame(['foo' => 'bar'], $notification->params);
        $this->assertSame('2.0', $notification->jsonrpc);
        $this->assertNull($notification->getId());
    }

    public function testConstructorWithNullParams(): void
    {
        $notification = new Request\Notification('testMethod', null);
        $this->assertSame('testMethod', $notification->method);
        $this->assertNull($notification->params);
        $this->assertSame('2.0', $notification->jsonrpc);
    }

    public function testJsonSerialize(): void
    {
        $request = new Request\Notification('testMethod', ['foo' => 'bar']);
        $this->assertSame(['jsonrpc' => '2.0', 'method' => 'testMethod', 'params' => ['foo' => 'bar']], $request->jsonSerialize());
        $requestNoParams = new Request\Notification('testMethod', null);
        $this->assertSame(['jsonrpc' => '2.0', 'method' => 'testMethod'], $requestNoParams->jsonSerialize());
    }
}
