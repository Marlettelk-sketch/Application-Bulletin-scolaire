<?php
/* ============================================================
api/notifications.php
Gestion des notifications

GET ?action=liste → notifications de l'utilisateur connecté
GET ?action=non_lues → nombre de notifs non lues
POST ?action=lire {id} → marquer une notif comme lue
POST ?action=tout_lire → tout marquer comme lu
POST ?action=envoyer → envoyer une notif (admin)
POST ?action=supprimer {id} → supprimer une notif
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'liste' => 'listeNotifications',
'non_lues' => 'compterNonLues',
'lire' => 'marquerLue',
'tout_lire' => 'toutMarquerLu',
'envoyer' => 'envoyerNotification',
'supprimer' => 'supprimerNotification',
]);

/* ============================================================
Liste des notifications de l'utilisateur connecté
============================================================ */
function listeNotifications(): void {
$db = getDB();
$userId = getUtilisateurId();
$limite = (int)($_GET['limite'] ?? 20);

$stmt = $db->prepare(
'SELECT id, titre, message, lu, type, created_at
FROM notifications
WHERE utilisateur_id = ?
ORDER BY created_at DESC
LIMIT ?'
);
$stmt->execute([$userId, $limite]);
$notifs = $stmt->fetchAll();

foreach ($notifs as &$n) {
$n['lu'] = (bool)$n['lu'];
}
unset($n);

jsonReponse(['notifications' => $notifs]);
}

/* ============================================================
Compter les notifications non lues
============================================================ */
function compterNonLues(): void {
$db = getDB();
$stmt = $db->prepare('SELECT COUNT(*) AS nb FROM notifications WHERE utilisateur_id = ? AND lu = 0');
$stmt->execute([getUtilisateurId()]);
$row = $stmt->fetch();

jsonReponse(['non_lues' => (int)$row['nb']]);
}

/* ============================================================
Marquer une notification comme lue
============================================================ */
function marquerLue(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare(
'UPDATE notifications SET lu = 1 WHERE id = ? AND utilisateur_id = ?'
);
$stmt->execute([$id, getUtilisateurId()]);

jsonReponse(['succes' => true]);
}

/* ============================================================
Tout marquer comme lu
============================================================ */
function toutMarquerLu(): void {
$db = getDB();
$stmt = $db->prepare('UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?');
$stmt->execute([getUtilisateurId()]);

jsonReponse(['succes' => true, 'mises_a_jour' => $stmt->rowCount()]);
}

/* ============================================================
Envoyer une notification (admin)
============================================================ */
function envoyerNotification(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['titre', 'cible']);

$titre = trim($data['titre']);
$message = trim($data['message'] ?? '');
$type = $data['type'] ?? 'general';
$cible = $data['cible']; // 'tous' | 'etudiant' | 'professeur' | utilisateur_id (int)

$db = getDB();

if ($cible === 'tous') {
// Envoyer à tous les utilisateurs actifs
$users = $db->query('SELECT id FROM utilisateurs WHERE statut = "actif"')->fetchAll();
} elseif (in_array($cible, ['etudiant', 'professeur', 'admin'])) {
// Envoyer à tous les membres d'un rôle
$stmt = $db->prepare('SELECT id FROM utilisateurs WHERE role = ? AND statut = "actif"');
$stmt->execute([$cible]);
$users = $stmt->fetchAll();
} else {
// Envoyer à un utilisateur précis
$users = [['id' => (int)$cible]];
}

if (!$users) jsonErreur('Aucun destinataire trouvé');

$insert = $db->prepare(
'INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, ?)'
);
$db->beginTransaction();
foreach ($users as $u) {
$insert->execute([$u['id'], $titre, $message, $type]);
}
$db->commit();

jsonReponse([
'succes' => true,
'message' => 'Notification envoyée',
'destinataires'=> count($users),
]);
}

/* ============================================================
Supprimer une notification
============================================================ */
function supprimerNotification(): void {
$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare(
'DELETE FROM notifications WHERE id = ? AND utilisateur_id = ?'
);
$stmt->execute([$id, getUtilisateurId()]);

if ($stmt->rowCount() === 0) jsonErreur('Notification introuvable', 404);

jsonReponse(['succes' => true]);
}

