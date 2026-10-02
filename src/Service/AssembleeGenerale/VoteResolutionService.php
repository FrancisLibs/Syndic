<?php

namespace App\Service\AssembleeGenerale;

use App\Entity\Resolution;
use App\Entity\VoteResolution;
use App\Entity\PresenceAssembleeGenerale;
use App\Enum\PresenceStatut;
use App\Enum\VoteChoix;
use App\Enum\MajoriteResolution;
use Doctrine\ORM\EntityManagerInterface;

class VoteResolutionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function initialiser(Resolution $resolution): void
    {
        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($assembleeGenerale === null) {
            throw new \LogicException(
                'Cette résolution n’est associée à aucune assemblée générale.'
            );
        }

        if ($assembleeGenerale->getPresences()->isEmpty()) {
            throw new \LogicException(
                'La feuille de présence doit être renseignée avant le vote.'
            );
        }

        foreach ($assembleeGenerale->getPresences() as $presence) {

            // Le vote en séance concerne uniquement
            // les présents et les représentés.
            if (
                $presence->getStatut() !== PresenceStatut::PRESENT
                && $presence->getStatut() !== PresenceStatut::REPRESENTE
            ) {
                continue;
            }

            // Vérifie si un vote existe déjà pour ce
            // copropriétaire, cette résolution et ce tour.
            $voteExistant = null;

            foreach ($resolution->getVotes() as $vote) {
                if (
                    $vote->getTour() === 1
                    && $vote->getCoproprietaire()
                    === $presence->getCoproprietaire()
                ) {
                    $voteExistant = $vote;
                    break;
                }
            }

            if ($voteExistant !== null) {
                continue;
            }

            $vote = new VoteResolution();

            $vote
                ->setResolution($resolution)
                ->setCoproprietaire($presence->getCoproprietaire())
                ->setNombreVoix($presence->getNombreVoix())
                ->setTour(1);

            $resolution->addVote($vote);

            $this->entityManager->persist($vote);
        }

        $this->entityManager->flush();
    }

    public function initialiserSecondTour(
        Resolution $resolution
    ): void {
        if (!$resolution->isSecondVoteNecessaire()) {
            throw new \LogicException(
                'Cette résolution ne nécessite pas de second vote.'
            );
        }

        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($assembleeGenerale === null) {
            throw new \LogicException(
                'Cette résolution n’est associée à aucune assemblée générale.'
            );
        }

        foreach ($assembleeGenerale->getPresences() as $presence) {

            if ($presence->getStatut() === PresenceStatut::ABSENT) {
                continue;
            }

            // Vérifie si le vote du second tour existe déjà.
            $voteSecondTourExistant = null;

            foreach ($resolution->getVotes() as $vote) {
                if (
                    $vote->getTour() === 2
                    && $vote->getCoproprietaire()
                    === $presence->getCoproprietaire()
                ) {
                    $voteSecondTourExistant = $vote;
                    break;
                }
            }

            if ($voteSecondTourExistant !== null) {
                continue;
            }

            $vote = new VoteResolution();

            $vote
                ->setResolution($resolution)
                ->setCoproprietaire(
                    $presence->getCoproprietaire()
                )
                ->setNombreVoix(
                    $presence->getNombreVoix()
                )
                ->setTour(2);

            /*
         * Pour un vote par correspondance,
         * on reprend le choix exprimé au premier tour.
         */
            if (
                $presence->getStatut()
                === PresenceStatut::VOTE_CORRESPONDANCE
            ) {
                $choixPremierTour = null;

                foreach ($resolution->getVotes() as $votePremierTour) {
                    if (
                        $votePremierTour->getTour() === 1
                        && $votePremierTour->getCoproprietaire()
                        === $presence->getCoproprietaire()
                    ) {
                        $choixPremierTour =
                            $votePremierTour->getChoix();

                        break;
                    }
                }

                $vote->setChoix($choixPremierTour);
            }

            /*
         * Présents et représentés :
         * choix laissé à NULL pour permettre
         * un nouveau vote en séance.
         */

            $resolution->addVote($vote);

            $this->entityManager->persist($vote);
        }

        $this->entityManager->flush();
    }

    public function recalculerTotaux(
        Resolution $resolution,
        int $tour = 1
    ): void {
        $pour = 0;
        $contre = 0;
        $abstention = 0;

        $assembleeGenerale = $resolution->getAssembleeGenerale();

        if ($assembleeGenerale === null) {
            throw new \LogicException(
                'Cette résolution n’est associée à aucune assemblée générale.'
            );
        }

        foreach ($resolution->getVotes() as $vote) {

            if ($vote->getTour() !== $tour) {
                continue;
            }

            /*
         * Si la résolution a été amendée en séance,
         * un copropriétaire ayant voté POUR par
         * correspondance est assimilé à un défaillant.
         *
         * Son vote reste enregistré, mais ses voix
         * ne sont pas comptabilisées dans les POUR.
         */
            if (
                $resolution->isAmendeeEnSeance()
                && $vote->getChoix() === VoteChoix::POUR
            ) {
                foreach ($assembleeGenerale->getPresences() as $presence) {

                    if (
                        $presence->getCoproprietaire()
                        === $vote->getCoproprietaire()
                        && $presence->getStatut()
                        === PresenceStatut::VOTE_CORRESPONDANCE
                    ) {
                        continue 2;
                    }
                }
            }

            switch ($vote->getChoix()) {

                case VoteChoix::POUR:
                    $pour += $vote->getNombreVoix();
                    break;

                case VoteChoix::CONTRE:
                    $contre += $vote->getNombreVoix();
                    break;

                case VoteChoix::ABSTENTION:
                    $abstention += $vote->getNombreVoix();
                    break;
            }
        }

        $resolution
            ->setTantiemesPour($pour)
            ->setTantiemesContre($contre)
            ->setTantiemesAbstention($abstention);
    }

    public function calculerResultat(Resolution $resolution): void
    {
        $pour = $resolution->getTantiemesPour() ?? 0;
        $contre = $resolution->getTantiemesContre() ?? 0;

        // On repart toujours d'un état propre.
        $resolution->setSecondVoteNecessaire(false);

        switch ($resolution->getMajorite()) {

            case MajoriteResolution::ARTICLE_24:
                /**
                 * Majorité des voix exprimées.
                 * Les abstentions ne sont pas prises en compte.
                 */
                $resolution->setAdoptee($pour > $contre);
                break;

            case MajoriteResolution::ARTICLE_25:
                /**
                 * Notre copropriété possède 10 000 voix.
                 *
                 * Article 25 :
                 * majorité des voix de tous les copropriétaires.
                 */
                if ($pour > 5000) {
                    $resolution
                        ->setAdoptee(true)
                        ->setSecondVoteNecessaire(false);

                    break;
                }

                /**
                 * Article 25-1 :
                 * au moins un tiers des 10 000 voix.
                 */
                if ($pour >= 3334) {
                    $resolution
                        ->setAdoptee(null)
                        ->setSecondVoteNecessaire(true);

                    break;
                }

                $resolution
                    ->setAdoptee(false)
                    ->setSecondVoteNecessaire(false);

                break;

            default:
                $resolution
                    ->setAdoptee(null)
                    ->setSecondVoteNecessaire(false);
        }
    }

    public function calculerResultatSecondTour(
        Resolution $resolution
    ): void {
        $pour = 0;
        $contre = 0;
        $abstention = 0;

        foreach ($resolution->getVotes() as $vote) {

            if ($vote->getTour() !== 2) {
                continue;
            }

            switch ($vote->getChoix()) {

                case VoteChoix::POUR:
                    $pour += $vote->getNombreVoix();
                    break;

                case VoteChoix::CONTRE:
                    $contre += $vote->getNombreVoix();
                    break;

                case VoteChoix::ABSTENTION:
                    $abstention += $vote->getNombreVoix();
                    break;
            }
        }

        /*
     * On conserve les résultats du premier tour
     * dans tantiemesPour / Contre / Abstention.
     *
     * Les résultats du second tour sont stockés
     * séparément.
     */
        $resolution
            ->setTantiemesPourSecondTour($pour)
            ->setTantiemesContreSecondTour($contre)
            ->setTantiemesAbstentionSecondTour($abstention)
            ->setAdoptee($pour > $contre)
            ->setSecondVoteNecessaire(false);
    }

    public function enregistrerVotesCorrespondance(
        Resolution $resolution,
        PresenceAssembleeGenerale $presence,
        ?VoteChoix $choix
    ): void {
        if (
            $presence->getStatut()
            !== PresenceStatut::VOTE_CORRESPONDANCE
        ) {
            throw new \LogicException(
                'Ce copropriétaire ne vote pas par correspondance.'
            );
        }

        $voteExistant = null;

        foreach ($resolution->getVotes() as $vote) {
            if (
                $vote->getTour() === 1
                && $vote->getCoproprietaire()
                === $presence->getCoproprietaire()
            ) {
                $voteExistant = $vote;
                break;
            }
        }

        if ($choix === null) {
            if ($voteExistant !== null) {
                $resolution->removeVote($voteExistant);
            }

            return;
        }

        if ($voteExistant === null) {
            $voteExistant = new VoteResolution();

            $voteExistant
                ->setResolution($resolution)
                ->setCoproprietaire(
                    $presence->getCoproprietaire()
                )
                ->setNombreVoix($presence->getNombreVoix())
                ->setTour(1);

            $resolution->addVote($voteExistant);

            $this->entityManager->persist($voteExistant);
        }

        $voteExistant->setChoix($choix);
    }
}
