<?php

namespace App\Form;

use App\Entity\FactureFournisseur;
use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureFournisseurType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        $builder

            ->add(
                'numero',
                TextType::class,
                [
                    'label' => 'Numéro facture',
                ]
            )

            ->add(
                'libelle',
                TextType::class,
                [
                    'label' => 'Libellé',
                ]
            )

            ->add(
                'dateFacture',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'label' => 'Date facture',
                ]
            )

            ->add(
                'dateEcheance',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'label' => 'Échéance',
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
                'fournisseur',
                EntityType::class,
                [
                    'class' => Fournisseur::class,
                    'choice_label' => 'nom',
                    'label' => 'Fournisseur',
                ]
            )

            ->add(
                'typeCharge',
                EntityType::class,
                [
                    'class' => TypeCharge::class,
                    'choice_label' => 'nom',
                    'label' => 'Type de charge',]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {

        $resolver->setDefaults([
            'data_class' => FactureFournisseur::class,
        ]);
    }
}
