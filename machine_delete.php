<?php
require __DIR__ . '/includes/bootstrap.php';
require_level(NIVEAU_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('machines.php');
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare('SELECT nom FROM machines WHERE id = ?');
$stmt->execute([$id]);
$nom = $stmt->fetchColumn();

if ($nom === false) {
    flash('error', 'Cette machine n’existe pas.');
} else {
    // Les logiciels installés et les signalements sont supprimés en cascade (clés étrangères).
    $pdo->prepare('DELETE FROM machines WHERE id = ?')->execute([$id]);
    flash('success', 'La machine « ' . $nom . ' » a été supprimée.');
}
redirect('machines.php');
