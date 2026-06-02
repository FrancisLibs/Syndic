<?php

namespace App\Form;

use App\Entity\Fournisseur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaiementFournisseurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'fournisseur',
                EntityType::class,
                [
                    'class' => Fournisseur::class,
                    'choice_label' => 'nom',
                    'label' => 'Fournisseur'
                ]
            )
            ->add(
                'montant',
                MoneyType::class,
                [
                    'mapped' => false,
                    'error_bubbling' => false,
                    'label' => 'Montant versé',
                    'currency' => 'EUR',
                    'constraints' => [
                        new Assert\NotBlank(
                            [
                                'message' => 'Le montant ne peut pas être vide.'
                            ]
                        ),
                        new Assert\Positive(
                            [
                                'message' => 'Le montant doit être positif.'
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'date',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'label' => 'Date du paiement'
                ]
            )
            ->add(
                'libelle',
                TextType::class,
                [
                    'data' => 'Paiement de charges',
                    'label' => 'Libellé'
                ],
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
