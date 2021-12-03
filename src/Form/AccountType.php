<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'Adresse e-mail',
                ],
                'label' => 'Adresse e-mail',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('username', TextType::class, [
                'attr' => [
                    'placeholder' => 'Pseudo',
                ],
                'label' => 'Pseudo',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Prénom',
                ],
                'label' => 'Prénom',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom de famille',
                ],
                'label' => 'Nom de famille',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
