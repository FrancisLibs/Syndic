<?php

namespace App\Form;

use App\Entity\Compte;
use App\Entity\Copropriete;
use App\Entity\Operation;
use App\Enum\OperationType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OperationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'copropriete',
                EntityType::class,
                [
                    'class' => Copropriete::class,
                    'choice_label' => 'nom',
                    'mapped' => false,
                    'label' => 'Copropriété'
                ]
            )
            ->add(
                'type',
                EnumType::class,
                [
                    'class' => OperationType::class,
                ]
            )
            ->add(
                'date',
                DateType::class,
                [
                    'input' => 'datetime_immutable',
                    'widget' => 'single_text',
                ]
            )
            ->add('libelle')
            ->add('piece')
            ->add(
                'compte',
                EntityType::class,
                [
                    'class' => Compte::class,
                    'choice_label' => 'libelle',
                    'mapped' => false,
                    'label' => 'Compte de charge'
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
