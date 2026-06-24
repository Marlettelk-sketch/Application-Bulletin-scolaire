<?php
/* ============================================================
api/configuration.php
Gestion admin : filières, niveaux, options, matières, classes

--- FILIÈRES ---
GET ?action=filieres → liste toutes les filières
POST ?action=ajouter_filiere → ajouter une filière
POST ?action=modifier_filiere → modifier une filière
POST ?action=supprimer_filiere → supprimer une filière

--- OPTIONS ---
GET ?action=options &filiere_id=1 → options d'une filière
POST ?action=ajouter_option → ajouter une option
POST ?action=supprimer_option → supprimer une option

--- NIVEAUX ---
GET ?action=niveaux → liste tous les niveaux
POST ?action=ajouter_niveau → ajouter un niveau
POST ?action=supprimer_niveau → supprimer un niveau

--- MATIÈRES ---
GET ?action=matieres &filiere_id=1&niveau_id=1&semestre=S1
POST ?action=ajouter_matiere
POST ?action=modifier_matiere
POST ?action=supprimer_matiere

--- CLASSES ---
GET ?action=classes &annee_id=1
POST ?action=ajouter_classe
POST ?action=supprimer_classe

--- ANNÉES ACADÉMIQUES ---
GET ?action=annees
POST ?action=ajouter_annee
POST ?action=activer_annee → changer l'année active
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion('admin');

routerAction([
// Filières
'filieres' => 'listerFilieres',
'ajouter_filiere' => 'ajouterFiliere',
'modifier_filiere' => 'modifierFiliere',
'supprimer_filiere' => 'supprimerFiliere',
// Options
'options' => 'listerOptions',
'ajouter_option' => 'ajouterOption',
'supprimer_option' => 'supprimerOption',
// Niveaux
'niveaux' => 'listerNiveaux',
'ajouter_niveau' => 'ajouterNiveau',
'supprimer_niveau' => 'supprimerNiveau',
// Matières
'matieres' => 'listerMatieres',
'ajouter_matiere' => 'ajouterMatiere',
'modifier_matiere' => 'modifierMatiere',
'supprimer_matiere' => 'supprimerMatiere',
// Classes
'classes' => 'listerClasses',
'ajouter_classe' => 'ajouterClasse',
'supprimer_classe' => 'supprimerClasse',
// Années
'annees' => 'listerAnnees',
'ajouter_annee' => 'ajouterAnnee',
'activer_annee' => 'activerAnnee',
]);

/* ============================================================
FILIÈRES
============================================================ */
function listerFilieres(): void {
$db = getDB();
$rows = $db->query(
'SELECT f.*, COUNT(o.id) AS nb_options FROM filieres f
LEFT JOIN options_filiere o ON o.filiere_id = f.id
GROUP BY f.id ORDER BY f.nom'
)->fetchAll();
jsonReponse(['filieres' => $rows]);
}

function ajouterFiliere(): void {
$data = getJson();
requis($data, ['nom', 'code']);

$db = getDB();
$stmt = $db->prepare('INSERT INTO filieres (nom, code, description) VALUES (?, ?, ?)');
$stmt->execute([trim($data['nom']), strtoupper(trim($data['code'])), $data['description'] ?? null]);

jsonReponse(['succes' => true, 'message' => 'Filière ajoutée', 'id' => (int)$db->lastInsertId()]);
}

function modifierFiliere(): void {
$data = getJson();
requis($data, ['id', 'nom', 'code']);

$db = getDB();
$db->prepare('UPDATE filieres SET nom = ?, code = ?, description = ? WHERE id = ?')
->execute([trim($data['nom']), strtoupper(trim($data['code'])), $data['description'] ?? null, (int)$data['id']]);

jsonReponse(['succes' => true, 'message' => 'Filière modifiée']);
}

function supprimerFiliere(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM filieres WHERE id = ?');
$stmt->execute([$id]);
if ($stmt->rowCount() === 0) jsonErreur('Filière introuvable', 404);

jsonReponse(['succes' => true, 'message' => 'Filière supprimée']);
}

/* ============================================================
OPTIONS
============================================================ */
function listerOptions(): void {
$filiereId = (int)($_GET['filiere_id'] ?? 0);
$db = getDB();

if ($filiereId) {
$stmt = $db->prepare('SELECT * FROM options_filiere WHERE filiere_id = ? ORDER BY nom');
$stmt->execute([$filiereId]);
} else {
$stmt = $db->query(
'SELECT o.*, f.nom AS filiere FROM options_filiere o
JOIN filieres f ON o.filiere_id = f.id ORDER BY f.nom, o.nom'
);
}
jsonReponse(['options' => $stmt->fetchAll()]);
}

function ajouterOption(): void {
$data = getJson();
requis($data, ['nom', 'code', 'filiere_id']);

$db = getDB();
$stmt = $db->prepare('INSERT INTO options_filiere (nom, code, filiere_id) VALUES (?, ?, ?)');
$stmt->execute([trim($data['nom']), strtoupper(trim($data['code'])), (int)$data['filiere_id']]);

jsonReponse(['succes' => true, 'message' => 'Option ajoutée', 'id' => (int)$db->lastInsertId()]);
}

function supprimerOption(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM options_filiere WHERE id = ?');
$stmt->execute([$id]);

jsonReponse(['succes' => true, 'message' => 'Option supprimée']);
}

/* ============================================================
NIVEAUX
============================================================ */
function listerNiveaux(): void {
$rows = getDB()->query('SELECT * FROM niveaux ORDER BY ordre')->fetchAll();
jsonReponse(['niveaux' => $rows]);
}

function ajouterNiveau(): void {
$data = getJson();
requis($data, ['libelle']);

$db = getDB();
$max = $db->query('SELECT MAX(ordre) AS m FROM niveaux')->fetch();
$ordre = ($max['m'] ?? 0) + 1;

$stmt = $db->prepare('INSERT INTO niveaux (libelle, ordre) VALUES (?, ?)');
$stmt->execute([strtoupper(trim($data['libelle'])), $ordre]);

jsonReponse(['succes' => true, 'message' => 'Niveau ajouté', 'id' => (int)$db->lastInsertId()]);
}

function supprimerNiveau(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM niveaux WHERE id = ?');
$stmt->execute([$id]);

jsonReponse(['succes' => true, 'message' => 'Niveau supprimé']);
}

/* ============================================================
MATIÈRES
============================================================ */
function listerMatieres(): void {
$db = getDB();
$filiereId = (int)($_GET['filiere_id'] ?? 0);
$niveauId = (int)($_GET['niveau_id'] ?? 0);
$semestre = $_GET['semestre'] ?? '';

$where = []; $params = [];
if ($filiereId) { $where[] = 'm.filiere_id = ?'; $params[] = $filiereId; }
if ($niveauId) { $where[] = 'm.niveau_id = ?'; $params[] = $niveauId; }
if (in_array($semestre, ['S1','S2'])) { $where[] = 'm.semestre = ?'; $params[] = $semestre; }

$sql = 'SELECT m.*, f.nom AS filiere, n.libelle AS niveau
FROM matieres m
JOIN filieres f ON m.filiere_id = f.id
JOIN niveaux n ON m.niveau_id = n.id'
. ($where ? ' WHERE ' . implode(' AND ', $where) : '')
. ' ORDER BY f.nom, n.ordre, m.semestre, m.nom';

$stmt = $db->prepare($sql);
$stmt->execute($params);
jsonReponse(['matieres' => $stmt->fetchAll()]);
}

function ajouterMatiere(): void {
$data = getJson();
requis($data, ['nom', 'filiere_id', 'niveau_id', 'semestre', 'coefficient']);

if (!in_array($data['semestre'], ['S1','S2'])) jsonErreur('Semestre invalide');
if ((int)$data['coefficient'] < 1) jsonErreur('Le coefficient doit être ≥ 1');

$db = getDB();
$stmt = $db->prepare(
'INSERT INTO matieres (nom, code, coefficient, filiere_id, niveau_id, semestre)
VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
trim($data['nom']),
strtoupper(trim($data['code'] ?? '')),
(int)$data['coefficient'],
(int)$data['filiere_id'],
(int)$data['niveau_id'],
$data['semestre'],
]);

jsonReponse(['succes' => true, 'message' => 'Matière ajoutée', 'id' => (int)$db->lastInsertId()]);
}

function modifierMatiere(): void {
$data = getJson();
requis($data, ['id', 'nom', 'coefficient']);

$db = getDB();
$db->prepare('UPDATE matieres SET nom = ?, code = ?, coefficient = ? WHERE id = ?')
->execute([
trim($data['nom']),
strtoupper(trim($data['code'] ?? '')),
(int)$data['coefficient'],
(int)$data['id'],
]);

jsonReponse(['succes' => true, 'message' => 'Matière modifiée']);
}

function supprimerMatiere(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM matieres WHERE id = ?');
$stmt->execute([$id]);

jsonReponse(['succes' => true, 'message' => 'Matière supprimée']);
}

/* ============================================================
CLASSES
============================================================ */
function listerClasses(): void {
$db = getDB();
$anneeId = (int)($_GET['annee_id'] ?? 0);

if (!$anneeId) {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
}

$stmt = $db->prepare(
'SELECT c.id, f.nom AS filiere, nv.libelle AS niveau, o.nom AS option_nom,
aa.libelle AS annee_academique, c.effectif_max,
COUNT(e.id) AS nb_etudiants
FROM classes c
JOIN filieres f ON c.filiere_id = f.id
JOIN niveaux nv ON c.niveau_id = nv.id
LEFT JOIN options_filiere o ON c.option_id = o.id
JOIN annees_academiques aa ON c.annee_academique_id = aa.id
LEFT JOIN etudiants e ON e.classe_id = c.id
WHERE c.annee_academique_id = ?
GROUP BY c.id ORDER BY f.nom, nv.ordre'
);
$stmt->execute([$anneeId]);
jsonReponse(['classes' => $stmt->fetchAll()]);
}

function ajouterClasse(): void {
$data = getJson();
requis($data, ['filiere_id', 'niveau_id']);

$db = getDB();
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
if (!$anneeId) jsonErreur('Aucune année académique active');

$stmt = $db->prepare(
'INSERT INTO classes (filiere_id, niveau_id, option_id, annee_academique_id, effectif_max)
VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([
(int)$data['filiere_id'],
(int)$data['niveau_id'],
!empty($data['option_id']) ? (int)$data['option_id'] : null,
$anneeId,
(int)($data['effectif_max'] ?? 50),
]);

jsonReponse(['succes' => true, 'message' => 'Classe créée', 'id' => (int)$db->lastInsertId()]);
}

function supprimerClasse(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM classes WHERE id = ?');
$stmt->execute([$id]);

jsonReponse(['succes' => true, 'message' => 'Classe supprimée']);
}

/* ============================================================
ANNÉES ACADÉMIQUES
============================================================ */
function listerAnnees(): void {
$rows = getDB()->query('SELECT * FROM annees_academiques ORDER BY date_debut DESC')->fetchAll();
foreach ($rows as &$r) $r['actif'] = (bool)$r['actif'];
unset($r);
jsonReponse(['annees' => $rows]);
}

function ajouterAnnee(): void {
$data = getJson();
requis($data, ['libelle', 'date_debut', 'date_fin']);

$db = getDB();
$stmt = $db->prepare(
'INSERT INTO annees_academiques (libelle, date_debut, date_fin, actif) VALUES (?, ?, ?, 0)'
);
$stmt->execute([trim($data['libelle']), $data['date_debut'], $data['date_fin']]);

jsonReponse(['succes' => true, 'message' => 'Année académique ajoutée', 'id' => (int)$db->lastInsertId()]);
}

function activerAnnee(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$db->exec('UPDATE annees_academiques SET actif = 0'); // désactiver toutes
$db->prepare('UPDATE annees_academiques SET actif = 1 WHERE id = ?')->execute([$id]);

jsonReponse(['succes' => true, 'message' => 'Année académique activée']);
}
