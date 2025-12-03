<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Entity\Location;
use App\Entity\Vehicle;
use App\Entity\VehicleCategory;
use App\Entity\User;
use App\Entity\Customer;
use App\Entity\VehicleLocationStock;

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

        if (class_exists(VehicleLocationStock::class)) {
            yield MenuItem::section('Stock');
            yield MenuItem::linkToCrud('Stock por ubicación', 'fa fa-warehouse', VehicleLocationStock::class);
        } elseif (class_exists(VehicleStock::class)) {
            yield MenuItem::section('Stock');
            yield MenuItem::linkToCrud('Stock por ubicación', 'fa fa-warehouse', VehicleStock::class);
        }

        yield MenuItem::section('Usuarios');
        yield MenuItem::linkToCrud('Usuarios', 'fa fa-user', User::class);

        yield MenuItem::section('Operaciones');
        yield MenuItem::linkToCrud('Reservas', 'fa fa-calendar-check', Reservation::class);
    }
}
