<?php
/* ============================================================
config/database.php
Connexion PDO à la base de données Scolaris (WAMP)
============================================================ */

define('DB_HOST', 'localhost');
define('DB_NAME', 'scolaris');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mot de passe WAMP (vide par défaut)
define('DB_CHARSET', 'utf8mb4');

/**
* Retourne l'instance PDO (singleton).
* Toujours utiliser getDB() pour obtenir la connexion.
*/
function getDB(): PDO {
static $pdo = null;

if ($pdo === null) {
try {
$dsn = sprintf(
'mysql:host=%s;dbname=%s;charset=%s',
DB_HOST, DB_NAME, DB_CHARSET
);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
]);
} catch (PDOException $e) {
http_response_code(500);
header('Content-Type: application/json');
die(json_encode([
'erreur' => 'Connexion à la base de données échouée.',
'detail' => $e->getMessage()
]));
}
}

return $pdo;
}

