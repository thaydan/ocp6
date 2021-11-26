<?php

namespace App\Form;

use App\Entity\TrickImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrickImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
//        $builder->addEventListener(FormEvents::PRE_SET_DATA,
//            function (FormEvent $event) use ($builder)
//            {
//                $form = $event->getForm();
//                $trick = $event->getData();

//                if ($trick instanceof TrickImage) {
                    $builder
                        ->add('title', TextType::class, [
                            'label'=> "Titre de l'image",
                            'required' => true,
                            'row_attr' => [
                                'class' => 'form-floating',
                            ],
                            'attr' => [
                                'placeholder' => "Titre de l'image",
                            ],
                        ])
                        ->add('file', FileType::class, [
                            'required' => false,
                            'label' => false
//                            'attr' => ['readonly' => !!$trick->getId()],
//                            'disabled' => !!$trick->getId()
                        ]);
//                }
//            }
//        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TrickImage::class,
        ]);
    }
}
