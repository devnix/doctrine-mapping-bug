<?php

namespace App\Controller;

use App\Entity\App;
use App\Repository\AppRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private AppRepository $appRepository
    )
    {
    }

    #[Route('/app')]
    public function index(): JsonResponse
    {
        $apps = $this->appRepository->findAll();
        return $this->json($apps);
    }

    #[Route('/app/create')]
    public function create(): Response
    {
        $app = App::create($this->appRepository->nextIdentity());

        $this->appRepository->save($app);

        return new Response('');
    }

    #[Route('/app/{id}')]
    public function show(string $id): JsonResponse
    {
        $app = $this->appRepository->find($id);
        return $this->json($app);
    }

    #[Route('/app/{id}/create')]
    public function createUser(string $id, Request $request): Response
    {
        $app = $this->appRepository->find($id);

        $app->createUser($request->query->get('alias'), $request->query->get('username'), $request->query->get('password'));
        $this->appRepository->save($app);

        return new Response('');
    }

    #[Route('/app/{id}/login')]
    public function login(string $id, Request $request): Response
    {
        $app = $this->appRepository->find($id);
        return new JsonResponse($app->login($request->query->get('username'), $request->query->get('password')));
    }

    #[Route('/app/{id}/{username}/changePassword')]
    public function changePassword(Request $request, string $id, string $username): Response
    {
        $app = $this->appRepository->find($id);
        $app->updateUserPassword($username, $request->query->get('password'));

        $this->appRepository->save($app);

        return new Response('');
    }

    #[Route('/app/{id}/{username}/delete')]
    public function deleteUser(Request $request, string $id, string $username): Response
    {
        $app = $this->appRepository->find($id);
        $app->removeUser($username);

        $this->appRepository->save($app);

        return new Response('');
    }

}
