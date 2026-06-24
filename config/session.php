<?php
/* ============================================================
config/session.php
Gestion des sessions et contrôle d'accès par rôle
============================================================ */

if (session_status() === PHP_SESSION_NONE) {
session_name('SCOLARIS_SESS');
session_set_cookie_params([
'lifetime' => 0,
'path' => '/',
'secure' => false, // passer à true en HTTPS (production)
'httponly' => true,
'samesite' => 'Strict'
]);
session_start();
}

/* ---------- Vérifications ---------- */

function estConnecte(): bool {
return !empty($_SESSION['utilisateur_id']);
}

function getRole(): ?string {
return $_SESSION['role'] ?? null;
}

function getUtilisateurId(): ?int {
return $_SESSION['utilisateur_id'] ?? null;
}

function getEtudiantId(): ?int {
return $_SESSION['etudiant_id'] ?? null;
}

function getProfesseurId(): ?int {
return $_SESSION['professeur_id'] ?? null;
}

/* ---------- Protection des routes ---------- */

/**
* Exiger une connexion + optionnellement un rôle précis.
* Si non connecté → réponse JSON 401.
* Si mauvais rôle → réponse JSON 403.
*/
function exigerConnexion(?string $role = null): void {
if (!estConnecte()) {
http_response_code(401);
header('Content-Type: application/json');
die(json_encode(['erreur' => 'Non connecté', 'redirection' => 'connexion.html']));
}

if ($role !== null && getRole() !== $role) {
http_response_code(403);
header('Content-Type: application/json');
die(json_encode(['erreur' => 'Accès refusé — rôle insuffisant']));
}
}

/**
* Exiger que le rôle soit l'un des rôles autorisés.
* Ex: exigerRoles(['admin', 'professeur'])
*/
function exigerRoles(array $roles): void {
if (!estConnecte()) {
http_response_code(401);
header('Content-Type: application/json');
die(json_encode(['erreur' => 'Non connecté']));
}

if (!in_array(getRole(), $roles, true)) {
http_response_code(403);
header('Content-Type: application/json');
die(json_encode(['erreur' => 'Accès refusé']));
}
}

/* ---------- Déconnexion ---------- */

function deconnecter(): void {
$_SESSION = [];
if (ini_get('session.use_cookies')) {
$p = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
$p['path'], $p['domain'], $p['secure'], $p['httponly']
);
}
session_destroy();
}

