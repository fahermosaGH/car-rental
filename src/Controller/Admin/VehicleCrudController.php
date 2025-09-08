<?php

namespace App\Controller\Admin;

use App\Entity\Vehicle;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class VehicleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Vehicle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('category')
                ->setRequired(true),
            TextField::new('brand'),
            TextField::new('model'),
            IntegerField::new('year'),
            IntegerField::new('seats'),
            ChoiceField::new('transmission')
                ->setChoices([
                    'Manual' => 'manual',
                    'Automática' => 'automatica',
                ]),
            NumberField::new('dailyPriceOverride')
                ->setNumDecimals(2)
                ->setRequired(false),
            BooleanField::new('isActive'),
        ];
    }
}

