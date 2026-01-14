<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Dto\UserDto;
use App\Application\Factory\UserFactory;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserMailer $userMailer,
        private EntityManagerInterface $em,
        private UserFactory $userFactory,
        private HttpClientInterface $httpClient
    ) {}

    public function createUser(UserDto $dto, int $adminId): User
    {
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new \DomainException('Pracownik o tym adresie email już istnieje.');
        }

        if ($this->userRepository->findOneBy(['employeeNumber' => $dto->employeeNumber])) {
            throw new \DomainException('Pracownik o takim numerze kadrowym już istnieje.');
        }

        $adminUuid = Uuid::v4();
        $user = $this->userFactory->createFromDto($dto, $adminUuid);

        $this->em->persist($user);
        $this->em->flush();

        try {
            $accessToken = $this->getKeycloakAdminAccessToken();
            $keycloakUserId = $this->createKeycloakUser($accessToken, $dto, $user);
            $this->assignKeycloakUserRole($accessToken, $keycloakUserId, 'sandbox', 'ROLE_USER');
        } catch (\Throwable $e) {
            $this->em->remove($user);
            $this->em->flush();
            throw new \DomainException('Błąd podczas tworzenia użytkownika w Keycloak: ' . $e->getMessage(), 0, $e);
        }

        $this->userMailer->sendCreated($user);

        try {
            $tokenResponse = $this->httpClient->request('POST', 'http://auth-www/api/auth/internal/email-verification-token', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Internal-Api-Key' => 'secret-key-123',
                ],
                'json' => [
                    'userUuid' => $user->getUuid()?->toRfc4122(),
                    'email' => $dto->email,
                    'keycloakUserId' => $keycloakUserId,
                ],
            ]);

            $tokenData = $tokenResponse->toArray(false);
            $token = is_array($tokenData) ? ($tokenData['token'] ?? null) : null;
            if (!is_string($token) || $token === '') {
                throw new \RuntimeException('Brak token w odpowiedzi z backendAuth.');
            }

            $link = "http://localhost:8081/api/auth/verify-email?token=" . urlencode($token);
            $this->userMailer->sendVerifyMail($link, $dto->email);
        } catch (\Throwable $e) {
            throw new \DomainException('Nie udało się przygotować maila weryfikacyjnego: ' . $e->getMessage(), 0, $e);
        }

        return $user;
    }

    public function updateUser(UserDto $dto, int $adminId): ?User
    {
        $user = $this->userRepository->find($dto->id);

        if (!$user) {
            return null;
        }

        $existingByEmail = $this->userRepository->findOneBy(['email' => $dto->email]);
        if ($existingByEmail && $existingByEmail->getId() !== $user->getId()) {
            throw new \DomainException('Pracownik o tym adresie email już istnieje.');
        }

        $existingByShortName = $this->userRepository->findOneBy(['employeeNumber' => $dto->employeeNumber]);
        if ($existingByShortName && $existingByShortName->getId() !== $user->getId()) {
            throw new \DomainException('Pracownik o takim numerze kadrowym już istnieje.');
        }

        $adminUuid = Uuid::v4();
        $user = $this->userFactory->updateFromDto($dto, $user, $adminUuid);

        $this->em->persist($user);
        $this->em->flush();

        $this->userMailer->sendUpdated($user);

        return $user;
    }

    public function changeActive(int $id, int $adminId): ?User
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return null;
        }

        $adminUuid = Uuid::v4();

        if ($user->isActive()) {
            $user->deactivate($adminUuid);
        } else {
            $user->activate($adminUuid);
        }

        $this->em->flush();

        $this->userMailer->sendChangeActive($user);

        return $user;
    }

    public function deleteCompany(int $id, int $adminId): ?User
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return null;
        }

        $adminUuid = Uuid::v4();
        $user->softDelete($adminUuid);

        $this->em->flush();

        $this->userMailer->sendDeleted($user);

        return $user;
    }

    private function getKeycloakAdminAccessToken(): string
    {
        $response = $this->httpClient->request('POST', 'http://keycloak:8080/realms/master/protocol/openid-connect/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'client_id' => 'admin-cli',
                'username' => 'admin',
                'password' => 'admin',
                'grant_type' => 'password',
            ],
        ]);

        $data = $response->toArray();
        if (!isset($data['access_token']) || !is_string($data['access_token'])) {
            throw new \RuntimeException('Brak access_token w odpowiedzi z Keycloak.');
        }

        return $data['access_token'];
    }

    private function createKeycloakUser(string $accessToken, UserDto $dto, User $user): string
    {
        $response = $this->httpClient->request('POST', 'http://keycloak:8080/admin/realms/sandbox/users', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'json' => [
                'username' => $dto->email,
                'email' => $dto->email,
                'enabled' => true,
                'firstName' => $dto->firstName,
                'lastName' => $dto->lastName,
                'credentials' => [
                    [
                        'type' => 'password',
                        'value' => 'TymczasoweHaslo123!',
                        'temporary' => true,
                    ]
                ],
                'attributes' => [
                    'company_uuid' => $dto->companyUuid->toRfc4122(),
                    'user_uuid' => $user->getUuid()?->toRfc4122(),
                    'employee_number' => $dto->employeeNumber,
                ],
                'requiredActions' => ['UPDATE_PASSWORD', 'VERIFY_EMAIL'],
                'emailVerified' => false
            ],
        ]);

        $headers = $response->getHeaders(false);
        $location = $headers['location'][0] ?? null;
        if (is_string($location) && $location !== '') {
            $parts = explode('/', rtrim($location, '/'));
            $id = end($parts);
            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        $getUserResponse = $this->httpClient->request('GET', 'http://keycloak:8080/admin/realms/sandbox/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => ['email' => $dto->email],
        ]);

        $users = $getUserResponse->toArray();
        $userId = $users[0]['id'] ?? null;
        if (!is_string($userId) || $userId === '') {
            throw new \RuntimeException('Nie udało się pobrać ID użytkownika z Keycloak.');
        }

        return $userId;
    }

    private function assignKeycloakUserRole(string $accessToken, string $userId, string $clientAlias, string $roleName): void
    {
        $clientsResponse = $this->httpClient->request('GET', 'http://keycloak:8080/admin/realms/sandbox/clients', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => ['clientId' => $clientAlias],
        ]);

        $clients = $clientsResponse->toArray();
        $clientId = $clients[0]['id'] ?? null;
        if (!is_string($clientId) || $clientId === '') {
            throw new \RuntimeException('Nie udało się pobrać clientId z Keycloak.');
        }

        $roleResponse = $this->httpClient->request('GET', "http://keycloak:8080/admin/realms/sandbox/clients/{$clientId}/roles/{$roleName}", [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);

        $role = $roleResponse->toArray();
        $roleId = $role['id'] ?? null;
        $roleNameValue = $role['name'] ?? null;
        if (!is_string($roleId) || $roleId === '' || !is_string($roleNameValue) || $roleNameValue === '') {
            throw new \RuntimeException('Nie udało się pobrać roli z Keycloak.');
        }

        $this->httpClient->request('POST', "http://keycloak:8080/admin/realms/sandbox/users/{$userId}/role-mappings/clients/{$clientId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                [
                    'id' => $roleId,
                    'name' => $roleNameValue,
                ]
            ],
        ])->getStatusCode();
    }
}
