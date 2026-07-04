<?php

namespace App\Dto;

use App\Entity\Exercice;
use App\Entity\Operation;

final class WorkflowCloture
{
private Exercice $exercice;

private EtatCloture $etat;

/** @var Regularisation[] */
private array $regularisations = [];

/** @var SoldeReportable[] */
private array $soldesReportables = [];

private ?Operation $operationOuverture = null;
}