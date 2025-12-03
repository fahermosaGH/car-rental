<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Usuario')
            ->setEntityLabelInPlural('Usuarios')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // ID solo en el listado
        yield IdField::new('id', 'ID')->onlyOnIndex();

        // Básicos
        yield TextField::new('email', 'Email');

        // Roles: solo en index
        yield ArrayField::new('roles', 'Roles')->onlyOnIndex();

        // Nombre / Apellido
        yield TextField::new('first_name', 'Nombre');
        yield TextField::new('last_name', 'Apellido');

        // Fecha de alta
        yield DateTimeField::new('created_at', 'Creado')->onlyOnIndex();

        // Perfil
        yield TextField::new('phone', 'Teléfono')->hideOnIndex();
        yield TextField::new('document_number', 'Documento')->hideOnIndex();
        yield DateField::new('birth_date', 'Fecha de nacimiento')->hideOnIndex();
        yield TextField::new('address', 'Dirección')->hideOnIndex();
        yield TextField::new('license_number', 'N° licencia')->hideOnIndex();
        yield TextField::new('license_country', 'País licencia')->hideOnIndex();
        yield DateField::new('license_expiry', 'Venc. licencia')->hideOnIndex();

        // Password: solo en formularios, nunca en el listado
        yield TextField::new('password', 'Password')
            ->onlyOnForms()
            ->setFormType(PasswordType::class)
            ->setHelp('Solo completar si querés cambiar la contraseña.');
    }
}

