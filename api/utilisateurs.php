<?php
/* ============================================================
api/utilisateurs.php
Gestion des utilisateurs (admin) et profil étudiant

GET ?action=liste &role=etudiant → liste des utilisateurs
GET ?action=profil → profil de l'utilisateur connecté
POST ?action=ajouter → créer un compte (admin)
POST ?action=modifier → modifier un utilisateur
POST ?action=modifier_profil → modifier son propre profil
POST ?action=changer_mdp → changer son mot de passe
POST ?action=activer {id} → activer/désactiver un compte (admin)
GET ?action=etudiants &classe_id=1 → étudiants d'une classe
POST ?action=assigner_classe {etudiant_id, classe_id} → assigner un étudiant à une classe
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'liste' => 'listeUtilisateurs',
'profil' => 'monProfil',
'ajouter' => 'ajouterUtilisateur',
'modifier' => 'modifierUtilisateur',
'modifier_profil' => 'modifierMonProfil',
'changer_mdp' => 'changerMotDePasse',
'activer' => 'toggleStatut',
'etudiants' => 'listeEtudiants',
'assigner_classe' => 'assignerClasse',
]);

/* ============================================================
Liste des utilisateurs (admin)
============================================================ */
function listeUtilisateurs(): void {
exigerConnexion('admin');

$db = getDB();
$role = $_GET['role'] ?? '';
$statut = $_GET['statut'] ?? '';

$where = []; $params = [];
if (in_array($role, ['admin','professeur','etudiant'])) { $where[] = 'u.role = ?'; $params[] = $role; }
if (in_array($statut, ['actif','inactif'])) { $where[] = 'u.statut = ?'; $params[] = $statut; }

$sql = 'SELECT u.id, u.nom, u.prenom, u.email, u.role, u.statut, u.created_at
FROM utilisateurs u'
. ($where ? ' WHERE ' . implode(' AND ', $where) : '')
. ' ORDER BY u.nom, u.prenom';

$stmt = $db->prepare($sql);
$stmt->execute($params);
jsonReponse(['utilisateurs' => $stmt->fetchAll()]);
}

/* ============================================================
Profil de l'utilisateur connecté
============================================================ */
function monProfil(): void {
$db = getDB();
$userId = getUtilisateurId();

$stmt = $db->prepare('SELECT id, nom, prenom, email, role, statut FROM utilisateurs WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) jsonErreur('Utilisateur introuvable', 404);

$infos = [];
if ($user['role'] === 'etudiant') {
$s = $db->prepare(
'SELECT e.id, e.matricule, e.date_naissance, e.lieu_naissance, e.telephone, e.photo,
f.nom AS filiere, nv.libelle AS niveau, o.nom AS option_nom,
aa.libelle AS annee_academique, c.id AS classe_id
FROM etudiants e
JOIN classes c ON e.classe_id = c.id
JOIN filieres f ON c.filiere_id = f.id
JOIN niveaux nv ON c.niveau_id = nv.id
LEFT JOIN options_filiere o ON c.option_id = o.id
JOIN annees_academiques aa ON c.annee_academique_id = aa.id
WHERE e.utilisateur_id = ? LIMIT 1'
);
$s->execute([$userId]);
$infos = $s->fetch() ?: [];
} elseif ($user['role'] === 'professeur') {
$s = $db->prepare('SELECT id, specialite, grade, telephone FROM professeurs WHERE utilisateur_id = ?');
$s->execute([$userId]);
$infos = $s->fetch() ?: [];
}

jsonReponse(['utilisateur' => array_merge($user, $infos)]);
}

/* ============================================================
Ajouter un utilisateur (admin)
============================================================ */
function ajouterUtilisateur(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['nom', 'prenom', 'email', 'role', 'mot_de_passe']);

if (!in_array($data['role'], ['admin','professeur','etudiant'])) jsonErreur('Rôle invalide');
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) jsonErreur('Email invalide');
if (strlen($data['mot_de_passe']) < 6) jsonErreur('Mot de passe trop court (6 chars min)');

$db = getDB();

// Email unique
$check = $db->prepare('SELECT id FROM utilisateurs WHERE email = ?');
$check->execute([trim($data['email'])]);
if ($check->fetch()) jsonErreur('Cet email est déjà utilisé');

$hash = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
$stmt = $db->prepare(
'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut) VALUES (?,?,?,?,?,?)'
);
$stmt->execute([
trim($data['nom']),
trim($data['prenom']),
trim($data['email']),
$hash,
$data['role'],
'actif',
]);
$userId = (int)$db->lastInsertId();

// Créer le profil selon le rôle
if ($data['role'] === 'professeur') {
$db->prepare('INSERT INTO professeurs (utilisateur_id, specialite, grade, telephone) VALUES (?,?,?,?)')
->execute([$userId, $data['specialite'] ?? null, $data['grade'] ?? null, $data['telephone'] ?? null]);

} elseif ($data['role'] === 'etudiant') {
if (empty($data['classe_id'])) jsonErreur('classe_id requis pour un étudiant');
$matricule = genererMatricule($db, $data['classe_id']);
$db->prepare(
'INSERT INTO etudiants (utilisateur_id, matricule, classe_id, date_naissance, lieu_naissance, telephone)
VALUES (?,?,?,?,?,?)'
)->execute([
$userId, $matricule, (int)$data['classe_id'],
$data['date_naissance'] ?? null,
$data['lieu_naissance'] ?? null,
$data['telephone'] ?? null,
]);
}

jsonReponse(['succes' => true, 'message' => 'Utilisateur créé', 'id' => $userId]);
}

/* ============================================================
Modifier un utilisateur (admin)
============================================================ */
function modifierUtilisateur(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['id']);
$id = (int)$data['id'];

$db = getDB();
$cols = []; $vals = [];

foreach (['nom','prenom','email','statut'] as $c) {
if (isset($data[$c])) { $cols[] = "$c = ?"; $vals[] = trim($data[$c]); }
}

if (!$cols) jsonErreur('Aucun champ à modifier');
$vals[] = $id;
$db->prepare('UPDATE utilisateurs SET ' . implode(', ',$cols) . ' WHERE id = ?')->execute($vals);

jsonReponse(['succes' => true, 'message' => 'Utilisateur modifié']);
}

/* ============================================================
Modifier son propre profil
============================================================ */
function modifierMonProfil(): void {
$data = getJson();
$userId = getUtilisateurId();
$db = getDB();

// Champs modifiables sur la table utilisateurs
$colsU = []; $valsU = [];
foreach (['nom','prenom'] as $c) {
if (!empty($data[$c])) { $colsU[] = "$c = ?"; $valsU[] = trim($data[$c]); }
}
if ($colsU) {
$valsU[] = $userId;
$db->prepare('UPDATE utilisateurs SET ' . implode(',',$colsU) . ' WHERE id = ?')->execute($valsU);
}

// Champs étudiant
if (getRole() === 'etudiant') {
$colsE = []; $valsE = [];
foreach (['telephone','lieu_naissance','date_naissance'] as $c) {
if (isset($data[$c])) { $colsE[] = "$c = ?"; $valsE[] = $data[$c]; }
}
if ($colsE) {
$valsE[] = $userId;
$db->prepare('UPDATE etudiants SET ' . implode(',',$colsE) . ' WHERE utilisateur_id = ?')
->execute($valsE);
}
}

// Champs professeur
if (getRole() === 'professeur') {
$colsP = []; $valsP = [];
foreach (['telephone','specialite','grade'] as $c) {
if (isset($data[$c])) { $colsP[] = "$c = ?"; $valsP[] = $data[$c]; }
}
if ($colsP) {
$valsP[] = $userId;
$db->prepare('UPDATE professeurs SET ' . implode(',',$colsP) . ' WHERE utilisateur_id = ?')
->execute($valsP);
}
}

jsonReponse(['succes' => true, 'message' => 'Profil mis à jour']);
}

/* ============================================================
Changer son mot de passe
============================================================ */
function changerMotDePasse(): void {
$data = getJson();
requis($data, ['ancien_mdp', 'nouveau_mdp']);

if (strlen($data['nouveau_mdp']) < 6) jsonErreur('Mot de passe trop court (6 chars min)');

$db = getDB();
$userId = getUtilisateurId();

$stmt = $db->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($data['ancien_mdp'], $user['mot_de_passe'])) {
jsonErreur('Ancien mot de passe incorrect', 401);
}

$hash = password_hash($data['nouveau_mdp'], PASSWORD_BCRYPT);
$db->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')->execute([$hash, $userId]);

jsonReponse(['succes' => true, 'message' => 'Mot de passe mis à jour']);
}

/* ============================================================
Activer / Désactiver un compte (admin)
============================================================ */
function toggleStatut(): void {
exigerConnexion('admin');

$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('SELECT statut FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) jsonErreur('Utilisateur introuvable', 404);

$nouveau = $user['statut'] === 'actif' ? 'inactif' : 'actif';
$db->prepare('UPDATE utilisateurs SET statut = ? WHERE id = ?')->execute([$nouveau, $id]);

jsonReponse(['succes' => true, 'nouveau_statut' => $nouveau]);
}

/* ============================================================
Étudiants d'une classe (admin / prof)
============================================================ */
function listeEtudiants(): void {
exigerRoles(['admin', 'professeur']);

$classeId = (int)($_GET['classe_id'] ?? 0);
if (!$classeId) jsonErreur('classe_id requis');

$db = getDB();
$stmt = $db->prepare(
'SELECT e.id, e.matricule, e.telephone,
CONCAT(u.prenom, " ", u.nom) AS etudiant,
u.email, u.statut
FROM etudiants e
JOIN utilisateurs u ON e.utilisateur_id = u.id
WHERE e.classe_id = ?
ORDER BY u.nom, u.prenom'
);
$stmt->execute([$classeId]);

jsonReponse(['etudiants' => $stmt->fetchAll()]);
}

/* ============================================================
Assigner un étudiant à une classe (admin)
============================================================ */
function assignerClasse(): void {
exigerConnexion('admin');

$data = getJson();
requis($data, ['etudiant_id', 'classe_id']);

$db = getDB();
$stmt = $db->prepare('UPDATE etudiants SET classe_id = ? WHERE id = ?');
$stmt->execute([(int)$data['classe_id'], (int)$data['etudiant_id']]);

if ($stmt->rowCount() === 0) jsonErreur('Étudiant introuvable', 404);

jsonReponse(['succes' => true, 'message' => 'Classe assignée avec succès']);
}

/* ============================================================
Générer un matricule unique
============================================================ */
function genererMatricule(PDO $db, int $classeId): string {
$classe = $db->prepare(
'SELECT f.code AS filiere_code, nv.libelle AS niveau, aa.libelle AS annee
FROM classes c
JOIN filieres f ON c.filiere_id = f.id
JOIN niveaux nv ON c.niveau_id = nv.id
JOIN annees_academiques aa ON c.annee_academique_id = aa.id
WHERE c.id = ?'
);
$classe->execute([$classeId]);
$c = $classe->fetch();

$annee = $c ? substr(str_replace('-', '', $c['annee']), 0, 4) : date('Y');
$filiere = $c ? strtoupper($c['filiere_code']) : 'SCO';
$niveau = $c ? strtoupper($c['niveau']) : 'L1';

// Compteur d'étudiants dans la classe
$count = $db->prepare('SELECT COUNT(*) AS nb FROM etudiants WHERE classe_id = ?');
$count->execute([$classeId]);
$nb = (int)$count->fetch()['nb'] + 1;

return sprintf('%s-%s-%s-%04d', $annee, $filiere, $niveau, $nb);
}
