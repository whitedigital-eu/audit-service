<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use WhiteDigital\Audit\Service\AuditServiceLocator;

use function in_array;

class AuditExceptionEventSubscriber implements EventSubscriberInterface
{
    private readonly array $excludedRoutes;

    public function __construct(
        private readonly AuditServiceLocator $audit,
        ParameterBagInterface $bag,
    ) {
        $this->excludedRoutes = $bag->get('whitedigital.audit.excluded_routes');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'handleConsoleErrorEvent',
            KernelEvents::EXCEPTION => 'handleExceptionEvent',
        ];
    }

    public function handleExceptionEvent(ExceptionEvent $event): void
    {
        if (in_array($event->getRequest()->attributes->get('_route'), $this->excludedRoutes, true)) {
            return;
        }

        $this->audit->auditException($event->getThrowable(), $event->getRequest()->getPathInfo());
    }

    public function handleConsoleErrorEvent(ConsoleErrorEvent $event): void
    {
        $this->audit->auditException($event->getError());
    }
}
