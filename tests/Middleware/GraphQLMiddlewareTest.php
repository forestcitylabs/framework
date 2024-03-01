<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\GraphQLMiddleware;
use GraphQL\Type\Schema;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Http\Factory\Guzzle\ResponseFactory;
use Http\Factory\Guzzle\StreamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(GraphQLMiddleware::class)]
class GraphQLMiddlewareTest extends TestCase
{
    #[Test]
    public function testCorrectPath(): void
    {
        $middleware = new GraphQLMiddleware(
            new Schema([]),
            new ResponseFactory(),
            new StreamFactory(),
        );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        $response = $middleware->process(
            new ServerRequest("POST", new Uri("http://example.com/graphql"), body: '{"query": ""}'),
            $handler
        );

        // The path will match, so we will never delegate this request.
        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertTrue($response->hasHeader('cache-control'));
    }

    #[Test]
    public function testIncorrectPath(): void
    {
        $middleware = new GraphQLMiddleware(
            new Schema([]),
            new ResponseFactory(),
            new StreamFactory(),
        );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new Response());
        $middleware->process(
            new ServerRequest("POST", new Uri("http://example.com/notgraphql"), body: '{"query": ""}'),
            $handler
        );
    }
}
