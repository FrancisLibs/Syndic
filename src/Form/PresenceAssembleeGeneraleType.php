<?php

namespace App\Form;

use App\Entity\PresenceAssembleeGenerale;
use App\Enum\PresenceStatut;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PresenceAssembleeGeneraleType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('statut', EnumType::class, [
                'class' => PresenceStatut::class,
                'label' => false,
                'choice_label' => fn(PresenceStatut $statut) => $statut->label(),
            ])
            ->add('dateReceptionVoteCorrespondance', DateType::class, [
                'label' => false,
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('mandataire', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom du mandataire',
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => PresenceAssembleeGenerale::class,
        ]);
    }
}
