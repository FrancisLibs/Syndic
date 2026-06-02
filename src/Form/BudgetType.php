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

class BudgetType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        $builder

            ->add('libelle')

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
