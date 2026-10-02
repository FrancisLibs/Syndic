<?php

namespace App\Form;

use App\Entity\VoteResolution;
use App\Enum\VoteChoix;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoteResolutionType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('choix', EnumType::class, [
                'class' => VoteChoix::class,
                'label' => false,
                'choice_label' => fn(VoteChoix $choix) => $choix->label(),
                'placeholder' => '— Choisir —',
                'required' => false,
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => VoteResolution::class,
        ]);
    }
}
