<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-admin', description: 'Crea un usuario admin por defecto (admin@local / admin123)')]
class SeedAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(User::class);
        if ($repo->findOneBy(['email' => 'admin@local'])) {
            $output->writeln('<info>Ya existe admin@local. No se hizo nada.</info>');
            return Command::SUCCESS;
        }

        $u = new User();
        $u->setEmail('admin@local');
        $u->setFirstName('Admin');
        $u->setLastName('Local');
        $u->setRoles(['ROLE_ADMIN']);
        $u->setPassword($this->hasher->hashPassword($u, 'admin123'));

        $this->em->persist($u);
        $this->em->flush();

        $output->writeln('<info>Usuario creado: admin@local / admin123</info>');
        return Command::SUCCESS;
    }
}