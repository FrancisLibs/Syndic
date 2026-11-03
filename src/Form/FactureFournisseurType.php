<?php

namespace App\Form;

use App\Entity\FactureFournisseur;
use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use App\Entity\Coproprietaire;
use App\Entity\Travaux\Chantier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'type',
                ChoiceType::class,
                [
                    'label' => 'Type de document',
                    'choices' => [
                        'Facture' => \App\Enum\FactureFournisseurType::FACTURE,
                        'Avoir' => \App\Enum\FactureFournisseurType::AVOIR,
                    ],
                    'expanded' => true,
                    'multiple' => false,
                ]
            )

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
                    'required' => false,
                    'placeholder' => 'Sélectionner un type de charge si nécessaire',
                    'help' => 'Obligatoire pour les charges courantes, y compris les petits travaux.',
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
                'chantier',
                EntityType::class,
                [
                    'class' => Chantier::class,
                    'choice_label' => 'libelle',
                    'label' => 'Chantier',
                    'required' => false,
                    'placeholder' => 'Aucun chantier',
                    'help' => 'Facultatif pour une charge ordinaire. Permet de suivre les dépenses d’un chantier.',
                ]
            )

            ->add(
                'fnp',
                CheckboxType::class,
                [
                    'label' => 'Facture non parvenue',
                    'required' => false,
                    'help' => 'À cocher si la charge concerne l’exercice mais que la facture n’a pas encore été reçue.',
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
            )
            ->add(
                'confirmerCorrespondance',
                CheckboxType::class,
                [
                    'label' => 'Je confirme qu’il s’agit d’une nouvelle facture',
                    'required' => false,
                    'mapped' => false,
                    'row_attr' => [
                        'class' => $options['confirmation_correspondance']
                            ? ''
                            : 'd-none',
                        'id' => 'confirmation-correspondance-row',
                    ],
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {

        $resolver->setDefaults([
            'data_class' => FactureFournisseur::class,
            'confirmation_correspondance' => false,
        ]);

        $resolver->setAllowedTypes(
            'confirmation_correspondance',
            'bool'
        );
    }
}
