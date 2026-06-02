<?php

namespace App\Form;

use App\Entity\LigneBudget;
use App\Entity\TypeCharge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LigneBudgetType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        $builder

            ->add(
                'typeCharge',
                EntityType::class,
                [
                    'class' => TypeCharge::class,

                    'choice_label' => 'nom',

                    'label' => 'Type charge',
                ]
            )

            ->add(
                'montant',
                MoneyType::class,
                [
                    'currency' => false,

                    'label' => 'Montant',
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {

        $resolver->setDefaults([
            'data_class' => LigneBudget::class,
        ]);
    }
}
