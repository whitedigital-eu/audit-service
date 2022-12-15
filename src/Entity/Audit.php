<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Entity;

use Doctrine\ORM\Mapping as ORM;
use WhiteDigital\Audit\Contracts\AuditEntityInterface;
use WhiteDigital\Audit\Repository\AuditRepository;
use WhiteDigital\EntityResourceMapper\Entity\BaseEntity;

#[ORM\Entity(repositoryClass: AuditRepository::class)]
#[ORM\MappedSuperclass]
#[ORM\Index(fields: ['category'])]
#[ORM\Index(fields: ['message'])]
#[ORM\Index(fields: ['ipAddress'])]
#[ORM\Index(fields: ['userIdentifier'])]
#[ORM\Index(fields: ['createdAt'])]
#[ORM\Index(fields: ['updatedAt'])]
#[ORM\HasLifecycleCallbacks]
class Audit extends BaseEntity implements AuditEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(unique: true)]
    protected ?int $id = null;

    #[ORM\Column(nullable: true)]
    protected ?array $data = null;

    #[ORM\Column(nullable: true)]
    protected ?string $ipAddress = null;

    #[ORM\Column(nullable: true)]
    protected ?string $userIdentifier = null;

    #[ORM\Column]
    protected ?string $category = null;

    #[ORM\Column(type: 'text')]
    protected ?string $message = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): static
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }
}
