<?php

namespace Janderson\JsonRpc\Test\Response;

use Janderson\JsonRpc\Request;
use Janderson\JsonRpc\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response\Response::class)]
#[UsesClass(Request\Request::class)]
class ResponseTest extends TestCase
{
    public function testConstructor(): void
    {
        $response = new Response\Response( 'result data', 1);
        $this->assertSame('2.0', $response->jsonrpc);
        $this->assertSame('result data', $response->result);
        $this->assertSame(1, $response->id);
    }

    public function testEquals(): void
    {
        $response1 = new Response\Response(['foo' => 'bar'], 1);
        $response2 = new Response\Response(['foo' => 'bar'], 1);

        $this->assertTrue($response1->equals($response2));
        $this->assertTrue($response2->equals($response1));

        $response3 = new Response\Response(['foo' => 'baz'], 1);
        $this->assertFalse($response1->equals($response3));

        $response4 = new Response\Response(['foo' => 'bar'], 2);
        $this->assertFalse($response1->equals($response4));
    }
}