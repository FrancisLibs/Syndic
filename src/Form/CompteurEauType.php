<?php

namespace App\Form;

use App\Entity\CompteurEau;
use App\Entity\Lot;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteurEauType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add(
                'reference',
                TextType::class,
                [
                    'label' => 'Référence',
                ]
            )
            ->add(
                'indexInitial',
                IntegerType::class,
                [
                    'label' => 'Index initial',
                    'required' => false,
                    'attr' => [
                        'min' => 0,
                    ],
                ]
            );

        if ($options['avec_actif']) {
            $builder->add(
                'actif',
                CheckboxType::class,
                [
                    'label' => 'Actif',
                    'required' => false,
                ]
            );
        }

        if ($options['avec_lot']) {
            $builder->add(
                'lot',
                EntityType::class,
                [
                    'class' => Lot::class,
                    'choice_label' => 'designation',
                    'label' => 'Lot',
                ]
            );
        }
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => CompteurEau::class,
            'avec_lot' => true,
            'avec_actif' => true,
        ]);

        $resolver->setAllowedTypes(
            'avec_lot',
            'bool'
        );

        $resolver->setAllowedTypes(
            'avec_actif',
            'bool'
        );
    }
}
