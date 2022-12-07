<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use WhiteDigital\Audit\Service\AuditService;

class AuditExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'handleExceptionEvent',
        ];
    }

    public function handleExceptionEvent(ExceptionEvent $event): void
    {
        $this->audit->auditException($event->getThrowable(), $event->getRequest()->getPathInfo());
    }
}
