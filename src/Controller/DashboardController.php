<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EventRepository $eventRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $events = $eventRepository->findAll();
        } else {
            $events = $eventRepository->findByUser($user);
        }

        return $this->render('dashboard/index.html.twig', [
            'events' => $events,
        ]);
    }
}
