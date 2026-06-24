-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 21 juin 2026 à 16:56
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `scolaris`
--

-- --------------------------------------------------------

--
-- Structure de la table `administrateurs`
--

CREATE TABLE `administrateurs` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `niveau_acces` enum('super_admin','admin') DEFAULT 'admin',
  `service` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annees_academiques`
--

CREATE TABLE `annees_academiques` (
  `id` int(11) NOT NULL,
  `libelle` varchar(20) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `annees_academiques`
--

INSERT INTO `annees_academiques` (`id`, `libelle`, `date_debut`, `date_fin`, `actif`, `created_at`) VALUES
(1, '2025-2026', '2025-09-01', '2026-07-31', 1, '2026-06-20 18:00:19');

-- --------------------------------------------------------

--
-- Structure de la table `attributions`
--

CREATE TABLE `attributions` (
  `id` int(11) NOT NULL,
  `professeur_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bulletins`
--

CREATE TABLE `bulletins` (
  `id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `semestre` enum('S1','S2') NOT NULL,
  `moyenne_generale` decimal(4,2) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  `statut` enum('brouillon','valide','publie') NOT NULL DEFAULT 'brouillon',
  `date_publication` datetime DEFAULT NULL,
  `fichier_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `filiere_id` int(11) NOT NULL,
  `niveau_id` int(11) NOT NULL,
  `option_id` int(11) DEFAULT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `effectif_max` int(11) DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `filiere_id`, `niveau_id`, `option_id`, `annee_academique_id`, `effectif_max`, `created_at`) VALUES
(1, 1, 1, NULL, 1, 50, '2026-06-20 23:00:50');

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `utilisateur1_id` int(11) NOT NULL,
  `utilisateur2_id` int(11) NOT NULL,
  `sujet` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devoirs_controles`
--

CREATE TABLE `devoirs_controles` (
  `id` int(11) NOT NULL,
  `type` enum('devoir','controle') NOT NULL DEFAULT 'devoir',
  `matiere_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `professeur_id` int(11) NOT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `semestre` enum('S1','S2') NOT NULL,
  `date_evaluation` date NOT NULL,
  `heure` time DEFAULT NULL,
  `salle` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `categorie` enum('cours','td','administratif') NOT NULL DEFAULT 'cours',
  `fichier` varchar(255) NOT NULL,
  `taille_ko` int(11) DEFAULT NULL,
  `matiere_id` int(11) DEFAULT NULL,
  `classe_id` int(11) DEFAULT NULL,
  `uploader_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emploi_du_temps`
--

CREATE TABLE `emploi_du_temps` (
  `id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `professeur_id` int(11) NOT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `semestre` enum('S1','S2') NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `salle` varchar(50) DEFAULT NULL,
  `type_cours` enum('cours','td','tp') NOT NULL DEFAULT 'cours',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

CREATE TABLE `etudiants` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `matricule` varchar(30) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id`, `utilisateur_id`, `matricule`, `classe_id`, `date_naissance`, `lieu_naissance`, `telephone`, `photo`, `created_at`) VALUES
(1, 3, 'ETU2026001', 1, NULL, NULL, NULL, NULL, '2026-06-21 13:17:54');

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

CREATE TABLE `filieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id`, `nom`, `code`, `description`, `created_at`) VALUES
(1, 'Informatique', '2135', NULL, '2026-06-20 22:58:46');

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

CREATE TABLE `matieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `coefficient` int(11) NOT NULL DEFAULT 1,
  `credits` int(11) NOT NULL DEFAULT 0,
  `ue_id` int(11) NOT NULL,
  `filiere_id` int(11) NOT NULL,
  `niveau_id` int(11) NOT NULL,
  `semestre` enum('S1','S2') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id`, `nom`, `code`, `coefficient`, `credits`, `ue_id`, `filiere_id`, `niveau_id`, `semestre`, `created_at`) VALUES
(1, 'Algorithmique', NULL, 3, 2, 1, 1, 2, 'S1', '2026-06-20 23:04:09');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `niveaux`
--

CREATE TABLE `niveaux` (
  `id` int(11) NOT NULL,
  `libelle` varchar(20) NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `niveaux`
--

INSERT INTO `niveaux` (`id`, `libelle`, `ordre`, `created_at`) VALUES
(1, 'L1', 1, '2026-06-20 18:00:19'),
(2, 'L2', 2, '2026-06-20 18:00:19'),
(3, 'L3', 3, '2026-06-20 18:00:19'),
(4, 'M1', 4, '2026-06-20 18:00:19'),
(5, 'M2', 5, '2026-06-20 18:00:19');

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `professeur_id` int(11) NOT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `semestre` enum('S1','S2') NOT NULL,
  `note` decimal(4,2) NOT NULL,
  `observation` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notes`
--

INSERT INTO `notes` (`id`, `etudiant_id`, `matiere_id`, `professeur_id`, `annee_academique_id`, `semestre`, `note`, `observation`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 1, 1, 'S1', 14.00, NULL, '2026-06-21 13:18:52', '2026-06-21 13:18:52');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('bulletin','paiement','note','emploi_du_temps','message','devoir_controle','general') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `options_filiere`
--

CREATE TABLE `options_filiere` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `filiere_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `annee_academique_id` int(11) NOT NULL,
  `montant_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montant_paye` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_dernier_paiement` date DEFAULT NULL,
  `statut` enum('non_paye','partiel','complet') NOT NULL DEFAULT 'non_paye',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `professeurs`
--

CREATE TABLE `professeurs` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `specialite` varchar(200) DEFAULT NULL,
  `grade` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `professeurs`
--

INSERT INTO `professeurs` (`id`, `utilisateur_id`, `specialite`, `grade`, `telephone`, `created_at`) VALUES
(1, 4, 'Informatique', 'Docteur', '0146763771', '2026-06-21 13:01:38');

-- --------------------------------------------------------

--
-- Structure de la table `unites_enseignement`
--

CREATE TABLE `unites_enseignement` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `credits` int(11) NOT NULL DEFAULT 0,
  `niveau_id` int(11) NOT NULL,
  `semestre` enum('S1','S2') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `unites_enseignement`
--

INSERT INTO `unites_enseignement` (`id`, `code`, `nom`, `credits`, `niveau_id`, `semestre`, `created_at`) VALUES
(1, '2135', 'Informatique fondamentale', 6, 2, 'S1', '2026-06-20 23:03:06');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('admin','professeur','etudiant') NOT NULL DEFAULT 'etudiant',
  `statut` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `statut`, `created_at`, `updated_at`) VALUES
(2, 'Admin', 'Scolaris', 'admin@scolaris.tg', '$2y$10$ZextSBCLcse/nOVZY9/WL.UzMPap.zDEjC7R40kCtqhspRzKV3FbS', 'admin', 'actif', '2026-06-20 22:53:44', '2026-06-20 22:53:44'),
(3, 'LOKO DANSOU', 'Peace', 'peace@test.com', '$2y$10$ZextSBCLcse/nOVZY9/WL.UzMPap.zDEjC7R40kCtqhspRzKV3FbS', 'etudiant', 'actif', '2026-06-20 23:14:18', '2026-06-20 23:14:18'),
(4, 'AMOUSSOU', 'Luc', 'prof.amoussou@test.com', '$2y$10$ZextSBCLcse/nOVZY9/WL.UzMPap.zDEjC7R40kCtqhspRzKV3FbS', 'professeur', 'actif', '2026-06-21 13:00:13', '2026-06-21 13:55:38');

-- --------------------------------------------------------

--
-- Structure de la table `versements`
--

CREATE TABLE `versements` (
  `id` int(11) NOT NULL,
  `paiement_id` int(11) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_versement` date NOT NULL,
  `mode` enum('especes','virement','mobile_money','cheque') DEFAULT 'especes',
  `reference` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `administrateurs`
--
ALTER TABLE `administrateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `annees_academiques`
--
ALTER TABLE `annees_academiques`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `attributions`
--
ALTER TABLE `attributions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_attribution` (`professeur_id`,`matiere_id`,`classe_id`,`annee_academique_id`),
  ADD KEY `matiere_id` (`matiere_id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `bulletins`
--
ALTER TABLE `bulletins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_bulletin` (`etudiant_id`,`semestre`,`annee_academique_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `filiere_id` (`filiere_id`),
  ADD KEY `niveau_id` (`niveau_id`),
  ADD KEY `option_id` (`option_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_conversation` (`utilisateur1_id`,`utilisateur2_id`),
  ADD KEY `utilisateur2_id` (`utilisateur2_id`);

--
-- Index pour la table `devoirs_controles`
--
ALTER TABLE `devoirs_controles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matiere_id` (`matiere_id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `professeur_id` (`professeur_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matiere_id` (`matiere_id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Index pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `matiere_id` (`matiere_id`),
  ADD KEY `professeur_id` (`professeur_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilisateur_id` (`utilisateur_id`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `classe_id` (`classe_id`);

--
-- Index pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ue_id` (`ue_id`),
  ADD KEY `filiere_id` (`filiere_id`),
  ADD KEY `niveau_id` (`niveau_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `expediteur_id` (`expediteur_id`);

--
-- Index pour la table `niveaux`
--
ALTER TABLE `niveaux`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_note` (`etudiant_id`,`matiere_id`,`semestre`,`annee_academique_id`),
  ADD KEY `matiere_id` (`matiere_id`),
  ADD KEY `professeur_id` (`professeur_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `options_filiere`
--
ALTER TABLE `options_filiere`
  ADD PRIMARY KEY (`id`),
  ADD KEY `filiere_id` (`filiere_id`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_paiement` (`etudiant_id`,`annee_academique_id`),
  ADD KEY `annee_academique_id` (`annee_academique_id`);

--
-- Index pour la table `professeurs`
--
ALTER TABLE `professeurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `niveau_id` (`niveau_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `versements`
--
ALTER TABLE `versements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paiement_id` (`paiement_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `administrateurs`
--
ALTER TABLE `administrateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `annees_academiques`
--
ALTER TABLE `annees_academiques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `attributions`
--
ALTER TABLE `attributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bulletins`
--
ALTER TABLE `bulletins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devoirs_controles`
--
ALTER TABLE `devoirs_controles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `etudiants`
--
ALTER TABLE `etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `matieres`
--
ALTER TABLE `matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `niveaux`
--
ALTER TABLE `niveaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `options_filiere`
--
ALTER TABLE `options_filiere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `professeurs`
--
ALTER TABLE `professeurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `versements`
--
ALTER TABLE `versements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `administrateurs`
--
ALTER TABLE `administrateurs`
  ADD CONSTRAINT `fk_admin_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `attributions`
--
ALTER TABLE `attributions`
  ADD CONSTRAINT `attributions_ibfk_1` FOREIGN KEY (`professeur_id`) REFERENCES `professeurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attributions_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attributions_ibfk_3` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attributions_ibfk_4` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bulletins`
--
ALTER TABLE `bulletins`
  ADD CONSTRAINT `bulletins_ibfk_1` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletins_ibfk_2` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`);

--
-- Contraintes pour la table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`),
  ADD CONSTRAINT `classes_ibfk_3` FOREIGN KEY (`option_id`) REFERENCES `options_filiere` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `classes_ibfk_4` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`);

--
-- Contraintes pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`utilisateur1_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`utilisateur2_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `devoirs_controles`
--
ALTER TABLE `devoirs_controles`
  ADD CONSTRAINT `devoirs_controles_ibfk_1` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devoirs_controles_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devoirs_controles_ibfk_3` FOREIGN KEY (`professeur_id`) REFERENCES `professeurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devoirs_controles_ibfk_4` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`uploader_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD CONSTRAINT `emploi_du_temps_ibfk_1` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emploi_du_temps_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emploi_du_temps_ibfk_3` FOREIGN KEY (`professeur_id`) REFERENCES `professeurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emploi_du_temps_ibfk_4` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `etudiants_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`);

--
-- Contraintes pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD CONSTRAINT `matieres_ibfk_1` FOREIGN KEY (`ue_id`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matieres_ibfk_2` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`),
  ADD CONSTRAINT `matieres_ibfk_3` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`);

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`professeur_id`) REFERENCES `professeurs` (`id`),
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `options_filiere`
--
ALTER TABLE `options_filiere`
  ADD CONSTRAINT `options_filiere_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`);

--
-- Contraintes pour la table `professeurs`
--
ALTER TABLE `professeurs`
  ADD CONSTRAINT `professeurs_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  ADD CONSTRAINT `unites_enseignement_ibfk_1` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`);

--
-- Contraintes pour la table `versements`
--
ALTER TABLE `versements`
  ADD CONSTRAINT `versements_ibfk_1` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
