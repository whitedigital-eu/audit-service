<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
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

#[AsDoctrineListener(event: Events::postPersist, connection: 'default')]
#[AsDoctrineListener(event: Events::preRemove, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, connection: 'default')]
class AuditDoctrineEventSubscriber
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


    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->logActivity(Events::postPersist, $this->translator->trans('entity.create', domain: 'Audit'), $args);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->logActivity(Events::preRemove, $this->translator->trans('entity.remove', domain: 'Audit'), $args);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->logActivity(Events::postUpdate, $this->translator->trans('entity.update', domain: 'Audit'), $args);
    }

    private function logActivity(string $event, string $action, LifecycleEventArgs $args): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $entity = $args->getObject();
        if (in_array(AuditEntityInterface::class, class_implements($entity::class), true)) {
            return;
        }

        $entityManager = $args->getObjectManager();
        if (Events::postUpdate === $event) {
            $originalEntityData = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);
            $entityData = [];
            foreach ($originalEntityData as $field => $value) {
                $entityData[$field] = [
                    $this->normalizeEntityCollections($value),
                ];
            }
            $originalEntityData = $entityData;
        } else {
            $originalEntityData = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);
            $originalEntityData = $this->normalizeEntityCollections($originalEntityData);
        }

        if (!array_key_exists('id', $originalEntityData)) {
            $originalEntityData['id'] = $entity->getId();
        }

        $entityTranslation = $this->translator->trans('entity', domain: 'Audit');
        $this->audit->audit(AuditType::DB, sprintf('%s %s %s', $action, $entityTranslation, $entity::class), $originalEntityData);
    }

    private function normalizeEntityCollections(mixed $entity): array|int|string
    {
        if ($entity instanceof PersistentCollection) {
            $entity = $entity->toArray();
        }

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
