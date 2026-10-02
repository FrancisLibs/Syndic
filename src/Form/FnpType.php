<?php

namespace App\Form;

use App\Entity\Fournisseur;
use App\Entity\TypeCharge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FnpType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $options['date_defaut'],
                'mapped' => false,
            ])
            ->add('typeCharge', EntityType::class, [
                'class' => TypeCharge::class,
                'choice_label' => 'nom',
                'label' => 'Type de charge',
                'mapped' => false,
            ])
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => 'nom',
                'label' => 'Fournisseur',
                'required' => false,
                'mapped' => false,
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('piece', TextType::class, [
                'label' => 'Pièce / référence',
                'required' => false,
                'mapped' => false,
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'date_defaut' => null,
        ]);

        $resolver->setAllowedTypes(
            'date_defaut',
            ['null', \DateTimeInterface::class]
        );
    }
}
