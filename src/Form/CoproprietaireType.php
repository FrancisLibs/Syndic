<?php

namespace App\Form;

use App\Entity\Coproprietaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoproprietaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('telephone')
            ->add('adresse', null, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('codePostal', null, [
                'label' => 'Code postal',
                'required' => false,
            ])
            ->add('ville', null, [
                'label' => 'Ville',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(
                [
                    'data_class' => Coproprietaire::class,
                ]
            );
    }
}
