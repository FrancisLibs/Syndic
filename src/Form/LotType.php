<?php

namespace App\Form;

use App\Entity\Coproprietaire;
use App\Entity\Lot;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference')
            ->add('designation')
            ->add('tantiemes')
            // ->add(
            //     'copropriete',
            //     EntityType::class,
            //     [
            //         'class' => Copropriete::class,
            //         'choice_label' => 'nom',
            //         'placeholder' => 'Choisir une copropriété',
            //     ]
            // )
            ->add(
                'coproprietaire',
                EntityType::class,
                [
                    'class' => Coproprietaire::class,
                    'choice_label' => 'nom',
                    'mapped' => false, // 🔥 obligatoire
                    'placeholder' => 'Choisir un copropriétaire',
                ]
            )
            ->add(
                'dateChangement',
                DateType::class,
                [
                    'input' => 'datetime_immutable',
                    'mapped' => false, // 🔥 important
                    'widget' => 'single_text',
                    'required' => false,
                    'label' => 'Date de changement de propriétaire'
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Lot::class,
            ]
        );
    }
}
