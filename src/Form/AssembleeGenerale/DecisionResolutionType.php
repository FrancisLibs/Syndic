<?php

namespace App\Form\AssembleeGenerale;

use App\Entity\Resolution;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType; 
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DecisionResolutionType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('decision', TextareaType::class, [
                'label' => 'Décision de l’Assemblée Générale',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                ],
                'help' => 'Précisez ici la décision arrêtée par l’Assemblée Générale avant le vote.',
            ])
            ->add('amendeeEnSeance', CheckboxType::class, [
                'label' => 'La résolution a été amendée en séance',
                'required' => false,
                'help' => 'À cocher si le texte soumis au vote a été modifié au cours de l’Assemblée Générale.',
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Resolution::class,
        ]);
    }
}
