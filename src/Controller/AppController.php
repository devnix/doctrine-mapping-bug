<?php

namespace App\Controller;

use App\Entity\App;
use App\Repository\AppRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    public function __construct(
        private readonly AppRepository $appRepository
    )
    {
    }

    #[Route('/apps', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $apps = $this->appRepository->findAll();
        return $this->json($apps);
    }

    #[Route('/apps', methods: ['POST'])]
    public function createApp(): JsonResponse
    {
        $app = App::create($this->appRepository->nextIdentity());
        $this->appRepository->save($app);
        return $this->json(['id' => $app->getId()]);
    }

    #[Route('/apps/{id}', methods: ['GET'])]
    public function showApp(string $id): JsonResponse
    {
        $app = $this->appRepository->get($id);
        return $this->json($app);
    }

    #[Route('/apps/{id}', methods: ['POST'])]
    public function createUser(string $id, Request $request): Response
    {
        $app = $this->appRepository->get($id);

        $alias = $request->request->get('alias');
        if (!is_string($alias)) {
            throw new BadRequestException('Missing "alias" parameter');
        }

        $username = $request->request->get('username');
        if (!is_string($username)) {
            throw new BadRequestException('Missing "username" parameter');
        }

        $password = $request->request->get('password');
        if (!is_string($password)) {
            throw new BadRequestException('Missing "password" parameter');
        }

        $app->createUser($alias, $username, $password);
        $this->appRepository->save($app);
        return $this->json('');
    }

    #[Route('/apps/{id}/login', methods: ['GET'])]
    public function login(string $id, Request $request): Response
    {
        $app = $this->appRepository->get($id);

        $username = $request->query->get('username');
        if (!is_string($username)) {
            throw new BadRequestException('Missing "username" parameter');
        }

        $password = $request->query->get('password');
        if (!is_string($password)) {
            throw new BadRequestException('Missing "password" parameter');
        }

        return new JsonResponse($app->login($username, $password));
    }

    #[Route('/apps/{id}/{username}/changePassword', methods: ['PUT'])]
    public function changePassword(Request $request, string $id, string $username): JsonResponse
    {
        $app = $this->appRepository->get($id);

        $newPassword = $request->request->get('newPassword');
        if (!is_string($newPassword)) {
            throw new BadRequestException('Missing "newPassword" parameter');
        }

        $app->updateUserPassword($username, $newPassword);

        $this->appRepository->save($app);

        return $this->json('');
    }

    #[Route('/apps/{id}/{username}', methods: ['GET'])]
    public function showUser(string $id, string $username): JsonResponse
    {
        $app = $this->appRepository->get($id);
        return $this->json($app->getUserByUsername($username));
    }

    #[Route('/apps/{id}/{username}', methods: ['DELETE'])]
    public function deleteUser(string $id, string $username): Response
    {
        $app = $this->appRepository->get($id);
        $app->removeUser($username);

        $this->appRepository->save($app);

        return new Response('');
    }
}
