<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Service\ReservationValidator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ReservationCrudController extends AbstractCrudController
{
    private ReservationValidator $validator;

    public function __construct(ReservationValidator $validator)
    {
        $this->validator = $validator;
    }

    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reserva')
            ->setEntityLabelInPlural('Reservas')
            ->setDefaultSort(['startAt' => 'DESC'])
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig');
    }

    public function configureAssets(Assets $assets): Assets
    {
        // Carga nuestro script JS
        return $assets->addJsFile('js/reservation-check.js');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('customer')->setRequired(true)->autocomplete();
        yield AssociationField::new('vehicle')->setRequired(true)->autocomplete();
        yield AssociationField::new('pickupLocation', 'Retiro')->setRequired(true)->autocomplete();
        yield AssociationField::new('dropoffLocation', 'Devolución')->setRequired(true)->autocomplete();
        yield DateTimeField::new('startAt', 'Inicio')->setFormTypeOption('widget', 'single_text');
        yield DateTimeField::new('endAt', 'Fin')->setFormTypeOption('widget', 'single_text');
        yield ChoiceField::new('status')->setChoices([
            'Pendiente'  => 'pending',
            'Confirmada' => 'confirmed',
            'Cancelada'  => 'cancelled',
        ]);
        yield NumberField::new('totalPrice', 'Precio total')
            ->setNumDecimals(2)
            ->setHelp('Se puede calcular luego en el flujo de reserva');
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /** @var Reservation $reservation */
        $reservation = $entityInstance;

        $available = $this->validator->isVehicleAvailable(
            $reservation->getVehicle()->getId(),
            $reservation->getStartAt(),
            $reservation->getEndAt()
        );

        if (!$available) {
            $this->addFlash('danger', '🚫 El vehículo ya está reservado en ese rango de fechas. Por favor elegí otro.');
            return;
        }

        parent::persistEntity($em, $entityInstance);
        $this->addFlash('success', '✅ Reserva creada correctamente.');
    }

    #[Route('/api/check-availability', name: 'api_check_availability')]
    public function checkAvailability(Request $request): JsonResponse
    {
        $vehicleId  = (int) $request->query->get('vehicleId');
        $startAtStr = (string) $request->query->get('startAt');
        $endAtStr   = (string) $request->query->get('endAt');

        $startAt = $this->parseDateTimeFlexible($startAtStr);
        $endAt   = $this->parseDateTimeFlexible($endAtStr);

        if (!$vehicleId || !$startAt || !$endAt) {
            return $this->json(['available' => false, 'error' => 'missing_parameters']);
        }

        $available = $this->validator->isVehicleAvailable($vehicleId, $startAt, $endAt);

        return $this->json([
            'available' => $available,
            'message' => $available
                ? '✅ Vehículo disponible.'
                : '🚫 Vehículo NO disponible en ese rango.'
        ]);
    }

    private function parseDateTimeFlexible(?string $value): ?\DateTimeImmutable
    {
        if (!$value) return null;

        if (str_contains($value, 'T')) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
            if ($dt instanceof \DateTimeImmutable) return $dt;
        }

        if (preg_match('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2}$#', $value)) {
            $dt = \DateTimeImmutable::createFromFormat('d/m/Y H:i', $value);
            if ($dt instanceof \DateTimeImmutable) return $dt;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
