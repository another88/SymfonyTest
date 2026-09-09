<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Создаёт пользователя для входа в API или меняет пароль существующему')]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('login', InputArgument::REQUIRED, 'Логин')
            ->addArgument('password', InputArgument::REQUIRED, 'Пароль в открытом виде')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Дополнительная роль, например ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $login = $input->getArgument('login');

        // Если логин занят, перезаписываем пароль и роли.
        $user = $this->userRepository->findOneBy(['login' => $login]);
        $isNew = null === $user;

        if ($isNew) {
            $user = new User();
            $user->setLogin($login);
        }

        $user->setRoles($input->getOption('role'));
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $input->getArgument('password'))
        );

        $this->userRepository->save($user);

        $io->success(sprintf(
            'Пользователь "%s" %s, роли: %s',
            $user->getLogin(),
            $isNew ? 'создан' : 'обновлён',
            implode(', ', $user->getRoles())
        ));

        return Command::SUCCESS;
    }
}
