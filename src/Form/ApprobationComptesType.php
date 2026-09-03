<?php

namespace App\Form;

use App\Entity\ApprobationComptes;
use App\Entity\Exercice;
use App\Repository\ExerciceRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\QueryBuilder;

final class ApprobationComptesType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('dateAssembleeGenerale', DateType::class, [
                'label' => 'Date de l’Assemblée Générale',
                'widget' => 'single_text',
            ])
            ->add('numeroResolution', TextType::class, [
                'label' => 'Numéro de résolution',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex. Résolution n° 5',
                ],
            ])
            ->add(
                'exerciceComptabilisation',
                EntityType::class,
                [
                    'class' => Exercice::class,

                    'query_builder' => function (
                        ExerciceRepository $repository
                    ): QueryBuilder {
                        return $repository
                            ->createQueryBuilder('e')
                            ->andWhere('e.cloture = :cloture')
                            ->setParameter('cloture', false)
                            ->orderBy('e.dateDebut', 'ASC');
                    },

                    'choice_label' => 'nom',
                    'label' => 'Exercice de comptabilisation',
                    'placeholder' => 'Choisir un exercice',
                    'required' => true,
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => ApprobationComptes::class,
        ]);
    }
}
