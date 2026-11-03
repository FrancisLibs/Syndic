<?php

namespace App\Form;

use App\Entity\Budget;
use App\Entity\Copropriete;
use App\Entity\Exercice;
use App\Form\LigneBudgetType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Range;

class BudgetType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        $builder

            ->add(
                'libelle',
                null,
                [
                    'disabled' => true,
                ]
            )
            ->add(
                'copropriete',
                EntityType::class,
                [
                    'class' => Copropriete::class,
                    'choice_label' => 'nom',
                ]
            )
            ->add(
                'exercice',
                EntityType::class,
                [
                    'class' => Exercice::class,
                ]
            )
            ->add(
                'nbAppels',
                IntegerType::class,
                [
                    'label' => 'Nombre d’appels de fonds',
                    'required' => true,
                    'attr' => [
                        'min' => 1,
                    ],
                    'constraints' => [
                        new Range(min: 1),
                    ],
                    'help' => 'Indiquez 1 pour un appel annuel, 4 pour des appels trimestriels, etc.',
                ]
            )
            ->add(
                'lignes',
                CollectionType::class,
                [
                    'entry_type' => LigneBudgetType::class,

                    'allow_add' => true,

                    'allow_delete' => true,

                    'by_reference' => false,

                    'prototype' => true,
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {

        $resolver->setDefaults(
            [
                'data_class' => Budget::class,
            ]
        );
    }
}
