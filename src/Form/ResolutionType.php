<?php

namespace App\Form;


use App\Entity\AssembleeGenerale;
use App\Entity\Resolution;
use App\Entity\Budget;
use App\Entity\Travaux\Chantier;
use App\Entity\Travaux\VoteComplementaireChantier;
use App\Repository\Travaux\ChantierRepository;
use App\Repository\BudgetRepository;
use App\Repository\ExerciceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Enum\MajoriteResolution;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;

class ResolutionType extends AbstractType
{

    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private BudgetRepository $budgetRepository,
        private ChantierRepository $chantierRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        /** @var AssembleeGenerale $assembleeGenerale */
        $assembleeGenerale = $options['assemblee_generale'];

        $exerciceSuivant = $this->exerciceRepository->findSuivant(
            $assembleeGenerale->getExercice()
        );

        $budget = $exerciceSuivant
            ? $this->budgetRepository->findPourExercice($exerciceSuivant)
            : null;

        $copropriete = $assembleeGenerale
            ->getExercice()
            ->getCopropriete();

        $chantiers = $this->chantierRepository->findBy(
            ['copropriete' => $copropriete],
            ['libelle' => 'ASC']
        );

        $votesComplementaires = [];

        foreach ($chantiers as $chantier) {
            foreach ($chantier->getVotesComplementaires() as $vote) {
                $votesComplementaires[] = $vote;
            }
        }

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
            ])

            ->add('budget', EntityType::class, [
                'class' => Budget::class,
                'choices' => $budget ? [$budget] : [],
                'choice_label' => function (Budget $budget) {
                    return sprintf(
                        '%s - %s',
                        $budget->getExercice()->getNom(),
                        $budget->getLibelle() ?? 'Budget prévisionnel'
                    );
                },
                'label' => 'Budget prévisionnel concerné',
                'placeholder' => 'Aucun budget',
                'required' => false,
            ])

            ->add('chantier', EntityType::class, [
                'class' => Chantier::class,
                'choices' => $chantiers,
                'choice_label' => 'libelle',
                'label' => 'Chantier concerné',
                'placeholder' => 'Aucun chantier',
                'required' => false,
            ])

            ->add('voteComplementaireChantier', EntityType::class, [
                'class' => VoteComplementaireChantier::class,
                'choices' => $votesComplementaires,
                'choice_label' => function (VoteComplementaireChantier $vote) {
                    return sprintf(
                        '%s - %s €',
                        $vote->getChantier()?->getLibelle() ?? 'Chantier inconnu',
                        $vote->getMontant()
                    );
                },
                'label' => 'Vote complémentaire concerné',
                'placeholder' => 'Aucun vote complémentaire',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resolution::class,
        ]);

        $resolver->setRequired('assemblee_generale');
        $resolver->setAllowedTypes(
            'assemblee_generale',
            AssembleeGenerale::class
        );
    }
}
