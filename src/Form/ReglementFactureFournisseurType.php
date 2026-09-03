<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

final class ReglementFactureFournisseurType extends AbstractType
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
                    'label' => 'Date de règlement',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'data' => new \DateTimeImmutable(),
                ]
            )
            ->add('montant', MoneyType::class, [
                'label' => 'Montant réellement payé',
                'currency' => 'EUR',
                'scale' => 2,
                'required' => true,
            ]);;
    }
}
