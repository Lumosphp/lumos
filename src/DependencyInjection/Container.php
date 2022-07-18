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

namespace Lumos\DependencyInjection;

use InvalidArgumentException;
use Lumos\DependencyInjection\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface as DependencyInjectionContainerInterface;

class Container implements ContainerInterface, DependencyInjectionContainerInterface
{
    protected $services = [];
    protected $aliases = [];
    protected $params = [];

    public function initialized(string $key): bool
    {
        return isset($this->services[$key]) && is_object($this->services[$key]);
    }

    public function set(string $key, mixed $value): static
    {
        $this->services[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->services[$key]) || isset($this->aliases[$key]);
    }

    public function get(string $key, int $invalidBehaviour = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
        if (!$this->has($key)) {
            if ($invalidBehaviour === static::EXCEPTION_ON_INVALID_REFERENCE) {
                throw new InvalidArgumentException(sprintf('The key "%s" does not exist in the container', $key));
            } elseif ($invalidBehaviour === static::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE) {
                throw new RuntimeException(sprintf('The key "%s" does not exist on the container', $key));
            } elseif ($invalidBehaviour === static::NULL_ON_INVALID_REFERENCE) {
                return null;
            } elseif ($invalidBehaviour === static::IGNORE_ON_INVALID_REFERENCE) {
            }
        }

        return $this->services[$key] ?? $this->services[$this->aliases[$key]];
    }

    public function getAll(): array
    {
        return $this->services;
    }

    public function setParameter(string $key, mixed $value): static
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function hasParameter(string $key): bool
    {
        return isset($this->params[$key]);
    }

    public function getParameter(string $key): mixed
    {
        return $this->params[$key];
    }

    public function setAlias(string $serviceName, string $aliasName): static
    {
        $this->aliases[$aliasName] = $serviceName;

        return $this;
    }
}
