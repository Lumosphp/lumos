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

namespace Lumos\Http\Routing;

use Lumos\Http\Controller;
use Lumos\Http\Request;

class ErrorController extends Controller
{
    public function notFound(Request $request)
    {
        return $this->respond("
            <h1>Whoopsie</h1>
            <p>The page \"{$request->getPathInfo()}\" couldn't be found...</p>
        ");
    }
}
