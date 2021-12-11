<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Trick;
use App\Entity\TrickImage;
use App\Repository\TrickImageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Trick $trick */
        $trick = $builder->getData();
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Titre',
                ],
                'label' => 'Titre',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('slug', TextType::class, [
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('description', TextareaType::class, [
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('images', CollectionType::class, [
                'entry_type' => TrickImageType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'attr' => [
                    'class' => 'collection'
                ],
            ])
            ->add('videos', CollectionType::class, [
                'entry_type' => TrickVideoType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'attr' => [
                    'class' => 'collection'
                ],
            ])
            ->add('group', EntityType::class, [
                'class' => Group::class,
                'label'=> 'Groupe'
            ]);
        if ($trick->getId()) {
            $builder
                ->add('featuredImage', EntityType::class, [
                    'class' => TrickImage::class,
                    'expanded' => true,
                    'label' => 'Image mise en avant',
                    'query_builder' => function (TrickImageRepository $trickImageRepository) use ($trick) {
                        return $trickImageRepository->createQueryBuilder('trickImage')
                            ->where('trickImage.trick = :trick')
                            ->setParameter('trick', $trick);
                    },
                ]);
        }
        $builder
            ->add('cancel', SubmitType::class, [
                'label' => 'Annuler'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
