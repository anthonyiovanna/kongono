<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Http\HttpException;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Middleware;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Mail\Email;
use Kongono\Mail\Mailer;
use Kongono\Security\Attributes\Can;
use Kongono\Security\Middleware\BearerAuthMiddleware;
use Kongono\Security\Principal;
use Kongono\Security\TokenSigner;
use StarterApp\Domain\UserStore;

#[Prefix('/api/auth')]
final readonly class AuthController
{
    public function __construct(
        private UserStore $users,
        private TokenSigner $signer,
        private Mailer $mailer,
    ) {}

    #[Post('/register', name: 'api.auth.register')]
    public function register(RegisterInput $input): Response
    {
        if (null !== $this->users->findByEmail($input->email)) {
            throw new HttpException(409, 'An account with this email already exists.');
        }

        $user = $this->users->register($input->name, $input->email, $input->password);
        $this->mailer->sendView('emails/welcome', ['name' => $user['name']], static fn(Email $e): Email => $e
            ->to($user['email'], $user['name'])
            ->subject('Welcome to Kongono!'));

        $token = $this->signer->sign(['sub' => $user['id'], 'email' => $user['email'], 'name' => $user['name']], ttlSeconds: 3600);

        return Response::json(['user' => $user, 'token' => $token], status: 201);
    }

    #[Post('/login', name: 'api.auth.login')]
    public function login(LoginInput $input): Response
    {
        $user = $this->users->verifyCredentials($input->email, $input->password);
        if (null === $user) {
            throw new HttpException(401, 'Invalid email or password.');
        }

        $token = $this->signer->sign(['sub' => $user['id'], 'email' => $user['email'], 'name' => $user['name']], ttlSeconds: 3600);

        return Response::json(['user' => $user, 'token' => $token]);
    }

    #[Get('/me', name: 'api.auth.me')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('notes.read')]
    public function me(Principal $principal): Response
    {
        return Response::json([
            'id' => $principal->id,
            'kind' => $principal->kind->value,
            'capabilities' => $principal->capabilities,
            'attributes' => $principal->attributes,
        ]);
    }
}
