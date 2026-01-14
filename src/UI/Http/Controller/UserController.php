<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Application\Dto\UserDto;
use App\Application\Factory\UserDtoFactory;
use App\Application\Service\UserService;
use App\Domain\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user', name: 'api_user_')]
class UserController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private UserService $userService,
        private UserDtoFactory $userDtoFactory,
        private ValidatorInterface $validator
    ) {}

    #[Route('/review/{id}', name: 'review', methods: ['GET'])]
    public function review(int $id): JsonResponse
    {
        return new JsonResponse(UserDto::fromEntity($this->userRepository->getUser($id)));
    }

    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $dto = $this->userDtoFactory->fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        try {
            $user = $this->userService->createUser($dto, 1);
        } catch (\DomainException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        return new JsonResponse(['saved' => 'ok', 'id' => $user->getId()]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['POST'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            $dto = $this->userDtoFactory->fromRequest($request);
            $dto->id = $id;
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        try {
            $user = $this->userService->updateUser($dto, 1);
        } catch (\DomainException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 400);
        }

        if (!$user) {
            return new JsonResponse(['errors' => 'Firma nie znaleziona'], 404);
        }

        return new JsonResponse(['saved' => 'ok', 'id' => $user->getId()]);
    }

    #[Route('/active', name: 'active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        return new JsonResponse(UserDto::fromEntities($this->userRepository->getAllUsersActive()));
    }

    #[Route('/deleted', name: 'deleted', methods: ['GET'])]
    public function deleted(): JsonResponse
    {
        return new JsonResponse(UserDto::fromEntities($this->userRepository->getAllUsersDeleted()));
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $company = $this->userService->deleteCompany($id, 1);

        if (!$company) {
            return new JsonResponse(['errors' => 'Firma nie znaleziona'], 404);
        }

        return new JsonResponse(['api_user_delete' => 'ok'], 200);
    }
    #[Route('/toggle-active/{id}', name: 'toggle-active', methods: ['POST'])]
    public function changeActive(int $id): JsonResponse
    {
        $user = $this->userService->changeActive($id, 1);

        if (!$user) {
            return new JsonResponse(['errors' => 'Pracownik nie znaleziony'], 404);
        }

        return new JsonResponse(['api_user_change' => 'ok'], 200);
    }
}
