<?php
/* ============================================================
api/documents.php
Documents académiques (cours, TD, administratif)

GET ?action=liste &categorie=cours&matiere_id=1
→ documents accessibles à l'étudiant connecté (sa classe, ou
documents sans classe précise = pour tout le monde)

GET ?action=rechercher &q=algorithmique
→ recherche rapide (titre) — utilisée par "Espace personnel"

POST ?action=ajouter {titre, categorie, fichier, taille_ko,
matiere_id, classe_id}
→ déposer un document (prof/admin) — le fichier doit déjà avoir
été uploadé physiquement par un script séparé (upload.php),
ici on enregistre juste sa référence en base

POST ?action=supprimer {id}
→ supprimer un document (prof/admin)

Le téléchargement réel du fichier se fait via telecharger.php
(pas ici, puisqu'une réponse JSON ne peut pas être un fichier).
============================================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
exigerConnexion();

routerAction([
'liste' => 'listeDocuments',
'rechercher' => 'rechercherDocuments',
'ajouter' => 'ajouterDocument',
'supprimer' => 'supprimerDocument',
]);

/* ============================================================
Liste des documents accessibles à l'étudiant connecté
============================================================ */
function listeDocuments(): void {
$db = getDB();
$categorie = $_GET['categorie'] ?? null;
$matiereId = (int)($_GET['matiere_id'] ?? 0);

$classeId = 0;
if (getRole() === 'etudiant') {
$stmt = $db->prepare('SELECT classe_id FROM etudiants WHERE id = ?');
$stmt->execute([getEtudiantId()]);
$classeId = (int)($stmt->fetch()['classe_id'] ?? 0);
}

$sql = 'SELECT d.id, d.titre, d.categorie, d.taille_ko, d.created_at,
m.nom AS matiere
FROM documents d
LEFT JOIN matieres m ON m.id = d.matiere_id
WHERE (d.classe_id IS NULL OR d.classe_id = ?)';
$params = [$classeId];

if ($categorie) {
$sql .= ' AND d.categorie = ?';
$params[] = $categorie;
}
if ($matiereId) {
$sql .= ' AND d.matiere_id = ?';
$params[] = $matiereId;
}
$sql .= ' ORDER BY d.created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);

jsonReponse(['documents' => $stmt->fetchAll()]);
}

/* ============================================================
Recherche rapide par titre (Espace personnel)
============================================================ */
function rechercherDocuments(): void {
$db = getDB();
$q = trim($_GET['q'] ?? '');
if ($q === '') jsonReponse(['documents' => []]);

$classeId = 0;
if (getRole() === 'etudiant') {
$stmt = $db->prepare('SELECT classe_id FROM etudiants WHERE id = ?');
$stmt->execute([getEtudiantId()]);
$classeId = (int)($stmt->fetch()['classe_id'] ?? 0);
}

$stmt = $db->prepare(
'SELECT d.id, d.titre, d.categorie, d.taille_ko,
m.nom AS matiere
FROM documents d
LEFT JOIN matieres m ON m.id = d.matiere_id
WHERE (d.classe_id IS NULL OR d.classe_id = ?) AND d.titre LIKE ?
ORDER BY d.created_at DESC
LIMIT 20'
);
$stmt->execute([$classeId, '%' . $q . '%']);

jsonReponse(['documents' => $stmt->fetchAll()]);
}

/* ============================================================
Déposer un document (prof / admin) — référence uniquement,
le fichier physique doit déjà avoir été uploadé avant cet appel
============================================================ */
function ajouterDocument(): void {
exigerRoles(['admin', 'professeur']);

$data = getJson();
requis($data, ['titre', 'categorie', 'fichier']);

$titre = trim($data['titre']);
$categorie = $data['categorie'];
$fichier = trim($data['fichier']);
$tailleKo = (int)($data['taille_ko'] ?? 0);
$matiereId = !empty($data['matiere_id']) ? (int)$data['matiere_id'] : null;
$classeId = !empty($data['classe_id']) ? (int)$data['classe_id'] : null;

if (!in_array($categorie, ['cours', 'td', 'administratif'])) jsonErreur('Catégorie invalide');

$db = getDB();
$stmt = $db->prepare(
'INSERT INTO documents (titre, categorie, fichier, taille_ko, matiere_id, classe_id, uploader_id)
VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$titre, $categorie, $fichier, $tailleKo, $matiereId, $classeId, getUtilisateurId()]);

jsonReponse(['succes' => true, 'id' => (int)$db->lastInsertId()], 201);
}

/* ============================================================
Supprimer un document (prof / admin)
============================================================ */
function supprimerDocument(): void {
exigerRoles(['admin', 'professeur']);

$data = getJson();
$id = (int)($data['id'] ?? 0);
if (!$id) jsonErreur('id requis');

$db = getDB();
$stmt = $db->prepare('DELETE FROM documents WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) jsonErreur('Document introuvable', 404);

jsonReponse(['succes' => true]);
}
