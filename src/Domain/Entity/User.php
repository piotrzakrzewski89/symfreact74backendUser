<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\UserRoleEnum;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(
    name: '"user"',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_user_uuid', columns: ['uuid']),
        new ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email']),
        new ORM\UniqueConstraint(name: 'uniq_user_employee_company', columns: ['companyUuid', 'employee_number'])
    ],
    indexes: [
        new ORM\Index(name: 'idx_user_company', columns: ['companyUuid'])
    ]
)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\Column(type: 'uuid', nullable: false)]
    private Uuid $companyUuid;
    #[ORM\Column(type: 'uuid', nullable: false)]
    private Uuid $createdBy;
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $updatedBy = null;

    #[ORM\Column(length: 100, nullable: false)]
    private string $email;
    #[ORM\Column(length: 255, nullable: false)]
    private string $firstName;
    #[ORM\Column(length: 255, nullable: false)]
    private string $lastName;
    #[ORM\Column(length: 50, nullable: false)]
    private string $employeeNumber;
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastFailedLoginAt = null;
    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column]
    private bool $isActive;
    #[ORM\Column]
    private bool $isDeleted;

    public function __construct()
    {
        $this->isActive = true;
        $this->isDeleted = false;
        $this->uuid = Uuid::v4();
        $this->roles = [UserRoleEnum::USER->value];
        $this->createdAt = new DateTimeImmutable();
    }

    public function activate(Uuid $adminUuid): void
    {
        $this->isActive = true;
        $this->updatedBy = $adminUuid;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(Uuid $adminUuid): void
    {
        $this->isActive = false;
        $this->updatedBy = $adminUuid;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function softDelete(Uuid $adminUuid): void
    {
        $this->isDeleted = true;
        $this->isActive = false;
        $this->deletedAt = new DateTimeImmutable();
        $this->updatedBy = $adminUuid;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCompanyUuid(): ?Uuid
    {
        return $this->companyUuid;
    }

    public function setCompanyUuid(Uuid $companyUuid): static
    {
        $this->companyUuid = $companyUuid;

        return $this;
    }

    public function getCreatedBy(): ?Uuid
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Uuid $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?Uuid
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?Uuid $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmployeeNumber(): ?string
    {
        return $this->employeeNumber;
    }

    public function setEmployeeNumber(string $employeeNumber): static
    {
        $this->employeeNumber = $employeeNumber;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getLastFailedLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastFailedLoginAt;
    }

    public function setLastFailedLoginAt(?\DateTimeImmutable $lastFailedLoginAt): static
    {
        $this->lastFailedLoginAt = $lastFailedLoginAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }
}
