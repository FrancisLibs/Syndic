<?php

namespace App\Form;

use App\Entity\AppelFond;
use App\Entity\Budget;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppelFondType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'dateAppel',
                null,
                [
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'dateEcheance',
                null,
                [
                    'input'  => 'datetime_immutable',
                    'widget' => 'single_text',
                ]
            )
            ->add('libelle')
            ->add(
                'budget',
                EntityType::class,
                [
                    'class' => Budget::class,
                    'choice_label' => 'libelle',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => AppelFond::class,
            ]
        );
    }
}
