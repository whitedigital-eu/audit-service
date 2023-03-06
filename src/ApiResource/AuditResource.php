<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\ApiResource;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use WhiteDigital\Audit\DataProvider\AuditDataProvider;
use WhiteDigital\Audit\Entity\Audit;
use WhiteDigital\EntityResourceMapper\Attribute\Mapping;
use WhiteDigital\EntityResourceMapper\Filters\ResourceDateFilter;
use WhiteDigital\EntityResourceMapper\Filters\ResourceJsonFilter;
use WhiteDigital\EntityResourceMapper\Filters\ResourceOrderFilter;
use WhiteDigital\EntityResourceMapper\Filters\ResourceSearchFilter;
use WhiteDigital\EntityResourceMapper\Resource\BaseResource;

#[
    ApiResource(
        shortName: 'Audit',
        operations: [
            new Get(
                requirements: ['id' => '\d+', ],
                normalizationContext: ['groups' => [self::ITEM, ], ],
            ),
            new GetCollection(
                normalizationContext: ['groups' => [self::READ, ], ],
            ),
        ],
        normalizationContext: ['groups' => [self::READ, ], ],
        order: ['createdAt' => Criteria::DESC, ],
        provider: AuditDataProvider::class,
    ),
    ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'groups', 'overrideDefaultGroups' => false, ]),
    ApiFilter(ResourceDateFilter::class, properties: ['createdAt', 'updatedAt', ]),
    ApiFilter(ResourceJsonFilter::class, properties: ['data', ]),
    ApiFilter(ResourceOrderFilter::class, properties: ['category', 'message', 'ipAddress', 'userEmail', 'createdAt', 'updatedAt', ]),
    ApiFilter(ResourceSearchFilter::class, properties: ['category', 'message', 'ipAddress', 'userIdentifier', ]),
]
#[Mapping(Audit::class)]
class AuditResource extends BaseResource
{
    public const PREFIX = 'audit:';

    public const ITEM = self::PREFIX . 'item';
    public const READ = self::PREFIX . 'read';

    #[ApiProperty(identifier: true)]
    #[Groups([self::READ, self::ITEM, ])]
    public mixed $id = null;

    #[Groups([self::READ, self::ITEM, ])]
    public ?string $category = null;

    #[Groups([self::READ, self::ITEM, ])]
    public ?string $message = null;

    #[Groups([self::READ, self::ITEM, ])]
    #[Assert\Ip(version: 'all')]
    public ?string $ipAddress = null;

    #[Groups([self::READ, self::ITEM, ])]
    #[Assert\Email]
    public ?string $userIdentifier = null;

    #[Groups([self::ITEM, ])]
    #[Assert\Json]
    public ?array $data = null;

    #[Groups([self::READ, self::ITEM, ])]
    public ?DateTimeImmutable $createdAt = null;

    #[Groups([self::READ, self::ITEM, ])]
    public ?DateTimeImmutable $updatedAt = null;
}
