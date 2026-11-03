<?php

namespace App\Form\Travaux;

use App\Entity\Travaux\Chantier;
use App\Enum\ModeRepartition;
use App\Enum\FinancementChantier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChantierType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('libelle')
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('montantVote', MoneyType::class, [
                'label' => 'Montant prévisionnel des travaux',
                'currency' => 'EUR',
            ])
            ->add('modeRepartition', ChoiceType::class, [
                'label' => 'Mode de répartition',
                'choices' => ModeRepartition::cases(),
                'choice_label' => fn(ModeRepartition $mode) => $mode->label(),
                'choice_value' => fn(?ModeRepartition $mode) => $mode?->value,
            ])
            ->add('financement', ChoiceType::class, [
                'label' => 'Mode de financement',
                'choices' => [
                    'Appels de fonds spécifiques' =>
                    FinancementChantier::APPELS_SPECIFIQUES,
                    'Charges courantes' =>
                    FinancementChantier::CHARGES_COURANTES,
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Chantier::class,
        ]);
    }
}
