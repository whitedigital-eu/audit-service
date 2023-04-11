<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use WhiteDigital\Audit\Contracts\AuditEntityInterface;
use WhiteDigital\Audit\Contracts\AuditServiceInterface;
use WhiteDigital\Audit\Contracts\AuditType;
use WhiteDigital\EntityResourceMapper\Entity\BaseEntity;

use function array_key_exists;
use function array_map;
use function class_implements;
use function in_array;
use function sprintf;

class AuditDoctrineEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly TranslatorInterface $translator,
        private bool $isEnabled = true,
    ) {
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->logActivity($this->translator->trans('entity.create', domain: 'Audit'), $args);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->logActivity($this->translator->trans('entity.remove', domain: 'Audit'), $args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->logActivity($this->translator->trans('entity.update', domain: 'Audit'), $args);
    }

    private function logActivity(string $action, LifecycleEventArgs $args): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $entity = $args->getObject();
        if (in_array(AuditEntityInterface::class, class_implements($entity::class), true)) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $originalEntityData = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);
        if (!array_key_exists('id', $originalEntityData)) {
            $originalEntityData['id'] = $entity->getId();
        }
        $originalEntityData = $this->normalizeEntityCollections($originalEntityData);

        $this->audit->audit(AuditType::DB, sprintf('%s on %s', $action, $entity::class), $originalEntityData);
    }

    private function normalizeEntityCollections(mixed $entity): array|int|string
    {
        return array_map(static function ($value) {
            if ($value instanceof PersistentCollection) {
                $collectionValue = [];
                foreach ($value as $item) {
                    try {
                        $collectionValue[] = $item->getId();
                    } catch (Throwable) {
                        $collectionValue[] = '[cascade_persist]';
                    }
                }

                return $collectionValue;
            }

            if ($value instanceof BaseEntity) {
                return $value->getId();
            }

            return $value;
        }, $entity);
    }
}
