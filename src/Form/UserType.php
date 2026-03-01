<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('fullName', TextType::class, [
                'label' => 'ФИО',
            ])
            ->add('department', TextType::class, [
                'label' => 'Отдел/Кафедра',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Роли',
                'choices' => [
                    'Редактор' => 'ROLE_EDITOR',
                    'Администратор' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('isActive', null, [
                'label' => 'Активен',
            ])
        ;

        // Добавляем поле пароля только при создании нового пользователя
        if (!$options['is_edit']) {
            $builder->add('plainPassword', PasswordType::class, [
                'label' => 'Пароль',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Введите пароль'),
                    new Length(min: 6, max: 4096),
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
