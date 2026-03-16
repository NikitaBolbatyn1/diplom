<?php

namespace App\Controller;

use App\Entity\Faculty;
use App\Form\FacultyType;
use App\Repository\FacultyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/faculty')]
class FacultyController extends AbstractController
{
    #[Route('/', name: 'faculty_index', methods: ['GET'])]
    public function index(FacultyRepository $facultyRepository): Response
    {
        $faculties = $facultyRepository->findAllOrderedByName();

        return $this->render('faculty/index.html.twig', [
            'faculties' => $faculties,
        ]);
    }

    #[Route('/new', name: 'faculty_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $faculty = new Faculty();
        $form = $this->createForm(FacultyType::class, $faculty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($faculty);
            $em->flush();

            $this->addFlash('success', 'Факультет успешно добавлен.');
            return $this->redirectToRoute('faculty_index');
        }

        return $this->render('faculty/form.html.twig', [
            'form' => $form,
            'title' => 'Добавить факультет',
        ]);
    }

    #[Route('/{id}/edit', name: 'faculty_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Faculty $faculty, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FacultyType::class, $faculty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faculty->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Факультет успешно обновлён.');
            return $this->redirectToRoute('faculty_index');
        }

        return $this->render('faculty/form.html.twig', [
            'form' => $form,
            'title' => 'Редактировать факультет',
        ]);
    }

    #[Route('/{id}/delete', name: 'faculty_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Faculty $faculty, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$faculty->getId(), $request->request->get('_token'))) {
            // Проверяем, есть ли связанные мероприятия
            if ($faculty->getEvents()->count() > 0) {
                $this->addFlash('error', 'Нельзя удалить факультет, к которому привязаны мероприятия.');
                return $this->redirectToRoute('faculty_index');
            }

            $em->remove($faculty);
            $em->flush();
            $this->addFlash('success', 'Факультет успешно удалён.');
        }

        return $this->redirectToRoute('faculty_index');
    }
}
