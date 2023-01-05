<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;

use function in_array;

class AuditExceptionEventSubscriber implements EventSubscriberInterface
{
    private readonly array $excludedRoutes;

    public function __construct(
        private readonly AuditServiceInterface $audit,
        ParameterBagInterface $bag,
    ) {
        $this->excludedRoutes = $bag->get('whitedigital.audit.exluded_routes');
    }

    public static function getSubscribedEvents(): array
    {
        return [
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
}
