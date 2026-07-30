<?php

namespace App\Repository;

use App\Entity\Journal;
use App\Enum\JournalCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JournalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);
    }

    public function findByCode(JournalCode $code): Journal
    {
        $journal = $this->findOneBy([
            'code' => $code->value,
        ]);

        if (!$journal) {
            throw new \RuntimeException(
                sprintf(
                    'Le journal "%s" est introuvable.',
                    $code->value
                )
            );
        }

        return $journal;
    }
}
