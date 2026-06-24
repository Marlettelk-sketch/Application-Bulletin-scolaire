<?php
/* ============================================================
seed.php
Script de démarrage — à exécuter UNE SEULE FOIS
dans le navigateur : http://localhost/scolaris/seed.php

Crée le compte administrateur par défaut.
SUPPRIMEZ CE FICHIER après l'avoir exécuté.
============================================================ */

require_once __DIR__ . '/config/database.php';

// --- Compte admin par défaut ---
$nom = 'Admin';
$prenom = 'Scolaris';
$email = 'admin@scolaris.tg';
$mdp = 'Admin@2025'; // ← Changez ce mot de passe après la 1ère connexion

try {
$db = getDB();

// Vérifier si l'admin existe déjà
$check = $db->prepare('SELECT id FROM utilisateurs WHERE email = ?');
$check->execute([$email]);

if ($check->fetch()) {
echo '<p style="color:orange">⚠️ Le compte admin existe déjà. Rien n\'a été modifié.</p>';
} else {
$hash = password_hash($mdp, PASSWORD_BCRYPT);
$stmt = $db->prepare(
'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut)
VALUES (?, ?, ?, ?, "admin", "actif")'
);
$stmt->execute([$nom, $prenom, $email, $hash]);

echo '<h2 style="color:green">✅ Compte admin créé avec succès !</h2>';
echo '<ul>';
echo '<li><strong>Email :</strong> ' . htmlspecialchars($email) . '</li>';
echo '<li><strong>Mot de passe :</strong> ' . htmlspecialchars($mdp) . '</li>';
echo '</ul>';
echo '<p style="color:red"><strong>⚠️ Changez le mot de passe dès la première connexion, puis supprimez ce fichier.</strong></p>';
}

} catch (Exception $e) {
echo '<p style="color:red">❌ Erreur : ' . htmlspecialchars($e->getMessage()) . '</p>';
echo '<p>Vérifiez que vous avez bien importé <strong>scolaris.sql</strong> dans phpMyAdmin avant d\'exécuter ce script.</p>';
}

