<?php
/* ============================================================
api/devoirs-controle.php
Gestion des devoirs et contrôles continus

GET ?action=liste &semestre=S1&annee_id=1
→ devoirs/contrôles de la classe de l'étudiant connecté

POST ?action=ajouter {type, matiere_id, classe_id, semestre,
date_evaluation, heure, salle}
→ programmer un devoir/contrôle (prof/admin)

POST ?action=supprimer {id}
→ supprimer un devoir/contrôle (prof/admin)
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'liste' => 'listeDevoirsControles',
'ajouter' => 'ajouterDevoirControle',
'supprimer' => 'supprimerDevoirControle',
]);

/* ============================================================
Liste des devoirs/contrôles de la classe de l'étudiant connecté
(ou d'une classe précise, pour prof/admin)
============================================================ */
function listeDevoirsControles(): void {
$db = getDB();

$semestre = $_GET['semestre'] ?? 'S1';
$anneeId = (int)($_GET['annee_id'] ?? 0);
$classeId = (int)($_GET['classe_id'] ?? 0);

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');

if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}

if (getRole() === 'etudiant') {
$stmt = $db->prepare('SELECT classe_id FROM etudiants WHERE id = ?');
$stmt->execute([getEtudiantId()]);
$classeId = (int)($stmt->fetch()['classe_id'] ?? 0);
}

if (!$classeId) jsonErreur('classe_id requis');

$stmt = $db->prepare(
'SELECT dc.id, dc.type, dc.date_evaluation, dc.heure, dc.salle,
m.nom AS matiere,
CONCAT(u.prenom, " ", u.nom) AS professeur
FROM devoirs_controles dc
JOIN matieres m ON m.id = dc.matiere_id
JOIN professeurs p ON p.id = dc.professeur_id
JOIN utilisateurs u ON u.id = p.utilisateur_id
WHERE dc.classe_id = ? AND dc.semestre = ? AND dc.annee_academique_id = ?
ORDER BY dc.date_evaluation, dc.heure'
);
$stmt->execute([$classeId, $semestre, $anneeId]);
$liste = $stmt->fetchAll();

$devoirs = array_values(array_filter($liste, fn($d) => $d['type'] === 'devoir'));
$controles = array_values(array_filter($liste, fn($d) => $d['type'] === 'controle'));

jsonReponse([
'devoirs' => $devoirs,
'controles' => $controles,
'total' => count($liste),
]);
}

/* ============================================================
Programmer un devoir / contrôle (prof ou admin)
============================================================ */
function ajouterDevoirControle(): void {
exigerRoles(['admin', 'professeur']);

$data = getJson();
requis($data, ['type', 'matiere_id', 'classe_id', 'semestre', 'date_evaluation']);

$type = $data['type'];
$matiereId = (int)$data['matiere_id'];
$classeId = (int)$data['classe_id'];
$semestre = $data['semestre'];
$dateEvaluation = $data['date_evaluation'];
$heure = $data['heure'] ?? null;
$salle = trim($data['salle'] ?? '');

if (!in_array($type, ['devoir', 'controle'])) jsonErreur('Type invalide');
if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');

$db = getDB();

$anneeId = (int)($data['annee_id'] ?? 0);
if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}
if (!$anneeId) jsonErreur('Aucune année académique active');

// Déterminer le professeur : si l'appelant est prof, on prend son id ;
// si admin, il doit le préciser
$professeurId = getRole() === 'professeur'
? getProfesseurId()
: (int)($data['professeur_id'] ?? 0);

if (!$professeurId) jsonErreur('professeur_id requis');

$stmt = $db->prepare(
'INSERT INTO devoirs_controles
(type, matiere_id, classe_id, professeur_id, annee_academique_id, semestre, date_evaluation, heure, salle)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$type, $matiereId, $classeId, $professeurId, $anneeId, $semestre, $dateEvaluation, $heure, $salle]);
$id = (int)$db->lastInsertId();

// Notifier les étudiants de la classe concernée
$matiere = $db->prepare('SELECT nom FROM matieres WHERE id = ?');
$matiere->execute([$matiereId]);
$nomMatiere = $matiere->fetch()['nom'] ?? '';

$libelle = $type === 'devoir' ? 'Devoir' : 'Contrôle';
$etudiants = $db->prepare(
'SELECT utilisateur_id FROM etudiants WHERE classe_id = ?'
);
$etudiants->execute([$classeId]);
$insNotif = $db->prepare(
'INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, "emploi")'
);
foreach ($etudiants->fetchAll() as $e) {
$insNotif->execute([
$e['utilisateur_id'],
"$libelle programmé en $nomMatiere",
"Le $dateEvaluation" . ($heure ? " à $heure" : '') . ($salle ? " — Salle $salle" : ''),
]);
}

jsonReponse(['succes' => true, 'id' => $id], 201);
}

/* ============================================================
Supprimer un devoir / contrôle (prof ou admin)
============================================================ */
function supprimerDevoirControle(): void {
exigerRoles(['admin', 'professeur']);

$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM devoirs_controles WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) jsonErreur('Devoir/contrôle introuvable', 404);

jsonReponse(['succes' => true]);
}
