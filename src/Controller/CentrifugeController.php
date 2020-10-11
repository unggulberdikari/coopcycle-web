<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CentrifugeController extends AbstractController
{
    /**
     * @link https://centrifugal.github.io/centrifugo/server/proxy/#connect-proxy
     * @Route("/centrifuge/connect", name="centrifuge_connect")
     */
    public function connectAction(Request $request)
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([]);
        }

        return new JsonResponse(['result' => [
            'user' => $user->getUsername(),
        ]]);
    }
}
