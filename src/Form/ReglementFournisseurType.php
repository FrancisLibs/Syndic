<?php

namespace App\Form;

use App\Entity\Fournisseur;
use App\Entity\ReglementFournisseur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReglementFournisseurType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add(
                'dateReglement',
                DateType::class,
                [
                    'label' => 'Date du règlement',
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'fournisseur',
                EntityType::class,
                [
                    'class' => Fournisseur::class,
                    'choice_label' => 'nom',
                    'label' => 'Fournisseur',
                ]
            )
            ->add(
                'montant',
                MoneyType::class,
                [
                    'label' => 'Montant',
                    'currency' => 'EUR',
                    'scale' => 2,
                    'html5' => true,
                ]
            )
            ->add(
                'reference',
                TextType::class,
                [
                    'label' => 'Référence',
                    'required' => false,
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => ReglementFournisseur::class,
        ]);
    }
}
