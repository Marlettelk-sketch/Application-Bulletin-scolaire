<?php
/* ============================================================
api/notes.php
Gestion des notes

GET ?action=mes_notes &semestre=S1&annee_id=1 → notes de l'étudiant connecté
GET ?action=moyennes &semestre=S1&annee_id=1 → moyennes pondérées par semestre
GET ?action=classe &classe_id=1&matiere_id=1&semestre=S1&annee_id=1
→ notes d'une matière (prof/admin)
POST ?action=ajouter → saisir / modifier une note (prof/admin)
POST ?action=supprimer → supprimer une note (admin)
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'mes_notes' => 'mesNotes',
'moyennes' => 'moyennes',
'classe' => 'notesClasse',
'ajouter' => 'ajouterNote',
'supprimer' => 'supprimerNote',
]);

/* ============================================================
Notes de l'étudiant connecté
============================================================ */
function mesNotes(): void {
exigerConnexion('etudiant');

$etudiantId = getEtudiantId();
$semestre = $_GET['semestre'] ?? 'S1';
$anneeId = (int)($_GET['annee_id'] ?? 0);

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');

$db = getDB();

// Récupérer l'année active si non fournie
if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}

$stmt = $db->prepare(
'SELECT n.id, n.note, n.observation, n.updated_at,
m.nom AS matiere, m.coefficient,
CONCAT(u.prenom, " ", u.nom) AS professeur
FROM notes n
JOIN matieres m ON n.matiere_id = m.id
JOIN professeurs p ON n.professeur_id = p.id
JOIN utilisateurs u ON p.utilisateur_id = u.id
WHERE n.etudiant_id = ? AND n.semestre = ? AND n.annee_academique_id = ?
ORDER BY m.nom'
);
$stmt->execute([$etudiantId, $semestre, $anneeId]);
$notes = $stmt->fetchAll();

// Calculer la moyenne pondérée
$sommeCoef = 0; $sommeNotes = 0;
foreach ($notes as &$n) {
$n['note'] = (float)$n['note'];
$n['coefficient'] = (int)$n['coefficient'];
if (!$n['observation']) $n['observation'] = getObservation($n['note']);
$sommeCoef += $n['coefficient'];
$sommeNotes += $n['note'] * $n['coefficient'];
}
unset($n);

$moyenne = $sommeCoef > 0 ? round($sommeNotes / $sommeCoef, 2) : null;

jsonReponse([
'semestre' => $semestre,
'notes' => $notes,
'moyenne' => $moyenne,
'mention' => $moyenne !== null ? getObservation($moyenne) : null,
]);
}

/* ============================================================
Moyennes par semestre (les deux semestres)
============================================================ */
function moyennes(): void {
exigerConnexion('etudiant');

$etudiantId = getEtudiantId();
$db = getDB();

$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;

$resultats = [];
foreach (['S1', 'S2'] as $sem) {
$stmt = $db->prepare(
'SELECT SUM(n.note * m.coefficient) / SUM(m.coefficient) AS moyenne,
SUM(m.coefficient) AS total_coef,
COUNT(n.id) AS nb_notes
FROM notes n
JOIN matieres m ON n.matiere_id = m.id
WHERE n.etudiant_id = ? AND n.semestre = ? AND n.annee_academique_id = ?'
);
$stmt->execute([$etudiantId, $sem, $anneeId]);
$r = $stmt->fetch();

$moy = $r['moyenne'] !== null ? round((float)$r['moyenne'], 2) : null;
$resultats[$sem] = [
'moyenne' => $moy,
'mention' => $moy !== null ? getObservation($moy) : null,
'nb_notes' => (int)$r['nb_notes'],
'total_coef' => (int)$r['total_coef'],
];
}

// Moyenne annuelle
$moyS1 = $resultats['S1']['moyenne'];
$moyS2 = $resultats['S2']['moyenne'];
$annuelle = ($moyS1 !== null && $moyS2 !== null)
? round(($moyS1 + $moyS2) / 2, 2)
: null;

jsonReponse([
'S1' => $resultats['S1'],
'S2' => $resultats['S2'],
'annuelle' => $annuelle,
'mention_annuelle' => $annuelle ? getObservation($annuelle) : null,
]);
}

/* ============================================================
Notes d'une matière pour toute une classe (prof / admin)
============================================================ */
function notesClasse(): void {
exigerRoles(['admin', 'professeur']);

$classeId = (int)($_GET['classe_id'] ?? 0);
$matiereId = (int)($_GET['matiere_id'] ?? 0);
$semestre = $_GET['semestre'] ?? 'S1';
$anneeId = (int)($_GET['annee_id'] ?? 0);

if (!$classeId || !$matiereId) jsonErreur('classe_id et matiere_id requis');

$db = getDB();
if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}

$stmt = $db->prepare(
'SELECT e.id AS etudiant_id, e.matricule,
CONCAT(u.prenom, " ", u.nom) AS etudiant,
n.id AS note_id, n.note, n.observation
FROM etudiants e
JOIN utilisateurs u ON e.utilisateur_id = u.id
LEFT JOIN notes n ON n.etudiant_id = e.id
AND n.matiere_id = ? AND n.semestre = ? AND n.annee_academique_id = ?
WHERE e.classe_id = ?
ORDER BY u.nom, u.prenom'
);
$stmt->execute([$matiereId, $semestre, $anneeId, $classeId]);

jsonReponse(['notes' => $stmt->fetchAll()]);
}

/* ============================================================
Ajouter / modifier une note (prof ou admin)
============================================================ */
function ajouterNote(): void {
exigerRoles(['admin', 'professeur']);

$data = getJson();
requis($data, ['etudiant_id', 'matiere_id', 'semestre', 'note']);

$etudiantId = (int)$data['etudiant_id'];
$matiereId = (int)$data['matiere_id'];
$semestre = $data['semestre'];
$note = (float)$data['note'];
$observation = $data['observation'] ?? getObservation($note);

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');
if ($note < 0 || $note > 20) jsonErreur('La note doit être entre 0 et 20');

$db = getDB();

// Récupérer l'année active
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
if (!$anneeId) jsonErreur('Aucune année académique active');

// Trouver l'id du professeur (s'il est prof)
$profId = null;
if (getRole() === 'professeur') {
$profId = getProfesseurId();
} else {
// Admin peut spécifier le prof, ou mettre NULL
$profId = !empty($data['professeur_id']) ? (int)$data['professeur_id'] : null;
}

// Upsert (INSERT ou UPDATE si déjà existante)
$stmt = $db->prepare(
'INSERT INTO notes (etudiant_id, matiere_id, professeur_id, annee_academique_id,
semestre, note, observation)
VALUES (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
note = VALUES(note),
observation = VALUES(observation),
professeur_id = VALUES(professeur_id)'
);
$stmt->execute([$etudiantId, $matiereId, $profId, $anneeId, $semestre, $note, $observation]);

// Notifier l'étudiant
$etudiant = $db->prepare('SELECT utilisateur_id FROM etudiants WHERE id = ?');
$etudiant->execute([$etudiantId]);
$et = $etudiant->fetch();
$matiere = $db->prepare('SELECT nom FROM matieres WHERE id = ?');
$matiere->execute([$matiereId]);
$mat = $matiere->fetch();

if ($et && $mat) {
$notif = $db->prepare(
'INSERT INTO notifications (utilisateur_id, titre, message, type)
VALUES (?, ?, ?, "note")'
);
$notif->execute([
$et['utilisateur_id'],
'Nouvelle note disponible',
'Votre note en ' . $mat['nom'] . ' (' . $semestre . ') : ' . $note . '/20'
]);
}

jsonReponse(['succes' => true, 'message' => 'Note enregistrée avec succès']);
}

/* ============================================================
Supprimer une note (admin uniquement)
============================================================ */
function supprimerNote(): void {
exigerConnexion('admin');

$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id de la note requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM notes WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) jsonErreur('Note introuvable', 404);

jsonReponse(['succes' => true, 'message' => 'Note supprimée']);
}

