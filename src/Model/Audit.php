<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Model;

use Doctrine\ORM\Mapping as ORM;
use WhiteDigital\Audit\Contracts\AuditEntityInterface;

#[ORM\MappedSuperclass]
class Audit implements AuditEntityInterface
{
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
