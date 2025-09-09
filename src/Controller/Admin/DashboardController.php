<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Entity\VehicleCategory;
use App\Entity\Vehicle;
use App\Entity\VehicleLocationStock;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'ea_dashboard')]
public function index(): Response
{
    return $this->render('admin/dashboard.html.twig');
}

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Car Rental');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Inicio', 'fa fa-home', 'home');
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-gauge');
        yield MenuItem::linkToCrud('Ubicaciones', 'fa fa-location-dot', Location::class);
        yield MenuItem::linkToCrud('Categorías', 'fa fa-tags', VehicleCategory::class);
        yield MenuItem::linkToCrud('Vehículos', 'fa fa-car', Vehicle::class);
        yield MenuItem::linkToCrud('Stock por ubicación', 'fa-solid fa-boxes-stacked', VehicleLocationStock::class);
    }
}

