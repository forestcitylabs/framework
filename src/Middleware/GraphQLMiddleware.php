<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GraphQLMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Schema $schema,
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        private string $path = '/graphql',
        private bool $debug = false
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Process POST requests on the specified path.
        if ($request->getUri()->getPath() === $this->path && 'POST' === $request->getMethod()) {
            // Get the input from the request and parse variables.
            $input = json_decode($request->getBody()->getContents(), true);
            $variables = (isset($input['variables']) && 'null' !== $input['variables'])
                ? $input['variables']
                : null;

            // If debug is enabled include more information.
            if ($this->debug) {
                $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
            } else {
                $debug = DebugFlag::NONE;
            }

            // Execute GraphQL query.
            $output = GraphQL::executeQuery(
                $this->schema,
                $input['query'],
                null,
                $request,
                $variables
            )->toArray($debug);

            // Return the result.
            return $this
                ->response_factory
                ->createResponse()
                ->withHeader('content-type', 'application/json')
                ->withHeader('cache-control', 'no-store, no-cache, must-revalidate')
                ->withBody(
                    $this->stream_factory->createStream(json_encode($output))
                );
        }

        // Pass to next handler.
        return $handler->handle($request);
    }
}
