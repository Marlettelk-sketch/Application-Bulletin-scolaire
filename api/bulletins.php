<?php
/* ============================================================
api/bulletins.php
Génération et consultation des bulletins

GET ?action=bulletin &semestre=S1&annee_id=1 → bulletin complet étudiant connecté
GET ?action=classe &classe_id=1&semestre=S1 → bulletins d'une classe (prof/admin)
GET ?action=disponibles → semestres disponibles pour l'étudiant
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'bulletin' => 'genererBulletin',
'classe' => 'bulletinsClasse',
'disponibles' => 'semestresDisponibles',
]);

/* ============================================================
Bulletin complet de l'étudiant connecté
============================================================ */
function genererBulletin(): void {
exigerConnexion('etudiant');

$etudiantId = getEtudiantId();
$semestre = $_GET['semestre'] ?? 'S1';
$anneeId = (int)($_GET['annee_id'] ?? 0);

if (!in_array($semestre, ['S1', 'S2'])) jsonErreur('Semestre invalide');

$db = getDB();

// Année active si non fournie
if (!$anneeId) {
$row = $db->query('SELECT id, libelle FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;
$anneeLibelle = $row['libelle'] ?? '';
} else {
$row = $db->prepare('SELECT libelle FROM annees_academiques WHERE id = ?');
$row->execute([$anneeId]);
$anneeLibelle = ($row->fetch())['libelle'] ?? '';
}

// Infos de l'étudiant
$stmt = $db->prepare(
'SELECT u.nom, u.prenom, u.email,
e.matricule, e.date_naissance, e.lieu_naissance, e.photo,
f.nom AS filiere,
n.libelle AS niveau,
o.nom AS option_nom,
aa.libelle AS annee_academique
FROM etudiants e
JOIN utilisateurs u ON e.utilisateur_id = u.id
JOIN classes c ON e.classe_id = c.id
JOIN filieres f ON c.filiere_id = f.id
JOIN niveaux nv ON c.niveau_id = nv.id
LEFT JOIN options_filiere o ON c.option_id = o.id
JOIN annees_academiques aa ON c.annee_academique_id = aa.id
WHERE e.id = ? LIMIT 1'
);
$stmt->execute([$etudiantId]);
$etudiant = $stmt->fetch();
if (!$etudiant) jsonErreur('Étudiant introuvable', 404);

// Notes du semestre
$stmtNotes = $db->prepare(
'SELECT m.nom AS matiere, m.coefficient,
n.note, n.observation,
CONCAT(u.prenom, " ", u.nom) AS professeur
FROM notes n
JOIN matieres m ON n.matiere_id = m.id
JOIN professeurs p ON n.professeur_id = p.id
JOIN utilisateurs u ON p.utilisateur_id = u.id
WHERE n.etudiant_id = ? AND n.semestre = ? AND n.annee_academique_id = ?
ORDER BY m.nom'
);
$stmtNotes->execute([$etudiantId, $semestre, $anneeId]);
$notes = $stmtNotes->fetchAll();

// Calcul de la moyenne pondérée
$totalCoef = 0; $totalPoints = 0;
foreach ($notes as &$n) {
$n['note'] = (float)$n['note'];
$n['coefficient'] = (int)$n['coefficient'];
if (!$n['observation']) $n['observation'] = getObservation($n['note']);
$totalCoef += $n['coefficient'];
$totalPoints += $n['note'] * $n['coefficient'];
}
unset($n);

$moyenneGenerale = $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null;
$mention = $moyenneGenerale !== null ? getObservation($moyenneGenerale) : null;

// Rang dans la classe
$rang = calculerRang($db, $etudiantId, $semestre, $anneeId);

// Statut de paiement (pour autoriser le téléchargement)
$stmtPai = $db->prepare(
'SELECT statut FROM paiements WHERE etudiant_id = ? AND annee_academique_id = ? LIMIT 1'
);
$stmtPai->execute([$etudiantId, $anneeId]);
$paiement = $stmtPai->fetch();
$paiementComplet = $paiement && $paiement['statut'] === 'complet';

jsonReponse([
'etudiant' => $etudiant,
'semestre' => $semestre,
'annee_academique' => $anneeLibelle,
'notes' => $notes,
'total_coef' => $totalCoef,
'moyenne_generale' => $moyenneGenerale,
'mention' => $mention,
'rang' => $rang,
'paiement_complet' => $paiementComplet,
]);
}

/* ============================================================
Calcul du rang dans la classe
============================================================ */
function calculerRang(PDO $db, int $etudiantId, string $semestre, int $anneeId): array {
// Récupérer la classe de l'étudiant
$stmt = $db->prepare('SELECT classe_id FROM etudiants WHERE id = ?');
$stmt->execute([$etudiantId]);
$et = $stmt->fetch();
if (!$et) return ['rang' => null, 'effectif' => null];

// Moyennes de tous les étudiants de la classe
$stmt2 = $db->prepare(
'SELECT e.id,
SUM(n.note * m.coefficient) / SUM(m.coefficient) AS moyenne
FROM etudiants e
JOIN notes n ON n.etudiant_id = e.id AND n.semestre = ? AND n.annee_academique_id = ?
JOIN matieres m ON n.matiere_id = m.id
WHERE e.classe_id = ?
GROUP BY e.id
ORDER BY moyenne DESC'
);
$stmt2->execute([$semestre, $anneeId, $et['classe_id']]);
$classement = $stmt2->fetchAll();

$rang = null;
$effectif = count($classement);
foreach ($classement as $i => $ligne) {
if ((int)$ligne['id'] === $etudiantId) {
$rang = $i + 1;
break;
}
}

return ['rang' => $rang, 'effectif' => $effectif];
}

/* ============================================================
Bulletins de toute une classe (prof / admin)
============================================================ */
function bulletinsClasse(): void {
exigerRoles(['admin', 'professeur']);

$classeId = (int)($_GET['classe_id'] ?? 0);
$semestre = $_GET['semestre'] ?? 'S1';

if (!$classeId) jsonErreur('classe_id requis');

$db = getDB();
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;

$stmt = $db->prepare(
'SELECT e.id AS etudiant_id, e.matricule,
CONCAT(u.prenom, " ", u.nom) AS etudiant,
SUM(n.note * m.coefficient) / SUM(m.coefficient) AS moyenne
FROM etudiants e
JOIN utilisateurs u ON e.utilisateur_id = u.id
LEFT JOIN notes n ON n.etudiant_id = e.id
AND n.semestre = ? AND n.annee_academique_id = ?
LEFT JOIN matieres m ON n.matiere_id = m.id
WHERE e.classe_id = ?
GROUP BY e.id, u.nom, u.prenom, e.matricule
ORDER BY moyenne DESC'
);
$stmt->execute([$semestre, $anneeId, $classeId]);
$liste = $stmt->fetchAll();

foreach ($liste as &$l) {
$l['moyenne'] = $l['moyenne'] !== null ? round((float)$l['moyenne'], 2) : null;
$l['mention'] = $l['moyenne'] !== null ? getObservation($l['moyenne']) : null;
}
unset($l);

jsonReponse(['classe_id' => $classeId, 'semestre' => $semestre, 'bulletins' => $liste]);
}

/* ============================================================
Semestres avec des notes disponibles pour l'étudiant connecté
============================================================ */
function semestresDisponibles(): void {
exigerConnexion('etudiant');

$db = getDB();
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
$anneeId = $row ? (int)$row['id'] : 0;

$stmt = $db->prepare(
'SELECT DISTINCT semestre, COUNT(*) AS nb_notes
FROM notes WHERE etudiant_id = ? AND annee_academique_id = ?
GROUP BY semestre ORDER BY semestre'
);
$stmt->execute([getEtudiantId(), $anneeId]);

jsonReponse(['disponibles' => $stmt->fetchAll()]);
}

