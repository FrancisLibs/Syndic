<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TraitementFnpType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add(
                'dateFacture',
                DateType::class,
                [
                    'label' => 'Date de la facture',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ]
            )
            ->add(
                'numero',
                TextType::class,
                [
                    'label' => 'Numéro de facture',
                    'required' => true,
                ]
            )
            ->add(
                'imputerAcompte',
                CheckboxType::class,
                [
                    'label' => 'Imputer un acompte fournisseur déjà versé',
                    'required' => false,
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
