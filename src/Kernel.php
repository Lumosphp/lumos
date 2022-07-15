<?php
/*
 * Lumos Framework
 * Copyright (c) 2022 Jack Polgar
 * https://gitlab.com/nirix/lumos
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Lumos;

use Lumos\Http\Routing\RouterListener;
use Lumos\Kernel\ControllerResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
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
        $this->startTime = microtime(true);
        $this->startMem = memory_get_usage();

        $this->configureErrorHandler();

        $this->container = new Container();
        $this->container->set('config', $config);

        $this->controllerResolver = new ControllerResolver($this->container);
        $this->argumentResolver = new ArgumentResolver();

        $this->urlMatcher = new UrlMatcher($this->config->getRoutes(), new RequestContext());
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber(
            new RouterListener($this->urlMatcher, new RequestStack(), debug: true)
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
        $whoops = new \Whoops\Run;

        if ($this->config->isDebug()) {
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        } else {
            $whoops->pushHandler(function ($exception) {
                echo 'An error has occurred while handling the request.';
            });
        }

        $whoops->register();
    }
}
