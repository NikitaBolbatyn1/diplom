<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/new', name: 'admin_user_new')]
    public function newUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Пользователь создан.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form,
            'title' => 'Новый пользователь',
        ]);
    }

    #[Route('/users/{id}/edit', name: 'admin_user_edit')]
    public function editUser(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Если пароль был введен, то хешируем и сохраняем
            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                $plainPassword = $form->get('plainPassword')->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();
            $this->addFlash('success', 'Пользователь обновлен.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form,
            'title' => 'Редактировать пользователя',
        ]);
    }

    #[Route('/users/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggleUser(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(!$user->isIsActive());
        $em->flush();
        $this->addFlash('success', 'Статус пользователя изменен.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/export', name: 'admin_export')]
    public function export(Request $request, EventRepository $eventRepository): Response
    {
        // Получаем параметры фильтрации (можно добавить форму, но для простоты - выгружаем все)
        $start = $request->query->get('start') ? new \DateTime($request->query->get('start')) : null;
        $end = $request->query->get('end') ? new \DateTime($request->query->get('end')) : null;

        $events = $eventRepository->findAllForExport($start, $end);

        // Используем PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Дата');
        $sheet->setCellValue('C1', 'Время');
        $sheet->setCellValue('D1', 'Место');
        $sheet->setCellValue('E1', 'Название');
        $sheet->setCellValue('F1', 'Ответственный');
        $sheet->setCellValue('G1', 'Кто добавил');
        $sheet->setCellValue('H1', 'Email редактора');

        $row = 2;
        foreach ($events as $event) {
            $sheet->setCellValue('A' . $row, $event->getId());
            $sheet->setCellValue('B' . $row, $event->getDate()->format('d.m.Y'));
            $sheet->setCellValue('C' . $row, $event->getTime()->format('H:i'));
            $sheet->setCellValue('D' . $row, $event->getPlace());
            $sheet->setCellValue('E' . $row, $event->getTitle());
            $sheet->setCellValue('F' . $row, $event->getResponsible());
            $sheet->setCellValue('G' . $row, $event->getCreatedBy()->getFullName());
            $sheet->setCellValue('H' . $row, $event->getCreatedBy()->getEmail());
            $row++;
        }

        // Создаём временный файл
        $fileName = 'events_'.date('Y-m-d').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Отправляем файл как ответ
        return $this->file($tempFile, $fileName, 'attachment', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        // Не даём удалить самого себя
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Вы не можете удалить свою собственную учётную запись.');
            return $this->redirectToRoute('admin_users');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Пользователь удалён.');
        } else {
            $this->addFlash('error', 'Недействительный CSRF-токен.');
        }

        return $this->redirectToRoute('admin_users');
    }
}
