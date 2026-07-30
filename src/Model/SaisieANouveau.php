<?php

namespace App\Model;

use App\Entity\Exercice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class SaisieANouveau
{
    private ?Exercice $exercice = null;

    #[Assert\NotBlank]
    private string $libelle = 'À-nouveaux';

    /**
     * @var Collection<int, ANouveauLigne>
     */
    #[Assert\Valid]
    #[Assert\Count(
        min: 1,
        minMessage: 'Vous devez saisir au moins une ligne.'
    )]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();

        // On prépare quelques lignes vides à l'ouverture.
        for ($i = 0; $i < 10; $i++) {
            $this->addLigne(new ANouveauLigne());
        }
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(?Exercice $exercice): self
    {
        $this->exercice = $exercice;

        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, ANouveauLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(ANouveauLigne $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
        }

        return $this;
    }

    public function removeLigne(ANouveauLigne $ligne): self
    {
        $this->lignes->removeElement($ligne);

        return $this;
    }

    public function getTotalDebit(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            if ($ligne->estDebit()) {
                $total += $ligne->getMontantAbsolu();
            }
        }

        return round($total, 2);
    }

    public function getTotalCredit(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            if ($ligne->estCredit()) {
                $total += $ligne->getMontantAbsolu();
            }
        }

        return round($total, 2);
    }

    public function estEquilibree(): bool
    {
        return abs(
            $this->getTotalDebit() - $this->getTotalCredit()
        ) < 0.01;
    }
}
