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

namespace Lumos;

use Lumos\Http\Routing\RouteCollection as RouteCollection;

/**
 * Lumos config container.
 *
 * @example
 *  $config = new Config(routes: RouteCollection, debug: false);
 *  $config->set('DB_HOST', 'localhost')
 *      ->set('DB_USER', 'username');
 */
class Config
{
    protected array $config = [];

    public function __construct(
        protected RouteCollection $routes = new RouteCollection(),
        protected array $services = [],
        protected array $middleware = [],
        bool $debug = false
    ) {
        $this->set('debug', $debug);
    }

    public function setRoutes(RouteCollection $routes): static
    {
        $this->routes = $routes;

        return $this;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function setServices(array $services): static
    {
        $this->services = $services;

        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function isDebug(): bool
    {
        return $this->config['debug'] ?? false;
    }

    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    public function get(string $key, $fallback = null)
    {
        return $this->config[$key] ?? $fallback;
    }

    public function set(string $key, $value): static
    {
        $this->config[$key] = $value;

        return $this;
    }
}
