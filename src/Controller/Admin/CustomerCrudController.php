<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('first_name', 'Nombre'),
            TextField::new('last_name', 'Apellido'),
            TextField::new('email', 'Email'),
            TextField::new('phone', 'Teléfono')->hideOnIndex(),
            TextField::new('document_number', 'Documento')->hideOnIndex(),
            DateTimeField::new('created_at', 'Creado')->onlyOnIndex(),
        ];
    }
}
