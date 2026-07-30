-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251215.aa153def95
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : Sam. 30 Mai 2026 à 14:18
-- Version du serveur : 8.0.18
-- Version de PHP : 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `syndic4`
--

-- --------------------------------------------------------

--
-- Structure de la table `affectation_paiement`
--

CREATE TABLE `affectation_paiement` (
  `id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `paiement_id` int(11) NOT NULL,
  `ligne_appel_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `appel_fond`
--

CREATE TABLE `appel_fond` (
  `id` int(11) NOT NULL,
  `date_appel` date NOT NULL,
  `montant_total` decimal(10,2) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `budget_id` int(11) NOT NULL,
  `date_reglement` date DEFAULT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `budget`
--

CREATE TABLE `budget` (
  `id` int(11) NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exercice_id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `verrouille` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `budget`
--

INSERT INTO `budget` (`id`, `libelle`, `exercice_id`, `copropriete_id`, `verrouille`) VALUES
(5, 'Budget prévisionnel', 1, 8, 0);

-- --------------------------------------------------------

--
-- Structure de la table `compte`
--

CREATE TABLE `compte` (
  `id` int(11) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `compte`
--

INSERT INTO `compte` (`id`, `numero`, `libelle`, `type`) VALUES
(3, '611000', 'Contrat d entretien et nettoyage', 'charge'),
(7, '626000', 'Frais postaux et télécoms', 'charge'),
(9, '627000', 'Services bancaires', 'charge'),
(13, '603000', 'Fournitures d entretien (Ampoules, outils)', 'charge'),
(14, '625000', 'Déplacements et missions', 'charge'),
(15, '401001', 'Fournisseur Elec STRASBOURG', 'tiers'),
(16, '401002', 'Fournisseur CMUT Banque', 'tiers'),
(17, '401003', 'Fournisseur La Poste', 'tiers'),
(18, '401004', 'Fournisseur Assurance MAVIT', 'tiers'),
(19, '401005', 'Fournisseur Leroy Merlin (Bricolage)', 'tiers'),
(20, '401006', 'Fournisseur GIFI', 'tiers'),
(21, '401007', 'Fournisseur Leclerc', 'tiers'),
(22, '401008', 'Fournisseur Total (Essence)', 'tiers'),
(23, '401009', 'Fournisseur Alpha Prestation (Ménage)', 'tiers'),
(24, '401010', 'Fournisseur SDEA (Eau)', 'tiers'),
(25, '401011', 'Fournisseur Thalès Expertise', 'tiers'),
(36, '450001', 'Copropriétaire Libs', 'tiers'),
(37, '450002', 'Copropriétaire Jouaville', 'tiers'),
(38, '450003', 'Copropriétaire Vieville', 'tiers'),
(39, '450004', 'Copropriétaire Feta', 'tiers'),
(40, '450005', 'Copropriétaire Pirot', 'tiers'),
(41, '450006', 'Copropriétaire Robichon & Ferre', 'tiers'),
(42, '102000', 'Fonds de réserve (Avances)', 'passif'),
(43, '105000', 'Fonds de travaux (Loi ALUR)', 'passif'),
(44, '489000', 'Résultat de l exercice', 'passif'),
(45, '450000', 'Compte collectif Copropriétaires', 'tiers'),
(46, '512000', 'Compte bancaire principal', 'banque'),
(47, '601000', 'Eau froide (Compteurs généraux)', 'charge'),
(48, '602000', 'Électricité (Parties communes)', 'charge'),
(49, '615000', 'Petites réparations et maintenance', 'charge'),
(50, '614000', 'Primes d assurances', 'charge'),
(51, '621000', 'Honoraires du Syndic', 'charge'),
(52, '671000', 'Travaux décidés (Hors budget courant)', 'charge'),
(53, '701000', 'Appels de fonds sur budget prévisionnel', 'produit'),
(54, '702000', 'Appels de fonds pour travaux', 'produit'),
(55, '714000', 'Produits divers (Intérêts, etc.)', 'produit');

-- --------------------------------------------------------

--
-- Structure de la table `coproprietaire`
--

CREATE TABLE `coproprietaire` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compte_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `coproprietaire`
--

INSERT INTO `coproprietaire` (`id`, `nom`, `prenom`, `email`, `telephone`, `compte_id`) VALUES
(1, 'Libs', 'Francis', 'fr.libs@gmail.com', '0687823380', 36),
(2, 'Jouaville', 'Alain', NULL, NULL, 37),
(3, 'Vieville', 'Laurence', NULL, NULL, 38),
(4, 'Feta', 'Ismail', NULL, NULL, 39),
(5, 'Pirot', 'Anne', NULL, NULL, 40),
(6, 'Robichon & Ferre', 'Claire & Jeremie', NULL, NULL, 41);

-- --------------------------------------------------------

--
-- Structure de la table `copropriete`
--

CREATE TABLE `copropriete` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_postal` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localite` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tantiemes_base` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `copropriete`
--

INSERT INTO `copropriete` (`id`, `nom`, `adresse`, `code_postal`, `localite`, `tantiemes_base`) VALUES
(8, '14 RUE NEUVE', '14, rue Neuve', '67200', 'Strasbourg', 10000);

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260529063602', '2026-05-29 06:36:40', 119),
('DoctrineMigrations\\Version20260529064111', '2026-05-29 06:41:18', 205),
('DoctrineMigrations\\Version20260529075735', '2026-05-29 07:57:41', 136),
('DoctrineMigrations\\Version20260529080117', '2026-05-29 08:01:23', 252),
('DoctrineMigrations\\Version20260529143629', '2026-05-29 14:36:44', 312);

-- --------------------------------------------------------

--
-- Structure de la table `ecriture`
--

CREATE TABLE `ecriture` (
  `id` int(11) NOT NULL,
  `debit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `compte_id` int(11) NOT NULL,
  `operation_id` int(11) NOT NULL,
  `exercice_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) DEFAULT NULL,
  `date` datetime NOT NULL
) ;

-- --------------------------------------------------------

--
-- Structure de la table `exercice`
--

CREATE TABLE `exercice` (
  `id` int(11) NOT NULL,
  `nom` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `copropriete_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exercice`
--

INSERT INTO `exercice` (`id`, `nom`, `date_debut`, `date_fin`, `statut`, `copropriete_id`) VALUES
(1, '2025', '2025-01-01 00:00:00', '2025-12-31 00:00:00', 'ouvert', 8);

-- --------------------------------------------------------

--
-- Structure de la table `facture_fournisseur`
--

CREATE TABLE `facture_fournisseur` (
  `id` int(11) NOT NULL,
  `numero` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_facture` date NOT NULL,
  `date_reglement` date DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `montant_regle` decimal(10,2) NOT NULL DEFAULT '0.00',
  `soldee` tinyint(4) NOT NULL,
  `comptabilisee` tinyint(4) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `type_charge_id` int(11) NOT NULL,
  `operation_id` int(11) DEFAULT NULL,
  `exercice_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseur`
--

CREATE TABLE `fournisseur` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `localite` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compte_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseur`
--

INSERT INTO `fournisseur` (`id`, `nom`, `adresse`, `code_postal`, `localite`, `compte_id`) VALUES
(1, 'Elec STRASBOURG', NULL, NULL, NULL, 15),
(2, 'CMUT Banque', NULL, NULL, NULL, 16),
(3, 'Poste', NULL, NULL, NULL, 17),
(4, 'Assurance MAVIT', NULL, NULL, NULL, 18),
(5, 'Bricolage1 Leroy Merlin', NULL, NULL, NULL, 19),
(6, 'Bricolage2 GIFI', NULL, NULL, NULL, 20),
(7, 'Alimentation divers Leclerc', NULL, NULL, NULL, 21),
(8, 'Total (essence)', NULL, NULL, NULL, 22),
(9, 'Alpha Prestation(ménage)', NULL, NULL, NULL, 23),
(10, 'SDEA eau', NULL, NULL, NULL, 24),
(11, 'Thalès Expertise', NULL, NULL, NULL, 25);

-- --------------------------------------------------------

--
-- Structure de la table `ligne_appel_fond`
--

CREATE TABLE `ligne_appel_fond` (
  `id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `appel_fond_id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `pourcentage` decimal(5,2) DEFAULT NULL,
  `coproprietaire_id` int(11) NOT NULL,
  `montant_regle` decimal(10,2) NOT NULL DEFAULT '0.00',
  `soldee` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_budget`
--

CREATE TABLE `ligne_budget` (
  `id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `type_charge_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ligne_budget`
--

INSERT INTO `ligne_budget` (`id`, `montant`, `budget_id`, `type_charge_id`) VALUES
(1, 100.00, 5, 1),
(2, 1000.00, 5, 2),
(4, 821.00, 5, 3);

-- --------------------------------------------------------

--
-- Structure de la table `lot`
--

CREATE TABLE `lot` (
  `id` int(11) NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tantiemes` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `lot`
--

INSERT INTO `lot` (`id`, `reference`, `designation`, `tantiemes`, `copropriete_id`) VALUES
(49, '1', 'RDC gauche', 1063, 8),
(50, '2', 'RDC droit', 1225, 8),
(51, '3', '1er étage gauche', 997, 8),
(52, '4', '1er étage droit', 1588, 8),
(53, '5', '2e étage gauche', 1014, 8),
(54, '6', '2e étage droit', 1631, 8),
(55, '7', '3e étage gauche', 961, 8),
(56, '8', '3e étage droit', 1521, 8);

-- --------------------------------------------------------

--
-- Structure de la table `lot_coproprietaire`
--

CREATE TABLE `lot_coproprietaire` (
  `id` int(11) NOT NULL,
  `pourcentage` decimal(5,2) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `lot_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `lot_coproprietaire`
--

INSERT INTO `lot_coproprietaire` (`id`, `pourcentage`, `date_debut`, `date_fin`, `lot_id`, `coproprietaire_id`) VALUES
(1, 100.00, '2026-04-22 00:00:00', NULL, 51, 1),
(2, 100.00, '2026-04-22 00:00:00', NULL, 53, 1),
(3, 100.00, '2026-04-22 00:00:00', NULL, 56, 2),
(4, 100.00, '2026-04-22 00:00:00', NULL, 52, 5),
(5, 100.00, '2026-04-22 00:00:00', NULL, 54, 3),
(6, 100.00, '2026-04-22 00:00:00', NULL, 55, 3),
(7, 100.00, '2026-04-22 00:00:00', NULL, 50, 4),
(8, 100.00, '2026-04-22 00:00:00', NULL, 49, 6);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `operation`
--

CREATE TABLE `operation` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `piece` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_charge_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `fournisseur_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `id` int(11) NOT NULL,
  `date_paiement` date NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exercice_id` int(11) NOT NULL,
  `operation_id` int(11) DEFAULT NULL,
  `coproprietaire_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `repartition`
--

CREATE TABLE `repartition` (
  `id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `tantiemes` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `ecriture_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `type_charge`
--

CREATE TABLE `type_charge` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `compte_id` int(11) NOT NULL,
  `mode_repartition` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `type_charge`
--

INSERT INTO `type_charge` (`id`, `nom`, `compte_id`, `mode_repartition`) VALUES
(1, 'Electricité des communs', 15, 'tantiemes'),
(2, 'Nettoyage', 23, 'tantiemes'),
(3, 'Assurance copropriété', 18, 'tantiemes'),
(4, 'Banque à distance', 16, 'tantiemes'),
(5, 'Frais postaux', 17, 'tantiemes'),
(7, 'Consommation eau générale', 24, 'tantiemes');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `affectation_paiement`
--
ALTER TABLE `affectation_paiement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_EAC0DB2D2A4C4478` (`paiement_id`),
  ADD KEY `IDX_EAC0DB2D78911F34` (`ligne_appel_id`);

--
-- Index pour la table `appel_fond`
--
ALTER TABLE `appel_fond`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_71C066AE36ABA6B8` (`budget_id`);

--
-- Index pour la table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_73F2F77B89D40298` (`exercice_id`),
  ADD KEY `IDX_73F2F77B6B07769E` (`copropriete_id`);

--
-- Index pour la table `compte`
--
ALTER TABLE `compte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_CFF65260F55AE19E` (`numero`);

--
-- Index pour la table `coproprietaire`
--
ALTER TABLE `coproprietaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1AB283E7F2C56620` (`compte_id`);

--
-- Index pour la table `copropriete`
--
ALTER TABLE `copropriete`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `ecriture`
--
ALTER TABLE `ecriture`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3098DEBF2C56620` (`compte_id`),
  ADD KEY `IDX_3098DEB44AC3583` (`operation_id`),
  ADD KEY `IDX_3098DEB89D40298` (`exercice_id`),
  ADD KEY `IDX_3098DEBFF2D1A27` (`coproprietaire_id`);

--
-- Index pour la table `exercice`
--
ALTER TABLE `exercice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E418C74D6B07769E` (`copropriete_id`);

--
-- Index pour la table `facture_fournisseur`
--
ALTER TABLE `facture_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_311911C444AC3583` (`operation_id`),
  ADD KEY `IDX_311911C4670C757F` (`fournisseur_id`),
  ADD KEY `IDX_311911C4E1EE0804` (`type_charge_id`),
  ADD KEY `IDX_311911C489D40298` (`exercice_id`);

--
-- Index pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_369ECA32F2C56620` (`compte_id`);

--
-- Index pour la table `ligne_appel_fond`
--
ALTER TABLE `ligne_appel_fond`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_926C88B31D64BBBA` (`appel_fond_id`),
  ADD KEY `IDX_926C88B3A8CBA5F7` (`lot_id`),
  ADD KEY `IDX_926C88B3FF2D1A27` (`coproprietaire_id`);

--
-- Index pour la table `ligne_budget`
--
ALTER TABLE `ligne_budget`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_55286B3D36ABA6B8` (`budget_id`),
  ADD KEY `IDX_55286B3DE1EE0804` (`type_charge_id`);

--
-- Index pour la table `lot`
--
ALTER TABLE `lot`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B81291B6B07769E` (`copropriete_id`);

--
-- Index pour la table `lot_coproprietaire`
--
ALTER TABLE `lot_coproprietaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_330138DDA8CBA5F7` (`lot_id`),
  ADD KEY `IDX_330138DDFF2D1A27` (`coproprietaire_id`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`);

--
-- Index pour la table `operation`
--
ALTER TABLE `operation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1981A66DE1EE0804` (`type_charge_id`),
  ADD KEY `IDX_1981A66DA8CBA5F7` (`lot_id`),
  ADD KEY `IDX_1981A66D670C757F` (`fournisseur_id`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_B1DC7A1E44AC3583` (`operation_id`),
  ADD KEY `IDX_B1DC7A1E89D40298` (`exercice_id`),
  ADD KEY `IDX_B1DC7A1EFF2D1A27` (`coproprietaire_id`);

--
-- Index pour la table `repartition`
--
ALTER TABLE `repartition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_82B791A0A8CBA5F7` (`lot_id`),
  ADD KEY `IDX_82B791A03407A4D0` (`ecriture_id`),
  ADD KEY `IDX_82B791A0FF2D1A27` (`coproprietaire_id`);

--
-- Index pour la table `type_charge`
--
ALTER TABLE `type_charge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_76BCCF0CF2C56620` (`compte_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `affectation_paiement`
--
ALTER TABLE `affectation_paiement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `appel_fond`
--
ALTER TABLE `appel_fond`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `budget`
--
ALTER TABLE `budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `compte`
--
ALTER TABLE `compte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT pour la table `coproprietaire`
--
ALTER TABLE `coproprietaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `copropriete`
--
ALTER TABLE `copropriete`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `ecriture`
--
ALTER TABLE `ecriture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exercice`
--
ALTER TABLE `exercice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `facture_fournisseur`
--
ALTER TABLE `facture_fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `ligne_appel_fond`
--
ALTER TABLE `ligne_appel_fond`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `ligne_budget`
--
ALTER TABLE `ligne_budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `lot`
--
ALTER TABLE `lot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT pour la table `lot_coproprietaire`
--
ALTER TABLE `lot_coproprietaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `operation`
--
ALTER TABLE `operation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `repartition`
--
ALTER TABLE `repartition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `type_charge`
--
ALTER TABLE `type_charge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affectation_paiement`
--
ALTER TABLE `affectation_paiement`
  ADD CONSTRAINT `FK_EAC0DB2D2A4C4478` FOREIGN KEY (`paiement_id`) REFERENCES `paiement` (`id`),
  ADD CONSTRAINT `FK_EAC0DB2D78911F34` FOREIGN KEY (`ligne_appel_id`) REFERENCES `ligne_appel_fond` (`id`);

--
-- Contraintes pour la table `appel_fond`
--
ALTER TABLE `appel_fond`
  ADD CONSTRAINT `FK_71C066AE36ABA6B8` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`);

--
-- Contraintes pour la table `budget`
--
ALTER TABLE `budget`
  ADD CONSTRAINT `FK_73F2F77B6B07769E` FOREIGN KEY (`copropriete_id`) REFERENCES `copropriete` (`id`),
  ADD CONSTRAINT `FK_73F2F77B89D40298` FOREIGN KEY (`exercice_id`) REFERENCES `exercice` (`id`);

--
-- Contraintes pour la table `coproprietaire`
--
ALTER TABLE `coproprietaire`
  ADD CONSTRAINT `FK_1AB283E7F2C56620` FOREIGN KEY (`compte_id`) REFERENCES `compte` (`id`);

--
-- Contraintes pour la table `ecriture`
--
ALTER TABLE `ecriture`
  ADD CONSTRAINT `FK_3098DEB44AC3583` FOREIGN KEY (`operation_id`) REFERENCES `operation` (`id`),
  ADD CONSTRAINT `FK_3098DEB89D40298` FOREIGN KEY (`exercice_id`) REFERENCES `exercice` (`id`),
  ADD CONSTRAINT `FK_3098DEBF2C56620` FOREIGN KEY (`compte_id`) REFERENCES `compte` (`id`),
  ADD CONSTRAINT `FK_3098DEBFF2D1A27` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaire` (`id`);

--
-- Contraintes pour la table `exercice`
--
ALTER TABLE `exercice`
  ADD CONSTRAINT `FK_E418C74D6B07769E` FOREIGN KEY (`copropriete_id`) REFERENCES `copropriete` (`id`);

--
-- Contraintes pour la table `facture_fournisseur`
--
ALTER TABLE `facture_fournisseur`
  ADD CONSTRAINT `FK_311911C444AC3583` FOREIGN KEY (`operation_id`) REFERENCES `operation` (`id`),
  ADD CONSTRAINT `FK_311911C4670C757F` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseur` (`id`),
  ADD CONSTRAINT `FK_311911C489D40298` FOREIGN KEY (`exercice_id`) REFERENCES `exercice` (`id`),
  ADD CONSTRAINT `FK_311911C4E1EE0804` FOREIGN KEY (`type_charge_id`) REFERENCES `type_charge` (`id`);

--
-- Contraintes pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  ADD CONSTRAINT `FK_369ECA32F2C56620` FOREIGN KEY (`compte_id`) REFERENCES `compte` (`id`);

--
-- Contraintes pour la table `ligne_appel_fond`
--
ALTER TABLE `ligne_appel_fond`
  ADD CONSTRAINT `FK_926C88B31D64BBBA` FOREIGN KEY (`appel_fond_id`) REFERENCES `appel_fond` (`id`),
  ADD CONSTRAINT `FK_926C88B3A8CBA5F7` FOREIGN KEY (`lot_id`) REFERENCES `lot` (`id`),
  ADD CONSTRAINT `FK_926C88B3FF2D1A27` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaire` (`id`);

--
-- Contraintes pour la table `ligne_budget`
--
ALTER TABLE `ligne_budget`
  ADD CONSTRAINT `FK_55286B3D36ABA6B8` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`),
  ADD CONSTRAINT `FK_55286B3DE1EE0804` FOREIGN KEY (`type_charge_id`) REFERENCES `type_charge` (`id`);

--
-- Contraintes pour la table `lot`
--
ALTER TABLE `lot`
  ADD CONSTRAINT `FK_B81291B6B07769E` FOREIGN KEY (`copropriete_id`) REFERENCES `copropriete` (`id`);

--
-- Contraintes pour la table `lot_coproprietaire`
--
ALTER TABLE `lot_coproprietaire`
  ADD CONSTRAINT `FK_330138DDA8CBA5F7` FOREIGN KEY (`lot_id`) REFERENCES `lot` (`id`),
  ADD CONSTRAINT `FK_330138DDFF2D1A27` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaire` (`id`);

--
-- Contraintes pour la table `operation`
--
ALTER TABLE `operation`
  ADD CONSTRAINT `FK_1981A66D670C757F` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseur` (`id`),
  ADD CONSTRAINT `FK_1981A66DA8CBA5F7` FOREIGN KEY (`lot_id`) REFERENCES `lot` (`id`),
  ADD CONSTRAINT `FK_1981A66DE1EE0804` FOREIGN KEY (`type_charge_id`) REFERENCES `type_charge` (`id`);

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `FK_B1DC7A1E44AC3583` FOREIGN KEY (`operation_id`) REFERENCES `operation` (`id`),
  ADD CONSTRAINT `FK_B1DC7A1E89D40298` FOREIGN KEY (`exercice_id`) REFERENCES `exercice` (`id`),
  ADD CONSTRAINT `FK_B1DC7A1EFF2D1A27` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaire` (`id`);

--
-- Contraintes pour la table `repartition`
--
ALTER TABLE `repartition`
  ADD CONSTRAINT `FK_82B791A03407A4D0` FOREIGN KEY (`ecriture_id`) REFERENCES `ecriture` (`id`),
  ADD CONSTRAINT `FK_82B791A0A8CBA5F7` FOREIGN KEY (`lot_id`) REFERENCES `lot` (`id`),
  ADD CONSTRAINT `FK_82B791A0FF2D1A27` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaire` (`id`);

--
-- Contraintes pour la table `type_charge`
--
ALTER TABLE `type_charge`
  ADD CONSTRAINT `fk_type_charge_compte` FOREIGN KEY (`compte_id`) REFERENCES `compte` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
