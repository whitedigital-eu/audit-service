<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteDigital\Audit\Entity\Audit;
use WhiteDigital\Audit\Service\AuditService;

use function array_key_exists;
use function sprintf;

class AuditDoctrineEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->logActivity($this->translator->trans('entity.create'), $args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->logActivity($this->translator->trans('entity.remove'), $args); // izdzēstie dati
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->logActivity($this->translator->trans('entity.update'), $args); // vecie dati
    }

    private function createEntityAuditData(LifecycleEventArgs $args, object $entity): ?array
    {
        $entityManager = $args->getObjectManager();
        if ($entityManager instanceof EntityManagerInterface) {
            $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);
            if (!array_key_exists('id', $changeSet)) {
                $changeSet = ['id' => [$entity->getId(), $entity->getId()]] + $changeSet;
            }

            return $changeSet;
        }

        return null;
    }

    private function logActivity(string $message, LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (Audit::class === $entity::class) {
            return;
        }

        $classNameTranslation = $this->translator->trans('entity.' . (new ReflectionClass($entity))->getShortName());
        $data = $this->createEntityAuditData($args, $entity);
        $this->audit->audit('db', sprintf('%s: %s', $message, $classNameTranslation), $data);
    }
}
