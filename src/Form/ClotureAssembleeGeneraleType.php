<?php

namespace App\Form;

use App\Entity\AssembleeGenerale;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClotureAssembleeGeneraleType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add('heureCloture', TimeType::class, [
            'label' => 'Heure de clôture',
            'widget' => 'single_text',
            'required' => true,
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
