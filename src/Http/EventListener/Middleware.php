<?php
/*
 * Lumos Framework
 * Copyright (c) 2022 Jack Polgar
 * https://gitlab.com/lumosphp/lumos
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Lumos\Http\EventListener;

use Lumos\DependencyInjection\ContainerInterface;
use Lumos\Http\Exceptions\InvalidMiddlewareException;
use Lumos\Http\Routing\RouteCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Middleware implements EventSubscriberInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected RouteCollection $routes,
        protected array $middleware,
    ) {}

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if ($request->attributes->has('_route')) {
            $route = $this->routes->get($request->attributes->get('_route'));

            // Get the routes middleware, if any
            $middlewares = $route->getOption('middleware') ?? [];
            foreach ($middlewares as $middlewareName) {
                // Check if the middleware exists before running it
                if (!isset($this->middleware[$middlewareName])) {
                    throw new InvalidMiddlewareException(sprintf('Unable to find middleware "%s"', $middlewareName));
                }

                // Check if it was registered as a service
                if ($this->container->has($this->middleware[$middlewareName])) {
                    $middleware = $this->container->get($this->middleware[$middlewareName]);
                } else {
                    $middleware = new $this->middleware[$middlewareName]();
                    $this->container->set($this->middleware[$middlewareName], $middleware);
                }

                // Can it handle a request?
                if (\method_exists($middleware, 'handleRequest')) {
                    $response = $middleware->handleRequest($request);
                }

                unset($route, $middlewares, $middleware);

                if (isset($response) && $response instanceof Response) {
                    $event->setResponse($response);
                    unset($response);

                    return;
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', -100]],
        ];
    }
}
