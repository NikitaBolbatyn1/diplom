<?php

namespace App\Form;

use App\Entity\Faculty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FacultyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Название факультета',
                'attr' => ['placeholder' => 'Например: Инженерный факультет']
            ])
            ->add('headName', TextType::class, [
                'label' => 'Заведующий кафедры (ФИО)',
                'attr' => ['placeholder' => 'Иванов Иван Иванович']
            ])
            ->add('headDegree', TextType::class, [
                'label' => 'Учёная степень',
                'required' => false,
                'attr' => ['placeholder' => 'Например: к.т.н., д.ф.-м.н.']
            ])
            ->add('headPosition', TextType::class, [
                'label' => 'Должность',
                'required' => false,
                'attr' => ['placeholder' => 'Например: доцент, профессор']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Телефон',
                'required' => false,
                'attr' => ['placeholder' => '+7 (XXX) XXX-XX-XX']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['placeholder' => 'example@university.ru']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faculty::class,
        ]);
    }
}
