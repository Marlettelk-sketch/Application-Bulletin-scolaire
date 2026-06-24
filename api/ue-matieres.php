<?php
/* ============================================================
api/ue-matieres.php
Gestion des Unités d'Enseignement (UE) et des Matières

GET ?action=liste &semestre=S1&annee_id=1
→ UE + leurs matières (avec professeur attribué) pour le niveau
de l'étudiant connecté

POST ?action=ajouter_ue {code, nom, credits, niveau_id, semestre}
→ créer une UE (admin)

POST ?action=ajouter_matiere {nom, code, coefficient, credits,
ue_id, filiere_id, niveau_id, semestre}
→ créer une matière (admin)

POST ?action=attribuer_professeur {professeur_id, matiere_id,
classe_id, annee_id}
→ attribuer un professeur à une matière pour une classe (admin)
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'liste' => 'listeUEetMatieres',
'professeurs' => 'listeProfesseurs',
'ajouter_ue' => 'ajouterUE',
'ajouter_matiere' => 'ajouterMatiere',
'attribuer_professeur' => 'attribuerProfesseur',
]);

/* ============================================================
Liste des UE + matières (avec professeur attribué) pour le
niveau / classe de l'étudiant connecté
============================================================ */
function listeUEetMatieres(): void {
$db = getDB();

$semestre = $_GET['semestre'] ?? 'S1';
$anneeId = (int)($_GET['annee_id'] ?? 0);

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');

if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}

// Niveau de l'étudiant connecté (ou paramètre classe_id pour prof/admin)
$niveauId = 0;
$classeId = (int)($_GET['classe_id'] ?? 0);

if (getRole() === 'etudiant') {
$stmt = $db->prepare('SELECT classe_id FROM etudiants WHERE id = ?');
$stmt->execute([getEtudiantId()]);
$classeId = (int)($stmt->fetch()['classe_id'] ?? 0);
}

if ($classeId) {
$stmt = $db->prepare('SELECT niveau_id FROM classes WHERE id = ?');
$stmt->execute([$classeId]);
$niveauId = (int)($stmt->fetch()['niveau_id'] ?? 0);
}

if (!$niveauId) jsonErreur('Impossible de déterminer le niveau');

// Les UE du niveau / semestre
$stmt = $db->prepare(
'SELECT id, code, nom, credits, semestre
FROM unites_enseignement
WHERE niveau_id = ? AND semestre = ?
ORDER BY code'
);
$stmt->execute([$niveauId, $semestre]);
$listeUE = $stmt->fetchAll();

// Les matières de chaque UE, avec le professeur attribué (s'il y en a un
// pour la classe / année concernée)
$stmtMatieres = $db->prepare(
'SELECT m.id, m.nom, m.code, m.coefficient, m.credits,
CONCAT(u.prenom, " ", u.nom) AS professeur
FROM matieres m
LEFT JOIN attributions a ON a.matiere_id = m.id
AND a.classe_id = ? AND a.annee_academique_id = ?
LEFT JOIN professeurs p ON p.id = a.professeur_id
LEFT JOIN utilisateurs u ON u.id = p.utilisateur_id
WHERE m.ue_id = ?
ORDER BY m.nom'
);

foreach ($listeUE as &$ue) {
$stmtMatieres->execute([$classeId, $anneeId, $ue['id']]);
$ue['matieres'] = $stmtMatieres->fetchAll();
$ue['credits'] = (int)$ue['credits'];
}
unset($ue);

jsonReponse(['ue' => $listeUE]);
}

/* ============================================================
Liste des professeurs (pour les <select> du formulaire d'ajout)
============================================================ */
function listeProfesseurs(): void {
$db = getDB();
$rows = $db->query(
'SELECT p.id, CONCAT(u.prenom, " ", u.nom) AS nom_complet
FROM professeurs p
JOIN utilisateurs u ON u.id = p.utilisateur_id
ORDER BY u.nom'
)->fetchAll();

jsonReponse(['professeurs' => $rows]);
}

/* ============================================================
Ajouter une UE (admin)
============================================================ */
function ajouterUE(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['code', 'nom', 'niveau_id', 'semestre']);

$code = trim($data['code']);
$nom = trim($data['nom']);
$credits = (int)($data['credits'] ?? 0);
$niveauId = (int)$data['niveau_id'];
$semestre = $data['semestre'];

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');

$db = getDB();
$stmt = $db->prepare(
'INSERT INTO unites_enseignement (code, nom, credits, niveau_id, semestre)
VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$code, $nom, $credits, $niveauId, $semestre]);

jsonReponse(['succes' => true, 'id' => (int)$db->lastInsertId()], 201);
}

/* ============================================================
Ajouter une matière à une UE (admin)
============================================================ */
function ajouterMatiere(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['nom', 'coefficient', 'ue_id', 'filiere_id', 'niveau_id', 'semestre']);

$nom = trim($data['nom']);
$code = trim($data['code'] ?? '');
$coefficient = (int)$data['coefficient'];
$credits = (int)($data['credits'] ?? 0);
$ueId = (int)$data['ue_id'];
$filiereId = (int)$data['filiere_id'];
$niveauId = (int)$data['niveau_id'];
$semestre = $data['semestre'];

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');
if ($coefficient <= 0) jsonErreur('Le coefficient doit être supérieur à 0');

$db = getDB();
$stmt = $db->prepare(
'INSERT INTO matieres (nom, code, coefficient, credits, ue_id, filiere_id, niveau_id, semestre)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$nom, $code, $coefficient, $credits, $ueId, $filiereId, $niveauId, $semestre]);
$matiereId = (int)$db->lastInsertId();

// Si un professeur + classe + année ont été fournis directement,
// on crée aussi l'attribution dans la foulée
if (!empty($data['professeur_id']) && !empty($data['classe_id'])) {
$anneeId = (int)($data['annee_id'] ?? 0);
if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}
if ($anneeId) {
$insAttr = $db->prepare(
'INSERT INTO attributions (professeur_id, matiere_id, classe_id, annee_academique_id)
VALUES (?, ?, ?, ?)'
);
$insAttr->execute([(int)$data['professeur_id'], $matiereId, (int)$data['classe_id'], $anneeId]);
}
}

jsonReponse(['succes' => true, 'id' => $matiereId], 201);
}

/* ============================================================
Attribuer un professeur à une matière pour une classe (admin)
============================================================ */
function attribuerProfesseur(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['professeur_id', 'matiere_id', 'classe_id']);

$anneeId = (int)($data['annee_id'] ?? 0);
if (!$anneeId) {
$db = getDB();
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}
if (!$anneeId) jsonErreur('Aucune année académique active');

$db = getDB();
$stmt = $db->prepare(
'INSERT INTO attributions (professeur_id, matiere_id, classe_id, annee_academique_id)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE professeur_id = VALUES(professeur_id)'
);
$stmt->execute([
(int)$data['professeur_id'],
(int)$data['matiere_id'],
(int)$data['classe_id'],
$anneeId,
]);

jsonReponse(['succes' => true]);
}
