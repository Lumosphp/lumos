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

namespace Lumos\Http\EventListener;

use Lumos\DependencyInjection\ContainerInterface;
use Lumos\Http\Routing\ErrorController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorListener implements EventSubscriberInterface
{
    public function __construct(
        protected ContainerInterface $containerInterface,
        protected ?string $notFoundController
    ) {}

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        $controller = $this->notFoundController ?? ErrorController::class;
        $controller = strpos('::', $controller) !== false ? $controller : "{$controller}::notFound";

        // If no route, go to 404
        if (!$request->attributes->has('_route') && $this->notFoundController) {
            $request->attributes->add([
                '_controller' => $controller
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 99999]]
        ];
    }
}
