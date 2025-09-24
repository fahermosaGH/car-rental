<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class ReservationCrudController extends AbstractCrudController
{
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
}
