<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('site/home.html.twig');
    }

    #[Route('/cotizar', name: 'quote')]
    public function quote(): Response
    {
        return $this->render('site/quote.html.twig');
    }

    #[Route('/reservar', name: 'reserve')]
    public function reserve(): Response
    {
        return $this->render('site/reserve.html.twig');
    }

    #[Route('/mis-reservas', name: 'my_reservations')]
    public function myReservations(): Response
    {
        return $this->render('site/my_reservations.html.twig');
    }
}
