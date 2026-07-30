<?php

namespace App\Service\Initialisation;

use App\Dto\Initialisation\ControleInitialisation;
use App\Dto\Initialisation\InitialisationComptable;
use App\Enum\JournalCode;
use App\Repository\JournalRepository;
use App\Repository\OperationRepository;

final class VerificationInitialisationService
{
    public function __construct(
        private readonly OperationRepository $operationRepository,
        private readonly JournalRepository $journalRepository,
    ) {}

    public function verifier(
        InitialisationComptable $initialisation,
    ): void {
        $this->verifierExercice($initialisation);
        $this->verifierDate($initialisation);
        $this->verifierJournalAN($initialisation);
        $this->verifierAbsenceANouveau($initialisation);
        $this->verifierLignes($initialisation);
        $this->verifierComptesCoproprietaires($initialisation);
        $this->verifierEquilibre($initialisation);
    }

    private function verifierExercice(
        InitialisationComptable $initialisation,
    ): void {
        $exercice = $initialisation->getExercice();

        if ($exercice->isCloture()) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Exercice disponible',
                    message: sprintf(
                        'L’exercice "%s" est clôturé.',
                        $exercice->getNom()
                    ),
                )
            );

            return;
        }

        if (!$exercice->isActif()) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Exercice actif',
                    message: sprintf(
                        'L’exercice "%s" n’est pas actif.',
                        $exercice->getNom()
                    ),
                )
            );

            return;
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Exercice actif',
                message: sprintf(
                    'L’exercice "%s" peut recevoir l’écriture d’ouverture.',
                    $exercice->getNom()
                ),
            )
        );
    }

    private function verifierDate(
        InitialisationComptable $initialisation,
    ): void {
        $exercice = $initialisation->getExercice();
        $date = $initialisation->getDate();

        if (
            $date < $exercice->getDateDebut()
            || $date > $exercice->getDateFin()
        ) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Date de l’écriture',
                    message: sprintf(
                        'La date du %s ne se situe pas dans l’exercice du %s au %s.',
                        $date->format('d/m/Y'),
                        $exercice->getDateDebut()->format('d/m/Y'),
                        $exercice->getDateFin()->format('d/m/Y')
                    ),
                )
            );

            return;
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Date de l’écriture',
                message: sprintf(
                    'La date du %s appartient bien à l’exercice.',
                    $date->format('d/m/Y')
                ),
            )
        );
    }

    private function verifierJournalAN(
        InitialisationComptable $initialisation,
    ): void {
        $journal = $this->journalRepository->findOneBy([
            'code' => JournalCode::AN->value,
        ]);

        if (!$journal) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Journal des à-nouveaux',
                    message: 'Le journal AN est introuvable.',
                )
            );

            return;
        }

        if (!$journal->isActif()) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Journal des à-nouveaux',
                    message: 'Le journal AN existe, mais il est inactif.',
                )
            );

            return;
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Journal des à-nouveaux',
                message: 'Le journal AN est disponible et actif.',
            )
        );
    }

    private function verifierAbsenceANouveau(
        InitialisationComptable $initialisation,
    ): void {
        $existe = $this->operationRepository
            ->existeANouveauPourExercice(
                $initialisation->getExercice()
            );

        if ($existe) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Absence d’écriture d’ouverture',
                    message: sprintf(
                        'Une opération d’à-nouveau existe déjà pour l’exercice "%s".',
                        $initialisation->getExercice()->getNom()
                    ),
                )
            );

            return;
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Absence d’écriture d’ouverture',
                message: sprintf(
                    'Aucune opération d’à-nouveau n’existe pour l’exercice "%s".',
                    $initialisation->getExercice()->getNom()
                ),
            )
        );
    }

    private function verifierLignes(
        InitialisationComptable $initialisation,
    ): void {
        if (!$initialisation->hasLignes()) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Lignes comptables',
                    message: 'Aucune ligne comptable n’a été préparée.',
                )
            );

            return;
        }

        foreach (
            $initialisation->getLignes()
            as $index => $ligne
        ) {
            if (!$ligne->getCompte()->getId()) {
                $initialisation->ajouterControle(
                    ControleInitialisation::erreur(
                        libelle: 'Comptes comptables',
                        message: sprintf(
                            'Le compte de la ligne %d n’est pas enregistré.',
                            $index + 1
                        ),
                    )
                );

                return;
            }

            if ($ligne->estVide()) {
                $initialisation->ajouterControle(
                    ControleInitialisation::erreur(
                        libelle: 'Lignes comptables',
                        message: sprintf(
                            'La ligne %d ne contient aucun montant.',
                            $index + 1
                        ),
                    )
                );

                return;
            }

            if ($ligne->estDebit() && $ligne->estCredit()) {
                $initialisation->ajouterControle(
                    ControleInitialisation::erreur(
                        libelle: 'Lignes comptables',
                        message: sprintf(
                            'La ligne %d contient simultanément un débit et un crédit.',
                            $index + 1
                        ),
                    )
                );

                return;
            }
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Lignes comptables',
                message: sprintf(
                    '%d ligne(s) comptable(s) ont été préparée(s).',
                    count($initialisation->getLignes())
                ),
            )
        );
    }

    private function verifierComptesCoproprietaires(
        InitialisationComptable $initialisation,
    ): void {
        foreach ($initialisation->getLignes() as $ligne) {
            $coproprietaire = $ligne->getCoproprietaire();

            if (!$coproprietaire) {
                continue;
            }

            $compteCoproprietaire =
                $coproprietaire->getCompte();

            if (!$compteCoproprietaire) {
                $initialisation->ajouterControle(
                    ControleInitialisation::erreur(
                        libelle: 'Comptes des copropriétaires',
                        message: sprintf(
                            'Aucun compte comptable n’est associé au copropriétaire "%s".',
                            (string) $coproprietaire
                        ),
                    )
                );

                return;
            }

            if (
                $compteCoproprietaire->getId()
                !== $ligne->getCompte()->getId()
            ) {
                $initialisation->ajouterControle(
                    ControleInitialisation::erreur(
                        libelle: 'Comptes des copropriétaires',
                        message: sprintf(
                            'Le compte de la ligne ne correspond pas au compte du copropriétaire "%s".',
                            (string) $coproprietaire
                        ),
                    )
                );

                return;
            }
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Comptes des copropriétaires',
                message: 'Tous les comptes des copropriétaires sont cohérents.',
            )
        );
    }

    private function verifierEquilibre(
        InitialisationComptable $initialisation,
    ): void {
        $totalDebit = $initialisation->getTotalDebit();
        $totalCredit = $initialisation->getTotalCredit();
        $ecart = $initialisation->getEcart();

        if (!$initialisation->isEquilibree()) {
            $initialisation->ajouterControle(
                ControleInitialisation::erreur(
                    libelle: 'Équilibre de l’écriture',
                    message: sprintf(
                        'Débits : %.2f € — Crédits : %.2f € — Écart : %.2f €.',
                        $totalDebit,
                        $totalCredit,
                        $ecart
                    ),
                )
            );

            return;
        }

        $initialisation->ajouterControle(
            ControleInitialisation::succes(
                libelle: 'Équilibre de l’écriture',
                message: sprintf(
                    'Les débits et les crédits sont équilibrés à %.2f €.',
                    $totalDebit
                ),
            )
        );
    }
}
