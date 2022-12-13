<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Contracts;

interface AuditEntityInterface
{
    public function getData();

    public function setData(?array $data): static;

    public function getIpAddress();

    public function setIpAddress(?string $ipAddress): static;

    public function getUserIdentifier();

    public function setUserIdentifier(?string $userIdentifier): static;

    public function getCategory();

    public function setCategory(?string $category): static;

    public function getMessage();

    public function setMessage(?string $message): static;
}
