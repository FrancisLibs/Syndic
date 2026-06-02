<?php

namespace App\Form;

use App\Entity\Compte;
use App\Entity\TypeCharge;
use App\Enum\ModeRepartition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeChargeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add(
                'modeRepartition',
                EnumType::class,
                [
                    'class' => ModeRepartition::class,
                    'choice_label'
                    => fn(ModeRepartition $choice) => $choice->label(),
                    'placeholder' => 'Choisir un mode',
                    'empty_data' => ModeRepartition::TANTIEMES,
                ]
            )

            ->add(
                'compte',
                EntityType::class,
                [
                    'class' => Compte::class,
                    'choice_label' => 'libelle',
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TypeCharge::class,
            ]
        );
    }
}
