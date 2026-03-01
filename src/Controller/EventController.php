<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/new', name: 'event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setCreatedBy($this->getUser());
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Мероприятие добавлено.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'title' => 'Добавить мероприятие',
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit')]
    public function edit(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        // Проверяем, что пользователь либо админ, либо создатель
        if ($event->getCreatedBy() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Мероприятие обновлено.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'title' => 'Редактировать мероприятие',
        ]);
    }

    #[Route('/{id}/delete', name: 'event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        if ($event->getCreatedBy() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Мероприятие удалено.');
        }

        return $this->redirectToRoute('app_dashboard');
    }
}
