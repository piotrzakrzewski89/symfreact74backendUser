<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRoleEnum;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    public ?int $id;

    #[Assert\NotBlank(message: 'Email jest wymagany')]
    #[Assert\Email(message: 'Nieprawidłowy adres email')]
    public string $email;

    public array $roles;
    public bool $isActive;

    #[Assert\NotBlank(message: 'Imię nazwa jest wymagana')]
    #[Assert\Length(max: 255, maxMessage: 'Imię może mieć maksymalnie {{ limit }} znaków')]
    public string $firstName;

    #[Assert\NotBlank(message: 'Nazwisko nazwa jest wymagana')]
    #[Assert\Length(max: 255, maxMessage: 'Nazwisko może mieć maksymalnie {{ limit }} znaków')]
    public string $lastName;

    public ?Uuid $uuid;
    public Uuid $companyUuid;
    public ?DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $updatedAt;
    public ?DateTimeImmutable $deletedAt;
    public ?DateTimeImmutable $lastLogin;
    #[Assert\NotBlank(message: 'Numer kadrowy jest wymagany')]
    public string $employeeNumber;

    public function __construct(
        ?int $id,
        ?Uuid $uuid = null,
        Uuid $companyUuid,
        string $email,
        string $firstName,
        string $lastName,
        string $employeeNumber,
        ?array $roles = null,
        bool $isActive,
        ?DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $deletedAt = null,
        ?DateTimeImmutable $lastLogin = null,

    ) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->companyUuid = $companyUuid;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->employeeNumber = $employeeNumber;
        $this->roles = $roles ?? [UserRoleEnum::USER->value];
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
        $this->lastLogin = $lastLogin;
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getUuid(),
            $user->getCompanyUuid(),
            $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmployeeNumber(),
            $user->getRoles(),
            $user->isActive(),
            $user->getCreatedAt(),
            $user->getUpdatedAt(),
            $user->getDeletedAt(),
            $user->getLastLoginAt(),
        );
    }
    public static function fromEntities(array $users): array
    {
        return array_map(fn(User $user) => self::fromEntity($user), $users);
    }
}
