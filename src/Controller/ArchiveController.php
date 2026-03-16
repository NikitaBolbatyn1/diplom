<?php

namespace App\Controller;

use App\Entity\ArchiveFile;
use App\Repository\ArchiveFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EventRepository;
use App\Repository\FacultyRepository;
use App\Repository\UserRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[Route('/archive')]
#[IsGranted('ROLE_ADMIN')]
class ArchiveController extends AbstractController
{
    #[Route('/', name: 'archive_index', methods: ['GET'])]
    public function index(ArchiveFileRepository $archiveRepository): Response
    {
        $files = $archiveRepository->findAllOrderedByDownloadedAt();

        return $this->render('archive/index.html.twig', [
            'files' => $files,
        ]);
    }

    #[Route('/{id}/delete', name: 'archive_delete', methods: ['POST'])]
    public function delete(Request $request, ArchiveFile $archiveFile, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$archiveFile->getId(), $request->request->get('_token'))) {
            // Удаляем файл с сервера
            $filePath = $this->getParameter('archive_directory') . '/' . $archiveFile->getStoredFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Удаляем запись из БД
            $em->remove($archiveFile);
            $em->flush();

            $this->addFlash('success', 'Файл успешно удалён из архива.');
        }

        return $this->redirectToRoute('archive_index');
    }

    #[Route('/{id}/download', name: 'archive_download', methods: ['GET'])]
    public function download(ArchiveFile $archiveFile): Response
    {
        $filePath = $this->getParameter('archive_directory') . '/' . $archiveFile->getStoredFilename();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Файл не найден на сервере.');
            return $this->redirectToRoute('archive_index');
        }

        return $this->file($filePath, $archiveFile->getOriginalFilename());
    }

    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(FacultyRepository $facultyRepository): Response
    {
        $statistics = $facultyRepository->getStatistics();

        // Общая статистика
        $totalEvents = array_sum(array_column($statistics, 'total_events'));
        $totalFaculties = count($statistics);
        $facultiesWithEvents = count(array_filter($statistics, fn($s) => $s['total_events'] > 0));

        return $this->render('admin/dashboard.html.twig', [
            'statistics' => $statistics,
            'totalEvents' => $totalEvents,
            'totalFaculties' => $totalFaculties,
            'facultiesWithEvents' => $facultiesWithEvents,
        ]);
    }

    #[Route('/export', name: 'admin_export', methods: ['GET'])]
    public function export(Request $request, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $start = $request->query->get('start') ? new \DateTime($request->query->get('start')) : null;
        $end = $request->query->get('end') ? new \DateTime($request->query->get('end')) : null;

        $events = $eventRepository->findAllForExport($start, $end);

        // Создаем Excel файл
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Дата');
        $sheet->setCellValue('C1', 'Время');
        $sheet->setCellValue('D1', 'Место');
        $sheet->setCellValue('E1', 'Название');
        $sheet->setCellValue('F1', 'Ответственный');
        $sheet->setCellValue('G1', 'Факультет');
        $sheet->setCellValue('H1', 'Кто добавил');
        $sheet->setCellValue('I1', 'Email редактора');

        $row = 2;
        foreach ($events as $event) {
            $sheet->setCellValue('A' . $row, $event->getId());
            $sheet->setCellValue('B' . $row, $event->getDate()->format('d.m.Y'));
            $sheet->setCellValue('C' . $row, $event->getTime()->format('H:i'));
            $sheet->setCellValue('D' . $row, $event->getPlace());
            $sheet->setCellValue('E' . $row, $event->getTitle());
            $sheet->setCellValue('F' . $row, $event->getResponsible());
            $sheet->setCellValue('G' . $row, $event->getFaculty() ? $event->getFaculty()->getName() : 'Не указан');
            $sheet->setCellValue('H' . $row, $event->getCreatedBy()->getFullName());
            $sheet->setCellValue('I' . $row, $event->getCreatedBy()->getEmail());
            $row++;
        }

        // Генерируем имя файла: дата время + "Список мероприятий"
        $dateTime = new \DateTime();
        $fileName = $dateTime->format('Y-m-d_H-i-s') . '_Список_мероприятий.xlsx';

        // Сохраняем на сервере
        $archiveDir = $this->getParameter('archive_directory');
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0777, true);
        }

        $filePath = $archiveDir . '/' . $fileName;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        // Сохраняем запись в архиве
        $archiveFile = new ArchiveFile();
        $archiveFile->setOriginalFilename($fileName);
        $archiveFile->setStoredFilename($fileName);
        $archiveFile->setFilePath($filePath);
        $archiveFile->setDownloadedBy($this->getUser());
        $archiveFile->setFileSize(filesize($filePath));
        $archiveFile->setFileType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $em->persist($archiveFile);
        $em->flush();

        // Отправляем файл пользователю
        return $this->file($filePath, $fileName);
    }
}
