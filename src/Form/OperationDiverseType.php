<?php

namespace App\Form;

use App\Entity\Compte;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperationDiverseType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Date',
            ])

            ->add('piece', TextType::class, [
                'label' => 'Pièce',
                'required' => false,
            ])

            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
            ])

            ->add('compteDebit', EntityType::class, [
                'class' => Compte::class,
                'choice_label' => static function (
                    Compte $compte
                ): string {
                    return sprintf(
                        '%s - %s',
                        $compte->getNumero(),
                        $compte->getLibelle()
                    );
                },
                'label' => 'Compte débité',
                'placeholder' => 'Sélectionner un compte',
            ])

            ->add('compteCredit', EntityType::class, [
                'class' => Compte::class,
                'choice_label' => static function (
                    Compte $compte
                ): string {
                    return sprintf(
                        '%s - %s',
                        $compte->getNumero(),
                        $compte->getLibelle()
                    );
                },
                'label' => 'Compte crédité',
                'placeholder' => 'Sélectionner un compte',
            ])

            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'scale' => 2,
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
