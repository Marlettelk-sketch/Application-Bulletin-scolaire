<?php
/* ============================================================
api/messagerie.php
Messagerie entre étudiant et administration / professeur

GET ?action=conversations
→ liste des conversations de l'utilisateur connecté

GET ?action=messages &conversation_id=1
→ messages d'une conversation (et les marque comme lus)

POST ?action=nouvelle_conversation {destinataire_id, sujet, message}
→ l'étudiant initie une demande (vers un admin ou un professeur)

POST ?action=envoyer {conversation_id, contenu}
→ répondre dans une conversation existante
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'conversations' => 'listeConversations',
'messages' => 'listeMessages',
'nouvelle_conversation' => 'nouvelleConversation',
'envoyer' => 'envoyerMessage',
]);

/* ============================================================
Liste des conversations de l'utilisateur connecté, avec aperçu
du dernier message et nombre de messages non lus
============================================================ */
function listeConversations(): void {
$db = getDB();
$userId = getUtilisateurId();

$stmt = $db->prepare(
'SELECT c.id, c.sujet, c.created_at,
CASE WHEN c.utilisateur1_id = ? THEN c.utilisateur2_id ELSE c.utilisateur1_id END AS autre_id,
CONCAT(u.prenom, " ", u.nom) AS autre_nom, u.role AS autre_role
FROM conversations c
JOIN utilisateurs u ON u.id = CASE WHEN c.utilisateur1_id = ? THEN c.utilisateur2_id ELSE c.utilisateur1_id END
WHERE c.utilisateur1_id = ? OR c.utilisateur2_id = ?
ORDER BY c.created_at DESC'
);
$stmt->execute([$userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();

$stmtDernier = $db->prepare(
'SELECT contenu, lu, expediteur_id, created_at
FROM messages WHERE conversation_id = ?
ORDER BY created_at DESC LIMIT 1'
);
$stmtNonLus = $db->prepare(
'SELECT COUNT(*) AS nb FROM messages
WHERE conversation_id = ? AND lu = 0 AND expediteur_id != ?'
);

foreach ($conversations as &$conv) {
$stmtDernier->execute([$conv['id']]);
$dernier = $stmtDernier->fetch();
$conv['dernier_message'] = $dernier['contenu'] ?? null;
$conv['dernier_message_de_moi'] = $dernier ? ((int)$dernier['expediteur_id'] === $userId) : null;

$stmtNonLus->execute([$conv['id'], $userId]);
$conv['non_lus'] = (int)$stmtNonLus->fetch()['nb'];
}
unset($conv);

jsonReponse(['conversations' => $conversations]);
}

/* ============================================================
Messages d'une conversation — marque aussi les messages reçus
comme lus au passage
============================================================ */
function listeMessages(): void {
$db = getDB();
$userId = getUtilisateurId();
$conversationId = (int)($_GET['conversation_id'] ?? 0);
if (!$conversationId) jsonErreur('conversation_id requis');

// Vérifier que l'utilisateur fait bien partie de cette conversation
$check = $db->prepare(
'SELECT id FROM conversations WHERE id = ? AND (utilisateur1_id = ? OR utilisateur2_id = ?)'
);
$check->execute([$conversationId, $userId, $userId]);
if (!$check->fetch()) jsonErreur('Conversation introuvable', 404);

// Marquer comme lus les messages reçus (pas envoyés par moi)
$marquer = $db->prepare(
'UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ?'
);
$marquer->execute([$conversationId, $userId]);

$stmt = $db->prepare(
'SELECT m.id, m.contenu, m.lu, m.created_at, m.expediteur_id,
(m.expediteur_id = ?) AS de_moi
FROM messages m
WHERE m.conversation_id = ?
ORDER BY m.created_at ASC'
);
$stmt->execute([$userId, $conversationId]);
$messages = $stmt->fetchAll();

foreach ($messages as &$m) {
$m['de_moi'] = (bool)$m['de_moi'];
$m['lu'] = (bool)$m['lu'];
}
unset($m);

jsonReponse(['messages' => $messages]);
}

/* ============================================================
Initier une nouvelle conversation (étudiant → admin ou professeur)
============================================================ */
function nouvelleConversation(): void {
exigerConnexion('etudiant');

$data = getJson();
requis($data, ['destinataire_id', 'message']);

$userId = getUtilisateurId();
$destinataireId = (int)$data['destinataire_id'];
$sujet = trim($data['sujet'] ?? '');
$contenu = trim($data['message']);

if ($destinataireId === $userId) jsonErreur('Destinataire invalide');

$db = getDB();

// Le destinataire doit être un admin ou un professeur
$dest = $db->prepare('SELECT role FROM utilisateurs WHERE id = ?');
$dest->execute([$destinataireId]);
$destRole = $dest->fetch()['role'] ?? null;
if (!in_array($destRole, ['admin', 'professeur'])) jsonErreur('Destinataire invalide');

// Réutiliser une conversation existante avec ce destinataire si elle existe déjà
$existe = $db->prepare(
'SELECT id FROM conversations
WHERE (utilisateur1_id = ? AND utilisateur2_id = ?) OR (utilisateur1_id = ? AND utilisateur2_id = ?)'
);
$existe->execute([$userId, $destinataireId, $destinataireId, $userId]);
$conv = $existe->fetch();

if ($conv) {
$conversationId = (int)$conv['id'];
} else {
$insConv = $db->prepare(
'INSERT INTO conversations (utilisateur1_id, utilisateur2_id, sujet) VALUES (?, ?, ?)'
);
$insConv->execute([$userId, $destinataireId, $sujet]);
$conversationId = (int)$db->lastInsertId();
}

$insMsg = $db->prepare(
'INSERT INTO messages (conversation_id, expediteur_id, contenu) VALUES (?, ?, ?)'
);
$insMsg->execute([$conversationId, $userId, $contenu]);

// Notifier le destinataire
$insNotif = $db->prepare(
'INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, "admin")'
);
$insNotif->execute([$destinataireId, 'Nouveau message', $contenu]);

jsonReponse(['succes' => true, 'conversation_id' => $conversationId], 201);
}

/* ============================================================
Répondre dans une conversation existante
============================================================ */
function envoyerMessage(): void {
$data = getJson();
requis($data, ['conversation_id', 'contenu']);

$userId = getUtilisateurId();
$conversationId = (int)$data['conversation_id'];
$contenu = trim($data['contenu']);

$db = getDB();

// Vérifier l'appartenance à la conversation + trouver l'autre personne
$check = $db->prepare(
'SELECT utilisateur1_id, utilisateur2_id FROM conversations WHERE id = ?'
);
$check->execute([$conversationId]);
$conv = $check->fetch();

if (!$conv || ($conv['utilisateur1_id'] != $userId && $conv['utilisateur2_id'] != $userId)) {
jsonErreur('Conversation introuvable', 404);
}

$autreId = ($conv['utilisateur1_id'] == $userId) ? $conv['utilisateur2_id'] : $conv['utilisateur1_id'];

$insMsg = $db->prepare(
'INSERT INTO messages (conversation_id, expediteur_id, contenu) VALUES (?, ?, ?)'
);
$insMsg->execute([$conversationId, $userId, $contenu]);

$insNotif = $db->prepare(
'INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, "admin")'
);
$insNotif->execute([$autreId, 'Nouveau message', $contenu]);

jsonReponse(['succes' => true, 'id' => (int)$db->lastInsertId()], 201);
}
