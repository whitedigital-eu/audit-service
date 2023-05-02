<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;

use function in_array;

class AuditExceptionEventSubscriber implements EventSubscriberInterface
{
    private readonly array $excludedRoutes;

    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly TranslatorInterface $translator,
        ParameterBagInterface $bag,
    ) {
        $this->excludedRoutes = $bag->get('whitedigital.audit.excluded.routes');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'handleConsoleErrorEvent',
            KernelEvents::EXCEPTION => 'handleExceptionEvent',
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function handleExceptionEvent(ExceptionEvent $event): void
    {
        if (in_array($event->getRequest()->attributes->get('_route'), $this->excludedRoutes, true)) {
            return;
        }

        $event->getRequest()->setRequestFormat('jsonld');
        $this->audit->auditException($event->getThrowable(), $event->getRequest()->getPathInfo());

        $this->translateExceptionMessage($event);
    }

    public function handleConsoleErrorEvent(ConsoleErrorEvent $event): void
    {
        $this->audit->auditException($event->getError());
    }

    /**
     * @throws ReflectionException
     */
    private function translateExceptionMessage(ExceptionEvent $event): void
    {
        $currentException = $event->getThrowable();
        $currentMessage = $currentException->getMessage();
        $translatedMessage = $this->translator->trans($currentMessage);
        $reflection = new ReflectionClass($currentException);
        $messageProperty = $reflection->getProperty('message');
        $messageProperty->setValue($currentException, $translatedMessage);
        $event->setThrowable($currentException);
    }
}
