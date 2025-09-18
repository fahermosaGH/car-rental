<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservar', name: 'app_reserve')]
    public function index(): Response
    {
        return $this->render('reservation/index.html.twig', [
            'title' => 'Reserva (stub)',
        ]);
    }
}