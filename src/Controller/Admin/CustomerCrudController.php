<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Cliente')
            ->setEntityLabelInPlural('Clientes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Listado de Clientes')
            ->setPaginatorPageSize(10);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nombre completo');
        yield EmailField::new('email', 'Correo electrónico');
        yield TelephoneField::new('phone', 'Teléfono')->hideOnIndex();

        yield IntegerField::new('reservasCount', 'Reservas activas')
            ->onlyOnIndex()
            ->formatValue(fn($value, $entity) => $entity->getReservations()->count());

        yield DateTimeField::new('createdAt', 'Registrado el')
            ->onlyOnIndex()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield AssociationField::new('reservations', 'Reservas')
            ->onlyOnDetail();
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Customer && !$entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        parent::persistEntity($em, $entityInstance);
    }
}
