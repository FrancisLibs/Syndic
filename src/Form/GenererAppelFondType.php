<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class GenererAppelFondType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('dateAppel', DateType::class, [
                'label' => 'Date de l\'appel',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dateReglement', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }
}
