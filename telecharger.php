<?php
/* ============================================================
telecharger.php
Téléchargement réel d'un document académique.

Usage depuis le front : <a href="telecharger.php?id=12">Télécharger</a>
(pas un appel fetch() — un vrai lien, pour que le navigateur
déclenche le téléchargement du fichier)

Les fichiers physiques doivent être stockés dans /uploads/documents/
============================================================ */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

if (!estConnecte()) {
http_response_code(401);
die('Vous devez être connecté pour télécharger ce document.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
http_response_code(400);
die('Document invalide.');
}

$db = getDB();
$stmt = $db->prepare('SELECT titre, fichier, classe_id FROM documents WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
http_response_code(404);
die('Document introuvable.');
}

// Si le document est réservé à une classe précise, vérifier que
// l'étudiant connecté en fait bien partie
if ($doc['classe_id'] !== null && getRole() === 'etudiant') {
$stmt = $db->prepare('SELECT classe_id FROM etudiants WHERE id = ?');
$stmt->execute([getEtudiantId()]);
$classeEtudiant = (int)($stmt->fetch()['classe_id'] ?? 0);

if ($classeEtudiant !== (int)$doc['classe_id']) {
http_response_code(403);
die('Accès refusé à ce document.');
}
}

// Chemin physique réel sur le serveur — le champ "fichier" en base
// ne doit contenir que le nom du fichier, pas un chemin arbitraire
// (sécurité : on évite que quelqu'un mette "../../etc/passwd" en base)
$nomFichier = basename($doc['fichier']);
$cheminComplet = __DIR__ . '/uploads/documents/' . $nomFichier;

if (!file_exists($cheminComplet)) {
http_response_code(404);
die('Le fichier physique est introuvable sur le serveur.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['titre']) . '_' . $nomFichier . '"');
header('Content-Length: ' . filesize($cheminComplet));
header('Cache-Control: no-cache');

readfile($cheminComplet);
exit;
