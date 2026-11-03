<?php

namespace App\Service\Travaux;

use App\Entity\Resolution;
use App\Entity\Travaux\Chantier;
use App\Enum\AssembleeGeneraleStatut;
use App\Repository\AssembleeGeneraleRepository;
use App\Repository\ResolutionRepository;
use Doctrine\ORM\EntityManagerInterface;

class CreationResolutionChantierService
{
    public function __construct(
        private AssembleeGeneraleRepository $assembleeGeneraleRepository,
        private ResolutionRepository $resolutionRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function creerPourChantier(Chantier $chantier): bool
    {
        // Éviter la création de résolutions en double.
        $resolutionExistante = $this->resolutionRepository->findOneBy([
            'chantier' => $chantier,
        ]);

        if ($resolutionExistante !== null) {
            return false;
        }

        // Rechercher les AG en préparation de la copropriété.
        $assemblees = $this->assembleeGeneraleRepository->findBy([
            'statut' => AssembleeGeneraleStatut::PREPARATION,
        ]);

        $assemblees = array_values(array_filter(
            $assemblees,
            fn($assemblee) =>
            $assemblee->getExercice()?->getCopropriete()?->getId()
                === $chantier->getCopropriete()?->getId()
        ));

        // Ne pas sélectionner arbitrairement une AG.
        if (count($assemblees) !== 1) {
            return false;
        }

        $assembleeGenerale = $assemblees[0];

        // Rechercher le prochain numéro disponible.
        $numero = 0;

        foreach ($assembleeGenerale->getResolutions() as $resolution) {
            $numero = max(
                $numero,
                $resolution->getNumero() ?? 0
            );
        }

        $numero++;

        // Préparer le texte de la résolution.
        $montant = number_format(
            (float) $chantier->getMontantVote(),
            2,
            ',',
            ' '
        );

        $texte = sprintf(
            'L’assemblée générale est appelée à se prononcer sur '
                . 'la réalisation des travaux suivants : %s. '
                . 'Le montant prévisionnel est de %s €.',
            $chantier->getLibelle(),
            $montant
        );

        if ($chantier->getDescription()) {
            $texte .= "\n\n" . $chantier->getDescription();
        }

        // Créer la résolution.
        $resolution = new Resolution();

        $resolution
            ->setNumero($numero)
            ->setTitre($chantier->getLibelle())
            ->setTexte($texte)
            ->setChantier($chantier)
            ->setAdoptee(null);

        $assembleeGenerale->addResolution($resolution);

        $this->entityManager->persist($resolution);

        return true;
    }
}
