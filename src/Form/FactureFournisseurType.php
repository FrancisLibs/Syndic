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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
