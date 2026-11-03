<?php

namespace App\Form;

use App\Entity\Copropriete;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoproprieteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('adresse')
            ->add('codePostal')
            ->add('localite')
            ->add('tantiemesBase')

            ->add('iban', TextType::class, [
                'label' => 'IBAN',
                'required' => false,
                'attr' => [
                    'maxlength' => 34,
                    'placeholder' => 'FR76...',
                ],
            ])

            ->add('bic', TextType::class, [
                'label' => 'BIC',
                'required' => false,
                'attr' => [
                    'maxlength' => 11,
                    'placeholder' => 'Code BIC',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Copropriete::class,
        ]);
    }
}
