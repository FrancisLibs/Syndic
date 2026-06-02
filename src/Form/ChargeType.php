<?php

namespace App\Form;

use App\Entity\Compte;
use App\Entity\Copropriete;
use App\Entity\Fournisseur;
use App\Entity\Operation;
use App\Entity\TypeCharge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChargeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle')
            ->add(
                'date',
                null,
                [
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'montant',
                MoneyType::class,
                [
                    'mapped' => false,
                    'error_bubbling' => false,
                    'label' => 'Montant versé',
                    'currency' => false,
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
                'typeCharge',
                EntityType::class,
                [
                    'class' => TypeCharge::class,
                    'choice_label' => 'nom',
                ]
            )
            ->add(
                'fournisseur',
                EntityType::class,
                [
                    'class' => Fournisseur::class,
                    'choice_label' => 'nom',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Operation::class,
            ]
        );
    }
}
