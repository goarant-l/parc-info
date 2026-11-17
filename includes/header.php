<?php
/** Gabarit commun (haut de page + menu latéral). Variables attendues : $pageTitle, $active */
$user = current_user();
$appName = config()['app_name'];

$nav = [
    ['index',        'index.php',        'Tableau de bord', 'grid',    NIVEAU_ELEVE],
    ['machines',     'machines.php',     'Machines',        'monitor', NIVEAU_ELEVE],
    ['signalements', 'signalements.php', 'Signalements',    'alert',   NIVEAU_ELEVE],
    ['reservations', 'reservations.php', 'Réservations',    'calendar', NIVEAU_PROF],
    ['comptes',      'comptes.php',      'Comptes',         'users',   NIVEAU_ADMIN],
];
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Page') ?> · <?= e($appName) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="side">
    <a class="brand" href="index.php">
      <span class="mark" aria-hidden="true"></span>
      <span><?= e($appName) ?><small>Inventaire du parc</small></span>
    </a>
    <nav class="menu">
      <?php foreach ($nav as [$key, $href, $label, $ico, $min]): ?>
        <?php if (has_level($min)): ?>
          <a href="<?= e($href) ?>" class="<?= ($active ?? '') === $key ? 'on' : '' ?>"><?= icon($ico) ?><?= e($label) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="me">
      <div class="who">
        <strong><?= e($user['prenom'] . ' ' . $user['nom']) ?></strong>
        <?= badge_niveau((int) $user['niveau']) ?>
      </div>
      <form method="post" action="logout.php">
        <?= csrf_field() ?>
        <button class="btn ghost small" type="submit"><?= icon('logout') ?>Déconnexion</button>
      </form>
    </div>
  </aside>
  <main class="main">
    <?php foreach (take_flashes() as $f): ?>
      <div class="flash <?= e($f['type']) ?>" role="status">
        <span><?= e($f['msg']) ?></span>
        <button type="button" class="flash-x" aria-label="Fermer"><?= icon('x') ?></button>
      </div>
    <?php endforeach; ?>
