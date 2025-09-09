<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Entity\Vehicle;
use App\Entity\VehicleCategory;
use App\Entity\Customer;

// Opcionales (según cómo la haya nombrado tu compañero)
use App\Entity\VehicleLocationStock;
use App\Entity\VehicleStock;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    // Nombre que EasyAdmin espera para el dashboard
    #[Route('/admin', name: 'ea_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Car Rental - Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Catálogo');
        yield MenuItem::linkToCrud('Ubicaciones', 'fa fa-location-dot', Location::class);
        yield MenuItem::linkToCrud('Categorías', 'fa fa-tags', VehicleCategory::class);
        yield MenuItem::linkToCrud('Vehículos', 'fa fa-car', Vehicle::class);

        // Trabajo de tu compañero (Stock por ubicación)
        if (class_exists(VehicleLocationStock::class)) {
            yield MenuItem::section('Stock');
            yield MenuItem::linkToCrud('Stock por ubicación', 'fa fa-warehouse', VehicleLocationStock::class);
        } elseif (class_exists(VehicleStock::class)) {
            yield MenuItem::section('Stock');
            yield MenuItem::linkToCrud('Stock por ubicación', 'fa fa-warehouse', VehicleStock::class);
        }

        // Tu trabajo (Clientes)
        yield MenuItem::section('Clientes');
        yield MenuItem::linkToCrud('Clientes', 'fa fa-users', Customer::class);
    }
}
