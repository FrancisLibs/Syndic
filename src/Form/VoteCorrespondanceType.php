<?php

namespace App\Form;

use App\Enum\VoteChoix;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoteCorrespondanceType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add('choix', EnumType::class, [
            'class' => VoteChoix::class,
            'choice_label' => fn(VoteChoix $choix) => $choix->label(),
            'label' => false,
            'placeholder' => '— Choisir —',
            'required' => false,
        ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
