<?php

namespace App\Form;

use App\Entity\Coproprietaire;
use App\Entity\Exercice;
use App\Entity\Paiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaiementType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        $builder

            ->add(
                'datePaiement',
                DateType::class,
                [
                    'input' => 'datetime_immutable',
                    'widget' => 'single_text',
                    'label' => 'Date paiement',
                ]
            )

            ->add(
                'coproprietaire',
                EntityType::class,
                [
                    'class' => Coproprietaire::class,
                    'choice_label' => 'nom',
                    'label' => 'Copropriétaire',
                ]
            )

            ->add(
                'exercice',
                EntityType::class,
                [
                    'class' => Exercice::class,
                    'choice_label' => 'nom',
                    'label' => 'Exercice',
                ]
            )

            ->add(
                'montant',
                MoneyType::class,
                [
                    'currency' => 'EUR',
                    'label' => 'Montant',
                ]
            )

            ->add(
                'reference',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'Référence',
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults(
            [
                'data_class' => Paiement::class,
            ]
        );
    }
}
