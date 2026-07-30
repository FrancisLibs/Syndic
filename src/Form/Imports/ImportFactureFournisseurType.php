<?php

namespace App\Form\Imports;

use App\Entity\Coproprietaire;
use App\Entity\Fournisseur;
use App\Entity\Imports\ImportFactureFournisseur;
use App\Entity\TypeCharge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ImportFactureFournisseurType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('dateFacture', DateType::class, [
                'label' => 'Date de facture',
                'widget' => 'single_text',
            ])

            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => 'nom',
                'label' => 'Fournisseur',
                'placeholder' => 'Sélectionner un fournisseur',
            ])

            ->add('typeCharge', EntityType::class, [
                'class' => TypeCharge::class,
                'choice_label' => 'nom',
                'label' => 'Type de charge',
                'placeholder' => 'Sélectionner un type de charge',
            ])

            ->add('coproprietaireAvanceur', EntityType::class, [
                'class' => Coproprietaire::class,
                'choice_label' => static function (
                    Coproprietaire $coproprietaire
                ): string {
                    return (string) $coproprietaire;
                },
                'label' => 'Copropriétaire avanceur',
                'placeholder' => 'Aucun',
                'required' => false,
            ])

            ->add('numero', TextType::class, [
                'label' => 'Numéro de facture',
                'required' => false,
            ])

            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
            ])

            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'scale' => 2,
            ])

            ->add('reglee', CheckboxType::class, [
                'label' => 'Facture déjà réglée',
                'required' => false,
            ])

            ->add('dateReglement', DateType::class, [
                'label' => 'Date de règlement',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => ImportFactureFournisseur::class,
        ]);
    }
}
