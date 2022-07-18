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

namespace Lumos\Kernel;

use InvalidArgumentException;
use Lumos\DependencyInjection\ContainerAwareInterface;
use Lumos\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as SymfonyControllerResolver;

class ControllerResolver extends SymfonyControllerResolver
{
    public function __construct(
        protected ContainerInterface $container,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
    }

    protected function instantiateController(string $class): object
    {
        // If no construct, skip reflection.
        if (!method_exists($class, '__construct')) {
            return new $class();
        }

        $arguments = [];
        $reflection  = new ReflectionMethod($class, '__construct');

        foreach ($reflection->getParameters() as $index => $param) {
            $paramInfo = new ReflectionParameter([$class, '__construct'], $index);

            // Get by variable name, then by typed class
            if ($this->container->has($param->getName())) {
                $arguments[$param->getName()] = $this->container->get($param->getName());
            } elseif($this->container->has($paramInfo->getType()->getName())) {
                $arguments[$param->getName()] = $this->container->get($paramInfo->getType()->getName());
            } else {
                throw new InvalidArgumentException(sprintf('Argument "%s" for controller %s was not found in the container', $param->getName(), $class));
            }
        }

        if (count($arguments)) {
            $controller = new $class(...$arguments);
        } else {
            $controller = new $class();
        }

        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->container);
        }

        return $controller;
    }
}
