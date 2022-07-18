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

use Lumos\Http\Response;
use Lumos\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener as SymfonyRouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class RouterListener extends SymfonyRouterListener
{
  public function __construct(
    UrlMatcherInterface|RequestMatcherInterface $matcher,
    RequestStack $requestStack,
    RequestContext $context = null,
    LoggerInterface $logger = null,
    private ?string $projectDir = null,
    private bool $debug = true
  ) {
    parent::__construct($matcher, $requestStack, $context, $logger, $projectDir, $debug);
  }

  public function onKernelException(ExceptionEvent $event)
  {
      if (!$this->debug || !($e = $event->getThrowable()) instanceof NotFoundHttpException) {
          return;
      }

      if ($e->getPrevious() instanceof NoConfigurationException) {
          $event->setResponse($this->createWelcomeResponse());
      }
  }

  private function createWelcomeResponse()
  {
      $version = Kernel::VERSION;
      $projectDir = realpath((string) $this->projectDir);
      $docsVersion = substr(Kernel::VERSION, 0, 3);

      ob_start();
      require \dirname(__DIR__).'/Resources/welcome.phtml';

      return new Response(ob_get_clean(), Response::HTTP_NOT_FOUND);
  }
}
