<?php

namespace App\Form;

use App\Entity\VoteResolution;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SecondVoteResolutionType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add('votes', CollectionType::class, [
            'entry_type' => VoteResolutionType::class,
            'entry_options' => [
                'label' => false,
            ],
            'label' => false,
            'allow_add' => false,
            'allow_delete' => false,
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
