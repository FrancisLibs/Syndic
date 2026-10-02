<?php

namespace App\Form;

use App\Entity\Coproprietaire;
use App\Entity\MandatCopropriete;
use App\Enum\FonctionCopropriete;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MandatCoproprieteType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('coproprietaire', EntityType::class, [
                'class' => Coproprietaire::class,
                'choice_label' => function (
                    Coproprietaire $coproprietaire
                ): string {
                    return sprintf(
                        '%s %s',
                        $coproprietaire->getPrenom(),
                        $coproprietaire->getNom()
                    );
                },
                'label' => 'Copropriétaire',
            ])

            ->add('fonction', ChoiceType::class, [
                'choices' => FonctionCopropriete::cases(),
                'choice_label' => static fn(
                    FonctionCopropriete $fonction
                ): string => $fonction->label(),
                'choice_value' => static fn(
                    ?FonctionCopropriete $fonction
                ): ?string => $fonction?->value,
                'label' => 'Fonction',
            ])

            ->add('dateDebut', null, [
                'widget' => 'single_text',
                'label' => 'Date de début',
            ])

            ->add('dateFin', null, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de fin',
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => MandatCopropriete::class,
        ]);
    }
}
