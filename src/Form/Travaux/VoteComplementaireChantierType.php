<?php

namespace App\Form\Travaux;

use App\Entity\Travaux\VoteComplementaireChantier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoteComplementaireChantierType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant complémentaire',
                'currency' => 'EUR',
            ])
            ->add('dateVote', DateType::class, [
                'label' => 'Date du vote',
                'widget' => 'single_text',
            ])
            ->add('numeroResolution', TextType::class, [
                'label' => 'Numéro de résolution',
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => VoteComplementaireChantier::class,
        ]);
    }
}
