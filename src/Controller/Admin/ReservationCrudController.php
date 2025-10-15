<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use App\Service\ReservationValidator;
use Doctrine\ORM\EntityManagerInterface;

class ReservationCrudController extends AbstractCrudController
{
    public function __construct(private ReservationValidator $validator) {}

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reserva')
            ->setEntityLabelInPlural('Reservas')
            ->setDefaultSort(['startAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('customer')->setRequired(true)->autocomplete();
        yield AssociationField::new('vehicle')->setRequired(true)->autocomplete();
        yield AssociationField::new('pickupLocation', 'Retiro')->setRequired(true)->autocomplete();
        yield AssociationField::new('dropoffLocation', 'Devolución')->setRequired(true)->autocomplete();

        yield DateTimeField::new('startAt', 'Inicio')->setFormTypeOption('widget', 'single_text');
        yield DateTimeField::new('endAt', 'Fin')->setFormTypeOption('widget', 'single_text');

        yield ChoiceField::new('status')
            ->setChoices([
                'Pendiente'  => 'pending',
                'Confirmada' => 'confirmed',
                'Cancelada'  => 'cancelled',
            ]);

        yield NumberField::new('totalPrice', 'Precio total')
            ->setNumDecimals(2)
            ->setHelp('Se puede calcular luego en el flujo de reserva');
    }

    public function configureAssets(Assets $assets): Assets
    {
        // Minimal y estable: EasyAdmin carga su propio CSS.
        return $assets
            ->addJsFile('js/reservation-check.js');
            // Si más adelante querés el modal "Ver conflicto", descomentá:
            // ->addJsFile('js/reservation-conflict-modal.js');
            // Dejamos fuera Flatpickr/datepicker por ahora.
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Reservation) {
            $vehicleId = $entityInstance->getVehicle()?->getId();
            $pickupId  = $entityInstance->getPickupLocation()?->getId(); // <-- sucursal de retiro
            $start     = $entityInstance->getStartAt();
            $end       = $entityInstance->getEndAt();

            if (!$vehicleId || !$pickupId || !$start || !$end || $start >= $end) {
                $this->addFlash('danger', 'Datos incompletos o fechas inválidas (Fin > Inicio y seleccionar vehículo/sucursal).');
                return;
            }

            // Disponibilidad por stock en sucursal (fin exclusivo)
            $ok = $this->validator->isAvailable($vehicleId, $pickupId, $start, $end, null);
            if (!$ok) {
                $this->addFlash('danger', 'No hay unidades disponibles para ese vehículo en esa sucursal y rango.');
                return;
            }
        }

        parent::persistEntity($em, $entityInstance);
        $this->addFlash('success', 'Reserva creada.');
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Reservation) {
            $vehicleId = $entityInstance->getVehicle()?->getId();
            $pickupId  = $entityInstance->getPickupLocation()?->getId(); // <-- sucursal de retiro
            $start     = $entityInstance->getStartAt();
            $end       = $entityInstance->getEndAt();

            if (!$vehicleId || !$pickupId || !$start || !$end || $start >= $end) {
                $this->addFlash('danger', 'Datos incompletos o fechas inválidas (Fin > Inicio y seleccionar vehículo/sucursal).');
                return;
            }

            // Excluye la propia reserva al editar
            $ok = $this->validator->isAvailable($vehicleId, $pickupId, $start, $end, $entityInstance->getId());
            if (!$ok) {
                $this->addFlash('danger', 'No hay unidades disponibles para ese vehículo en esa sucursal y rango.');
                return;
            }
        }

        parent::updateEntity($em, $entityInstance);
        $this->addFlash('success', 'Reserva actualizada.');
    }
}

