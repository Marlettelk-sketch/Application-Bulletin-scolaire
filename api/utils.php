<?php
/* ============================================================
api/utils.php
Fonctions utilitaires partagées par toutes les APIs
============================================================ */

/** Envoyer une réponse JSON avec code HTTP */
function jsonReponse(array $data, int $code = 200): void {
http_response_code($code);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
}

/** Envoyer une erreur JSON */
function jsonErreur(string $msg, int $code = 400): void {
jsonReponse(['erreur' => $msg], $code);
}

/** Lire le corps JSON de la requête */
function getJson(): array {
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
return is_array($data) ? $data : [];
}

/** Router les actions GET/POST vers les bonnes fonctions */
function routerAction(array $routes): void {
$action = trim($_GET['action'] ?? '');
if (isset($routes[$action])) {
call_user_func($routes[$action]);
} else {
jsonErreur('Action inconnue : ' . htmlspecialchars($action));
}
}

/** Retourner une observation selon la note */
function getObservation(float $note): string {
if ($note >= 16) return 'Très bien';
if ($note >= 14) return 'Bien';
if ($note >= 12) return 'Assez-bien';
if ($note >= 10) return 'Passable';
return 'Insuffisant';
}

/** Valider qu'un champ obligatoire existe */
function requis(array $data, array $champs): void {
foreach ($champs as $c) {
if (!isset($data[$c]) || trim((string)$data[$c]) === '') {
jsonErreur("Le champ « $c » est obligatoire");
}
}
}

