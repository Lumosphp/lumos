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

namespace Lumos\Http;

use Lumos\Http\Response;

abstract class Controller
{
    protected function respond($content, int $status = Response::HTTP_OK, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }
}
