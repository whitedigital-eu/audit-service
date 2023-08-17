<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\DataProvider;

use ApiPlatform\Exception\ResourceClassNotFoundException;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use WhiteDigital\Audit\Api\Resource\AuditResource;
use WhiteDigital\EntityResourceMapper\DataProvider\AbstractDataProvider;
use WhiteDigital\EntityResourceMapper\Entity\BaseEntity;

final class AuditDataProvider extends AbstractDataProvider
{
    /**
     * @throws ReflectionException
     * @throws ResourceClassNotFoundException
     * @throws ExceptionInterface
     */
    protected function createResource(BaseEntity $entity, array $context): AuditResource
    {
        return AuditResource::create($entity, $context);
    }
}
