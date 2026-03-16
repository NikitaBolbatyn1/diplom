<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Faculty;
use App\Repository\FacultyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Дата',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('time', TimeType::class, [
                'label' => 'Время',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('place', TextType::class, [
                'label' => 'Место проведения',
            ])
            ->add('title', TextType::class, [
                'label' => 'Название мероприятия',
            ])
            ->add('responsible', TextType::class, [
                'label' => 'Ответственный',
            ])
            ->add('faculty', EntityType::class, [
                'class' => Faculty::class,
                'choice_label' => 'name',
                'label' => 'Факультет/Отдел',
                'placeholder' => '-- Выберите факультет --',
                'required' => false,
                'query_builder' => function (FacultyRepository $repo) {
                    return $repo->createQueryBuilder('f')
                        ->orderBy('f.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
