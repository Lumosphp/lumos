<?php
/*
 * Lumos Framework
 * Copyright (c) 2022 Jack Polgar
 * https://github.com/lumosphp/lumos
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Lumos;

use Lumos\DependencyInjection\Container;
use Lumos\DependencyInjection\ContainerAwareInterface;
use Lumos\DependencyInjection\ContainerInterface;
use Lumos\DependencyInjection\ServiceInterface;
use Lumos\Http\EventListener\ErrorListener;
use Lumos\Http\EventListener\MiddlewareListener;
use Lumos\Http\Request;
use Lumos\Http\Routing\RouterListener;
use Lumos\Kernel\ControllerResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class Kernel
{
    public const VERSION = '0.1';

    protected $startTime;
    protected $startMem;

    protected ArgumentResolverInterface $argumentResolver;
    protected ContainerInterface $container;
    protected ControllerResolverInterface $controllerResolver;
    protected EventDispatcherInterface $eventDispatcher;
    protected HttpKernel $httpKernel;
    protected UrlMatcherInterface $urlMatcher;

    public function __construct(
        protected Config $config
    ) {
        Request::enableHttpMethodParameterOverride();

        $this->startTime = microtime(true);
        $this->startMem = memory_get_usage();

        $this->eventDispatcher = new EventDispatcher();
        $this->container = new Container();
        $this->container->set('config', $config);
        $this->container->set('routes', $config->getRoutes());
        $this->container->set('eventDispatcher', $this->eventDispatcher);

        $this->configureErrorHandler();
        $this->buildServices();

        $this->controllerResolver = new ControllerResolver($this->container);
        $this->argumentResolver = new ArgumentResolver();

        $this->urlMatcher = new UrlMatcher($this->config->getRoutes(), new RequestContext());
        $this->eventDispatcher->addSubscriber(
            new RouterListener($this->urlMatcher, new RequestStack(), debug: $this->config->isDebug())
        );

        // Error listener, for 404, etc
        $this->eventDispatcher->addSubscriber(
            new ErrorListener($this->container, $this->config->get('errorController'))
        );

        // Handle route middleware
        $this->eventDispatcher->addSubscriber(
            new MiddlewareListener($this->container, $this->config->getRoutes(), $this->config->getMiddleware())
        );

        $this->httpKernel = new HttpKernel(
            $this->eventDispatcher,
            $this->controllerResolver,
            new RequestStack(),
            $this->argumentResolver
        );
    }

    public function handle(Request $request): Response
    {
        $request->attributes->set('startTime', $this->startTime);
        $request->attributes->set('startMem', $this->startMem);
        $this->container->set('request', $request);

        return $this->httpKernel->handle($request);
    }

    public function terminate(
        Request $request,
        Response $response
    ): void {
        $this->httpKernel->terminate($request, $response);
    }

    protected function configureErrorHandler(): void
    {

        if ($this->config->isDebug()) {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        } else {
            set_error_handler(
                function () {
                    echo 'An error has occurred while handling the request.';
                    exit;
                }
            );
        }

        $whoops->register();
    }

    protected function buildServices(): void
    {
        foreach ($this->config->getServices() as $name => $config) {
            if (!isset($config['class']) && \is_array($config)) {
                $class = $name;
                $service = new $name(...$config);
            } elseif (isset($config['parameters'])) {
                $class = $config['class'];
                $service = new $config['class'](...$config['parameters']);
            } else {
                $class = $config['class'];
                $service = new $config['class']();
            }

            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($this->container);
            }

            if ($service instanceof ServiceInterface) {
                $service->build();
            }

            $this->container->set($name, $service);
            if ($name !== $class) {
                $this->container->setAlias($name, $class);
            }

            unset($service, $class, $name, $config);
        }
    }
}
