<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EcritureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'debit',
                null,
                [
                    'required' => false,
                    'constraints' => [
                        new Assert\NotBlank(allowNull: true),
                        new Assert\GreaterThanOrEqual(0),
                    ],
                ]
            )
            ->add(
                'credit',
                null,
                [
                    'required' => false,
                    'constraints' => [
                        new Assert\NotBlank(allowNull: true),
                        new Assert\GreaterThanOrEqual(0),
                    ],
                ]
            )
            ->add(
                'debit',
                MoneyType::class,
                [
                    'currency' => 'EUR',
                ]
            )
            ->add(
                'credit',
                MoneyType::class,
                [
                    'currency' => 'EUR',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
