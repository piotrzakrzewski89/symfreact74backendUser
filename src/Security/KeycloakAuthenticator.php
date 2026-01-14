<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\Security\Core\User\InMemoryUser;

class KeycloakAuthenticator extends AbstractAuthenticator
{
    private Configuration $jwtConfig;

    public function __construct()
    {
        $this->jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::empty(),
            InMemory::file(dirname(__DIR__, 2) . '/config/jwt/keycloak-public.pem')
        );
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') &&
            str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $authHeader = $request->headers->get('Authorization');
            $tokenString = substr($authHeader, 7);

            $token = $this->jwtConfig->parser()->parse($tokenString);

            $this->jwtConfig->validator()->assert(
                $token,
                new SignedWith($this->jwtConfig->signer(), $this->jwtConfig->verificationKey())
            );

            $claims = $token->claims()->all();
            $username = $claims['preferred_username'] ?? 'unknown';
            $roles = $claims['resource_access']['sandbox']['roles'] ?? [];

            return new SelfValidatingPassport(new UserBadge($username, fn() => new InMemoryUser($username, null, $roles)));
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // kontynuuj request
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
}
