<?php

namespace App\Form;

use App\Entity\AssembleeGenerale;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssembleeGeneraleBureauType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('presidentSeance', TextType::class, [
                'label' => 'Président de séance',
                'required' => false,
            ])
            ->add('scrutateur', TextType::class, [
                'label' => 'Scrutateur',
                'required' => false,
            ])
            ->add('secretaireSeance', TextType::class, [
                'label' => 'Secrétaire de séance',
                'required' => false,
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => AssembleeGenerale::class,
        ]);
    }
}
