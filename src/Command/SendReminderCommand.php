<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:send-monthly-reminder', description: 'Отправляет напоминания о заполнении отчета 25 числа')]
class SendReminderCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private string $appUrl
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findBy(['isActive' => true]);

        foreach ($users as $user) {
            // Отправляем только редакторам (не админам), но можно и всем, у кого есть роль EDITOR
            if (!in_array('ROLE_EDITOR', $user->getRoles())) {
                continue;
            }

            $email = (new Email())
                ->from('noreply@vospit.ru')
                ->to($user->getEmail())
                ->subject('Напоминание о заполнении отчета по воспитательной работе')
                ->html(sprintf(
                    '<p>Уважаемый(ая) %s, просим вас заполнить данные о проведенных мероприятиях за текущий месяц в системе учета.</p><p><a href="%s">%s</a></p>',
                    $user->getFullName(),
                    $this->appUrl,
                    $this->appUrl
                ));

            $this->mailer->send($email);
            $output->writeln('Отправлено пользователю: ' . $user->getEmail());
        }

        return Command::SUCCESS;
    }
}
