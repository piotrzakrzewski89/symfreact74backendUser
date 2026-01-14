<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\Factory\UserFactory;
use App\Application\Service\UserMailer;
use App\Application\Service\UserService;
use App\Domain\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseTestController extends WebTestCase
{
    protected KernelBrowser $client;
    protected ValidatorInterface $validator;
    protected UserRepository $repo;
    protected EntityManagerInterface $em;
    protected UserFactory $factory;
    protected UserService $service;
    protected MessageBusInterface $messageBus;
    protected UserMailer $userMailer;

    protected function setUp(): void
    {
        $this->setUpClient();
        $container = static::getContainer();
        $connection = $container->get('database_connection');
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
        $this->repo = $this->createMock(UserRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->factory = $this->createMock(UserFactory::class);
        // Zamockowany MessageBus w kontenerze
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->client->getContainer()->set(MessageBusInterface::class, $this->messageBus);
        $this->client->disableReboot();
        
        $this->userMailer = $this->createMock(UserMailer::class);
        // Przywróć stan bazy
        $connection->executeStatement('TRUNCATE TABLE "user" RESTART IDENTITY CASCADE');
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown(); // zakończ kernel żeby nie było błędów w kolejnych testach
        parent::tearDown();
    }

    protected function response(): ?Response
    {
        return $this->client->getResponse();
    }

    protected function setUpClient(): void
    {
        self::ensureKernelShutdown();
        parent::setUp();
        $this->client = static::createClient();

        $this->client->disableReboot();
    }

    protected function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null): void
    {
        $this->client->request($method, $uri, $parameters, $files, $server, $content);
    }
}
