<?php

namespace App\Form;

use App\Entity\FactureFournisseur;
use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use App\Entity\Coproprietaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

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
                'dateReglement',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'label' => 'date de règlement',
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
                    'required' => true,
                    'row_attr' => [
                        'id' => 'fournisseur-row',
                    ],
                ]
            )

            ->add(
                'coproprietaireAvanceur',
                EntityType::class,
                [
                    'class' => Coproprietaire::class,
                    'choice_label' => 'nom',
                    'label' => 'Copropriétaire à rembourser',
                    'required' => false,
                    'placeholder' => 'Aucun — règlement au fournisseur',
                    'help' => 'À renseigner uniquement si un copropriétaire a avancé le paiement.',
                ]
            )

            ->add(
                'typeCharge',
                EntityType::class,
                [
                    'class' => TypeCharge::class,
                    'choice_label' => 'nom',
                    'label' => 'Type de charge',
                    'choice_attr' => function (TypeCharge $typeCharge) {
                        return [
                            'data-est-eau' => $typeCharge->isEau()
                                ? '1'
                                : '0',
                        ];
                    },
                ]
            )

            ->add(
                'volumeEau',
                IntegerType::class,
                [
                    'label' => 'Volume facturé (m³)',
                    'required' => false,

                    'row_attr' => [
                        'id' => 'volume-eau-row',
                    ],

                    'attr' => [
                        'min' => 0,
                    ],
                ]
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
