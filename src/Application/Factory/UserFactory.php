<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Application\Dto\UserDto;
use App\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

class UserFactory
{
    public function createFromDto(UserDto $dto, Uuid $adminUuid): User
    {
        $user = new User();
        $this->mapDtoToEntity($dto, $user);

        $user->setIsDeleted(false);
        $user->setCreatedBy($adminUuid);

        return $user;
    }

    public function updateFromDto(UserDto $dto, User $user, Uuid $adminUuid): User
    {
        $this->mapDtoToEntity($dto, $user);
        $user->setIsDeleted(false);
        $user->setUpdatedBy($adminUuid);

        return $user;
    }

    private function mapDtoToEntity(UserDto $dto, User $user): void
    {
        $user
            ->setEmail($dto->email)
            ->setCompanyUuid($dto->companyUuid)
            ->setFirstName($dto->firstName)
            ->setLastName($dto->lastName)
            ->setEmployeeNumber($dto->employeeNumber)
            ->setIsActive($dto->isActive);
    }
}
