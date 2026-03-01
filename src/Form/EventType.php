<?php

namespace App\Form;

use App\Entity\Event;
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
