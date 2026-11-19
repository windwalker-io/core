<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\WebSocket\Parser;

use Windwalker\WebSocket\Parser\SocketIoParser;
use PHPUnit\Framework\TestCase;

class SocketIoParserTest extends TestCase
{
    public function testParseEvent(): void
    {
        $p = new SocketIoParser()->parse('42["hello",{"a":1},2]');

        self::assertSame('hello', $p['name']);
        self::assertSame([['a' => 1], 2], $p['args']);
        self::assertSame('/', $p['namespace']);
        self::assertNull($p['id']);
    }

    public function testParseNamespaceAndAck(): void
    {
        $p = new SocketIoParser()->parse('42/chat,12["msg","x"]');

        self::assertSame('/chat', $p['namespace']);
        self::assertSame(12, $p['id']);
        self::assertSame('msg', $p['name']);

        $a = new SocketIoParser()->parse('43/chat,12["ok"]');

        self::assertSame(SocketIoParser::ACK, $a['type']);
        self::assertSame(['ok'], $a['args']);
    }

    public function testParseConnectAndEngine(): void
    {
        $parser = new SocketIoParser();

        self::assertSame(SocketIoParser::CONNECT, $parser->parse('40')['type']);
        self::assertSame(['token' => 't'], $parser->parse('40/a,{"token":"t"}')['data']);
        self::assertSame(SocketIoParser::ENGINE_PING, $parser->parse('2')['engine']);
        self::assertSame('probe', $parser->parse('2probe')['data']);
        self::assertSame('abc', $parser->parse('0{"sid":"abc"}')['data']['sid']);
    }

    public function testFormatRoundTrip(): void
    {
        $parser = new SocketIoParser();

        self::assertSame('42["hi",1]', $parser->format('hi', 1));
        self::assertSame(
            '42/chat,5["hi",1]',
            $parser->format(name: 'hi', args: [1], namespace: '/chat', id: 5)
        );
        self::assertSame('431["ok"]', $parser->format(type: SocketIoParser::ACK, id: 1, args: ['ok']));
        self::assertSame('3', $parser->format(engine: SocketIoParser::ENGINE_PONG));
    }

    public function testInvalid(): void
    {
        $this->expectException(\RuntimeException::class);

        new SocketIoParser()->parse('42{"a":1}');
    }
}
