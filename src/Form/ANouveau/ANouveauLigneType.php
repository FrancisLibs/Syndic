<?php

namespace App\Form\ANouveau;

use App\Dto\ANouveau\ANouveauLigne;
use App\Entity\Compte;
use App\Entity\Coproprietaire;
use App\Repository\CompteRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ANouveauLigneType extends AbstractType
{
    public function __construct(
        private readonly CompteRepository $compteRepository,
    ) {}

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $comptes = array_filter(
            $this->compteRepository->findBy(
                [],
                ['numero' => 'ASC']
            ),
            static fn(Compte $compte): bool =>
            $compte->estCompteDeBilan()
        );

        $builder
            ->add(
                'compte',
                EntityType::class,
                [
                    'class' => Compte::class,
                    'choices' => $comptes,
                    'choice_label' => static function (
                        Compte $compte
                    ): string {
                        return sprintf(
                            '%s — %s',
                            $compte->getNumero(),
                            $compte->getLibelle()
                        );
                    },
                    'placeholder' => 'Choisir un compte',
                    'label' => false,
                ]
            )
            ->add(
                'coproprietaire',
                EntityType::class,
                [
                    'class' => Coproprietaire::class,
                    'choice_label' => static fn(
                        Coproprietaire $coproprietaire
                    ): string => (string) $coproprietaire,
                    'placeholder' => 'Aucun',
                    'required' => false,
                    'label' => false,
                ]
            )
            ->add(
                'solde',
                TextType::class,
                [
                    'label' => false,
                    'attr' => [
                        'inputmode' => 'decimal',
                        'placeholder' => '0,00',
                        'class' => 'text-end',
                    ],
                    'help' => 'Positif = débit ; négatif = crédit',
                ]
            );
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => ANouveauLigne::class,
        ]);
    }
}
