<?php

namespace Janderson\JsonRpc\Test;

use Janderson\JsonRpc\Request\Request;
use Janderson\JsonRpc\Response\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Error::class)]
#[UsesClass(Error\Error::class)]
#[UsesClass(Request::class)]
class ResponseErrorTest extends TestCase
{
    public function testConstructor(): void
    {
        $errorObject = new Error\Error(-32000, 'Server error');
        $errorResponse = new Error($errorObject, 1);

        $this->assertSame($errorObject, $errorResponse->error);
        $this->assertSame(1, $errorResponse->id);
        $this->assertSame('2.0', $errorResponse->jsonrpc);
    }

    #[DataProvider('equalsProvider')]
    public function testEquals(Error $error1, Error $error2, bool $expected): void
    {
        $this->assertSame($expected, $error1->equals($error2));
    }

    public static function equalsProvider(): array
    {
        $errorResponse1 = new Error(new Error\Error(-32000, 'Server error'), 1);
        $errorResponse2 = new Error(new Error\Error(-32000, 'Server error'), 1);
        $errorResponse3 = new Error(new Error\Error(-32000, 'Server error'), 2);
        $errorResponse4 = new Error(new Error\Error(-32001, 'Another error'), 1);

        return [
            'identical error objects and id' => [$errorResponse1, $errorResponse2, true],
            'self comparison' => [$errorResponse1, $errorResponse1, true],
            'different id' => [$errorResponse1, $errorResponse3, false],
            'different error object' => [$errorResponse1, $errorResponse4, false],
        ];
    }
}
