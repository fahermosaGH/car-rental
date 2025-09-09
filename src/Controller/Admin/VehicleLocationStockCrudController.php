<?php

namespace App\Controller\Admin;

use App\Entity\VehicleLocationStock;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class VehicleLocationStockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return VehicleLocationStock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Stock por ubicación')
            ->setEntityLabelInPlural('Stock por ubicación')
            ->setDefaultSort(['vehicle' => 'ASC', 'location' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('vehicle')
            ->setRequired(true)
            ->autocomplete();

        yield AssociationField::new('location')
            ->setRequired(true)
            ->autocomplete();

        yield IntegerField::new('quantity', 'Cantidad')
            ->setFormTypeOption('attr', ['min' => 0])
            ->setHelp('Unidades disponibles en esa ubicación');
    }
}

