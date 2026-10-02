<?php

namespace App\Form;

use App\Entity\AssembleeGenerale;
use App\Entity\Resolution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Enum\MajoriteResolution;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResolutionType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('numero', IntegerType::class, [
                'label' => 'Numéro',
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre de la résolution',
            ])
            ->add('texte', TextareaType::class, [
                'label' => 'Texte de la résolution',
                'attr' => [
                    'rows' => 8,
                ],
            ])
            ->add('majorite', EnumType::class, [
                'class' => MajoriteResolution::class,
                'choice_label' => fn(MajoriteResolution $majorite) => $majorite->label(),
                'placeholder' => 'Aucune majorité / pas de vote',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resolution::class,
        ]);
    }
}
