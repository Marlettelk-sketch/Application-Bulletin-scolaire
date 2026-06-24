<?php
/* ============================================================
api/paiements.php
Gestion des paiements de scolarité

GET ?action=mon_statut → statut de l'étudiant connecté
GET ?action=verifier &etudiant_id=1 → paiement complet ? (pour le download)
GET ?action=liste &annee_id=1 → tous les paiements (admin)
POST ?action=initialiser → créer la fiche de paiement d'un étudiant (admin)
POST ?action=versement → enregistrer un versement (admin)
GET ?action=historique&etudiant_id=1 → historique des versements (admin)
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'mon_statut' => 'monStatut',
'verifier' => 'verifierPaiement',
'liste' => 'listePaiements',
'initialiser' => 'initialiserPaiement',
'versement' => 'enregistrerVersement',
'historique' => 'historique',
]);

/* ============================================================
Statut du paiement de l'étudiant connecté
============================================================ */
function monStatut(): void {
exigerConnexion('etudiant');

$db = getDB();
$anneeId = anneeActive($db);

$stmt = $db->prepare(
'SELECT p.id, p.montant_total, p.montant_paye, p.statut, p.date_dernier_paiement,
(p.montant_total - p.montant_paye) AS solde_restant
FROM paiements p
WHERE p.etudiant_id = ? AND p.annee_academique_id = ? LIMIT 1'
);
$stmt->execute([getEtudiantId(), $anneeId]);
$paiement = $stmt->fetch();

if (!$paiement) {
jsonReponse([
'statut' => 'non_initialise',
'paiement_complet' => false,
'message' => 'Votre fiche de paiement n\'a pas encore été créée. Contactez l\'administration.'
]);
return;
}

jsonReponse([
'statut' => $paiement['statut'],
'montant_total' => (float)$paiement['montant_total'],
'montant_paye' => (float)$paiement['montant_paye'],
'solde_restant' => (float)$paiement['solde_restant'],
'date_paiement' => $paiement['date_dernier_paiement'],
'paiement_complet' => $paiement['statut'] === 'complet',
]);
}

/* ============================================================
Vérifier si le paiement est complet (appelé avant téléchargement)
============================================================ */
function verifierPaiement(): void {
exigerConnexion();

// L'étudiant peut vérifier son propre statut, l'admin peut vérifier n'importe lequel
if (getRole() === 'etudiant') {
$etudiantId = getEtudiantId();
} else {
exigerRoles(['admin']);
$etudiantId = (int)($_GET['etudiant_id'] ?? 0);
if (!$etudiantId) jsonErreur('etudiant_id requis');
}

$db = getDB();
$anneeId = anneeActive($db);

$stmt = $db->prepare(
'SELECT statut, montant_total, montant_paye FROM paiements
WHERE etudiant_id = ? AND annee_academique_id = ? LIMIT 1'
);
$stmt->execute([$etudiantId, $anneeId]);
$p = $stmt->fetch();

$complet = $p && $p['statut'] === 'complet';

jsonReponse([
'paiement_complet' => $complet,
'statut' => $p['statut'] ?? 'non_initialise',
'solde_restant' => $p ? (float)$p['montant_total'] - (float)$p['montant_paye'] : null,
]);
}

/* ============================================================
Liste de tous les paiements (admin)
============================================================ */
function listePaiements(): void {
exigerConnexion('admin');

$db = getDB();
$anneeId = (int)($_GET['annee_id'] ?? anneeActive($db));
$statut = $_GET['statut'] ?? '';

$where = 'WHERE p.annee_academique_id = ?';
$params = [$anneeId];

if (in_array($statut, ['non_paye', 'partiel', 'complet'])) {
$where .= ' AND p.statut = ?';
$params[] = $statut;
}

$stmt = $db->prepare(
"SELECT p.id, p.montant_total, p.montant_paye, p.statut, p.date_dernier_paiement,
(p.montant_total - p.montant_paye) AS solde_restant,
e.matricule,
CONCAT(u.prenom, ' ', u.nom) AS etudiant,
f.nom AS filiere, nv.libelle AS niveau
FROM paiements p
JOIN etudiants e ON p.etudiant_id = e.id
JOIN utilisateurs u ON e.utilisateur_id = u.id
JOIN classes c ON e.classe_id = c.id
JOIN filieres f ON c.filiere_id = f.id
JOIN niveaux nv ON c.niveau_id = nv.id
$where
ORDER BY p.statut, u.nom"
);
$stmt->execute($params);
$liste = $stmt->fetchAll();

foreach ($liste as &$l) {
$l['montant_total'] = (float)$l['montant_total'];
$l['montant_paye'] = (float)$l['montant_paye'];
$l['solde_restant'] = (float)$l['solde_restant'];
}
unset($l);

jsonReponse(['paiements' => $liste]);
}

/* ============================================================
Créer la fiche de paiement d'un étudiant (admin)
============================================================ */
function initialiserPaiement(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['etudiant_id', 'montant_total']);

$etudiantId = (int)$data['etudiant_id'];
$montantTotal = (float)$data['montant_total'];

if ($montantTotal <= 0) jsonErreur('Le montant total doit être positif');

$db = getDB();
$anneeId = anneeActive($db);

// Vérifier si déjà initialisé
$check = $db->prepare('SELECT id FROM paiements WHERE etudiant_id = ? AND annee_academique_id = ?');
$check->execute([$etudiantId, $anneeId]);
if ($check->fetch()) jsonErreur('La fiche de paiement existe déjà pour cet étudiant cette année');

$stmt = $db->prepare(
'INSERT INTO paiements (etudiant_id, annee_academique_id, montant_total, montant_paye, statut)
VALUES (?, ?, ?, 0.00, "non_paye")'
);
$stmt->execute([$etudiantId, $anneeId, $montantTotal]);

jsonReponse(['succes' => true, 'message' => 'Fiche de paiement créée']);
}

/* ============================================================
Enregistrer un versement (admin)
============================================================ */
function enregistrerVersement(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['etudiant_id', 'montant', 'date_versement']);

$etudiantId = (int)$data['etudiant_id'];
$montant = (float)$data['montant'];
$dateVersement = $data['date_versement'];
$mode = $data['mode'] ?? 'especes';
$reference = $data['reference'] ?? null;
$note = $data['note'] ?? null;

if ($montant <= 0) jsonErreur('Le montant doit être positif');

$db = getDB();
$anneeId = anneeActive($db);

// Récupérer la fiche de paiement
$stmt = $db->prepare(
'SELECT id, montant_total, montant_paye FROM paiements
WHERE etudiant_id = ? AND annee_academique_id = ? LIMIT 1'
);
$stmt->execute([$etudiantId, $anneeId]);
$paiement = $stmt->fetch();
if (!$paiement) jsonErreur('Fiche de paiement introuvable. Initialisez-la d\'abord.', 404);

$nouveauMontantPaye = (float)$paiement['montant_paye'] + $montant;
$montantTotal = (float)$paiement['montant_total'];

// Sécurité : ne pas dépasser le montant total
if ($nouveauMontantPaye > $montantTotal) {
jsonErreur(sprintf(
'Ce versement dépasse le montant total (%.2f FCFA restants)',
$montantTotal - (float)$paiement['montant_paye']
));
}

// Déterminer le nouveau statut
$nouveauStatut = 'partiel';
if ($nouveauMontantPaye >= $montantTotal) $nouveauStatut = 'complet';
if ($nouveauMontantPaye <= 0) $nouveauStatut = 'non_paye';

// Enregistrer le versement
$insVersement = $db->prepare(
'INSERT INTO versements (paiement_id, montant, date_versement, mode, reference, note)
VALUES (?, ?, ?, ?, ?, ?)'
);
$insVersement->execute([$paiement['id'], $montant, $dateVersement, $mode, $reference, $note]);

// Mettre à jour la fiche
$upd = $db->prepare(
'UPDATE paiements SET montant_paye = ?, statut = ?, date_dernier_paiement = ?
WHERE id = ?'
);
$upd->execute([$nouveauMontantPaye, $nouveauStatut, $dateVersement, $paiement['id']]);

// Notifier l'étudiant
$etudiant = $db->prepare('SELECT utilisateur_id FROM etudiants WHERE id = ?');
$etudiant->execute([$etudiantId]);
$et = $etudiant->fetch();
if ($et) {
$notif = $db->prepare(
'INSERT INTO notifications (utilisateur_id, titre, message, type)
VALUES (?, ?, ?, "paiement")'
);
$notif->execute([
$et['utilisateur_id'],
'Versement reçu',
sprintf('Versement de %s FCFA enregistré. Nouveau solde : %s FCFA réglé sur %s FCFA.',
number_format($montant, 0, ',', ' '),
number_format($nouveauMontantPaye, 0, ',', ' '),
number_format($montantTotal, 0, ',', ' ')
)
]);
}

jsonReponse([
'succes' => true,
'message' => 'Versement enregistré',
'nouveau_statut' => $nouveauStatut,
'montant_paye' => $nouveauMontantPaye,
'solde_restant' => $montantTotal - $nouveauMontantPaye,
]);
}

/* ============================================================
Historique des versements (admin)
============================================================ */
function historique(): void {
exigerRoles(['admin']);

$etudiantId = (int)($_GET['etudiant_id'] ?? 0);
if (!$etudiantId) jsonErreur('etudiant_id requis');

$db = getDB();
$anneeId = anneeActive($db);

$stmt = $db->prepare(
'SELECT v.id, v.montant, v.date_versement, v.mode, v.reference, v.note, v.created_at
FROM versements v
JOIN paiements p ON v.paiement_id = p.id
WHERE p.etudiant_id = ? AND p.annee_academique_id = ?
ORDER BY v.date_versement DESC'
);
$stmt->execute([$etudiantId, $anneeId]);

jsonReponse(['versements' => $stmt->fetchAll()]);
}

/* ============================================================
Utilitaire : ID de l'année académique active
============================================================ */
function anneeActive(PDO $db): int {
$row = $db->query('SELECT id FROM annees_academiques WHERE actif = 1 LIMIT 1')->fetch();
if (!$row) jsonErreur('Aucune année académique active', 500);
return (int)$row['id'];
}

