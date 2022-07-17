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

namespace Lumos\DependencyInjection;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function set(string $key, mixed $value): static;
    public function get(string $key): mixed;
    public function getAll(): array;
    public function has(string $key): bool;
}
