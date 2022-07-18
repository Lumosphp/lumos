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

namespace Lumos\Http\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

class Route extends SymfonyRoute {
    public static function create(string $path, array|callable $controller, string|array $methods = []): static
    {
        $methods = \is_array($methods) ? $methods : [$methods];

        return new static(
            $path,
            [
                '_controller' => $controller,
            ],
            methods: $methods
        );
    }

    public function addMiddleware(string|array $middleware): static
    {
        $this->setOption('middleware', \is_array($middleware) ? $middleware : [$middleware]);

        return $this;
    }
}
