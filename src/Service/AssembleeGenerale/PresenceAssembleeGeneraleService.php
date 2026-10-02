<?php

namespace App\Service\AssembleeGenerale;

use App\Entity\AssembleeGenerale;
use App\Entity\PresenceAssembleeGenerale;
use App\Enum\PresenceStatut;
use App\Repository\LotCoproprietaireRepository;
use Doctrine\ORM\EntityManagerInterface;

class PresenceAssembleeGeneraleService
{
    public function __construct(
        private readonly LotCoproprietaireRepository $lotCoproprietaireRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function initialiser(
        AssembleeGenerale $assembleeGenerale
    ): void {
        $date = $assembleeGenerale->getDate();

        if ($date === null) {
            throw new \LogicException(
                'La date de l’assemblée générale doit être définie.'
            );
        }

        // Une feuille existe déjà : on ne la recrée pas.
        if (!$assembleeGenerale->getPresences()->isEmpty()) {
            return;
        }

        $voixParCoproprietaire =
            $this->lotCoproprietaireRepository
            ->calculerVoixParCoproprietaire($date);

        foreach ($voixParCoproprietaire as $ligne) {
            $presence = new PresenceAssembleeGenerale();

            $presence
                ->setAssembleeGenerale($assembleeGenerale)
                ->setCoproprietaire($ligne['coproprietaire'])
                ->setNombreVoix($ligne['nombreVoix'])
                ->setStatut(PresenceStatut::ABSENT);

            $assembleeGenerale->addPresence($presence);

            $this->entityManager->persist($presence);
        }

        $this->entityManager->flush();
    }

    public function mettreAJourStatistiques(
        AssembleeGenerale $assembleeGenerale
    ): void {
        $nbPresents = 0;
        $nbRepresentes = 0;
        $nbPouvoirs = 0;
        $tantiemes = 0;

        foreach ($assembleeGenerale->getPresences() as $presence) {

            if ($presence->getStatut() === PresenceStatut::PRESENT) {
                $nbPresents++;
                $tantiemes += $presence->getNombreVoix();
            }

            if ($presence->getStatut() === PresenceStatut::REPRESENTE) {
                $nbRepresentes++;
                $nbPouvoirs++;
                $tantiemes += $presence->getNombreVoix();
            }
        }

        $assembleeGenerale
            ->setNombrePresents($nbPresents)
            ->setNombreRepresentes($nbRepresentes)
            ->setNombrePouvoirs($nbPouvoirs)
            ->setTantiemesPresentsRepresentes($tantiemes);

        $this->entityManager->flush();
    }

    public function recalculerTotaux(
        AssembleeGenerale $assembleeGenerale
    ): void {
        $tantiemesPresentsRepresentes = 0;
        $nombrePresents = 0;
        $nombreRepresentes = 0;
        $nombrePouvoirs = 0;

        foreach ($assembleeGenerale->getPresences() as $presence) {

            switch ($presence->getStatut()) {

                case PresenceStatut::PRESENT:
                    $nombrePresents++;
                    $tantiemesPresentsRepresentes +=
                        $presence->getNombreVoix();
                    break;

                case PresenceStatut::REPRESENTE:
                    $nombreRepresentes++;
                    $nombrePouvoirs++;
                    $tantiemesPresentsRepresentes +=
                        $presence->getNombreVoix();
                    break;

                case PresenceStatut::VOTE_CORRESPONDANCE:
                    // Comptabilisé lors des votes des résolutions,
                    // mais pas dans les tantièmes "présents/représentés".
                    break;

                case PresenceStatut::ABSENT:
                    break;
            }
        }

        $assembleeGenerale
            ->setNombrePresents($nombrePresents)
            ->setNombreRepresentes($nombreRepresentes)
            ->setNombrePouvoirs($nombrePouvoirs)
            ->setTantiemesPresentsRepresentes(
                $tantiemesPresentsRepresentes
            );
    }
}
