<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$user = current_user();
if ((int) $user['niveau'] === NIVEAU_ELEVE) {
    [$scopeSql, $scopeParams] = machine_scope($user);
    $st = $pdo->prepare("SELECT m.id FROM machines m WHERE $scopeSql LIMIT 1");
    $st->execute($scopeParams);
    $monPoste = $st->fetchColumn();
    if ($monPoste !== false) {
        redirect('machine.php?id=' . (int) $monPoste);
    }
    $pageTitle = 'Tableau de bord';
    $active = 'index';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><h2>Aucun poste reconnu</h2>'
       . '<p>Connectez-vous depuis un ordinateur de la salle pour voir sa fiche.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Répartition par statut
$parStatut = ['actif' => 0, 'panne' => 0, 'maintenance' => 0];
foreach ($pdo->query('SELECT statut, COUNT(*) AS n FROM machines GROUP BY statut') as $row) {
    $parStatut[$row['statut']] = (int) $row['n'];
}
$totalMachines = array_sum($parStatut);

$ramTotale = (int) $pdo->query('SELECT COALESCE(SUM(ram_go), 0) FROM machines')->fetchColumn();
$nbLogiciels = (int) $pdo->query('SELECT COUNT(DISTINCT logiciel_id) FROM machine_logiciel')->fetchColumn();
$ouverts = (int) $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'ouvert'")->fetchColumn();

// Répartition par système d'exploitation
$parOs = $pdo->query("SELECT COALESCE(os, 'Non renseigné') AS os, COUNT(*) AS n FROM machines GROUP BY os ORDER BY n DESC")->fetchAll();
$maxOs = $parOs ? max(array_column($parOs, 'n')) : 1;

// Derniers signalements
$recents = $pdo->query(
    'SELECT s.id, s.description, s.statut, s.date_creation, m.id AS machine_id, m.nom AS machine
       FROM signalements s
       JOIN machines m ON m.id = s.machine_id
   ORDER BY s.date_creation DESC, s.id DESC
      LIMIT 5'
)->fetchAll();

$pageTitle = 'Tableau de bord';
$active = 'index';
require __DIR__ . '/includes/header.php';
?>
<header class="page-head">
  <div>
    <h1>Bonjour <?= e($user['prenom']) ?></h1>
    <p class="muted">Vue d’ensemble du parc au <?= e(date('d/m/Y')) ?>.</p>
  </div>
  <?php if (has_level(NIVEAU_PROF)): ?>
    <a class="btn primary" href="machine_form.php"><?= icon('plus') ?>Ajouter une machine</a>
  <?php endif; ?>
</header>

<section class="stats">
  <div class="stat"><span>Machines</span><strong><?= $totalMachines ?></strong></div>
  <div class="stat"><span>En panne</span><strong class="bad-text"><?= $parStatut['panne'] ?></strong></div>
  <div class="stat"><span>Signalements ouverts</span><strong class="warn-text"><?= $ouverts ?></strong></div>
  <div class="stat"><span>RAM totale</span><strong><?= e(fsize($ramTotale)) ?></strong></div>
  <div class="stat"><span>Logiciels distincts</span><strong><?= $nbLogiciels ?></strong></div>
</section>

<div class="cols">
  <section class="card">
    <h2>État du parc</h2>
    <?php if ($totalMachines > 0): ?>
      <div class="bar" role="img" aria-label="Répartition des machines par statut">
        <?php foreach ($parStatut as $statut => $n): if ($n === 0) continue; ?>
          <span class="seg <?= e($statut) ?>" style="flex: <?= $n ?>"></span>
        <?php endforeach; ?>
      </div>
      <ul class="legend">
        <?php foreach ($parStatut as $statut => $n): ?>
          <li><span class="dot <?= e($statut) ?>"></span><?= e(STATUTS_MACHINE[$statut]) ?> <strong><?= $n ?></strong></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">Aucune machine enregistrée.</p>
    <?php endif; ?>

    <h3>Systèmes d’exploitation</h3>
    <ul class="hbars">
      <?php foreach ($parOs as $row): ?>
        <li>
          <span class="lbl"><?= e($row['os']) ?></span>
          <span class="track"><span style="width: <?= round(($row['n'] / $maxOs) * 100) ?>%"></span></span>
          <span class="num"><?= (int) $row['n'] ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <div class="card-head">
      <h2>Derniers signalements</h2>
      <a href="signalements.php" class="link">Tout voir</a>
    </div>
    <?php if (!$recents): ?>
      <p class="muted">Aucun signalement pour le moment.</p>
    <?php else: ?>
      <ul class="feed">
        <?php foreach ($recents as $s): ?>
          <li>
            <div class="feed-top">
              <a href="machine.php?id=<?= (int) $s['machine_id'] ?>#signalements"><strong><?= e($s['machine']) ?></strong></a>
              <?= badge_statut_signalement($s['statut']) ?>
            </div>
            <p><?= e(mb_strimwidth($s['description'], 0, 110, '…')) ?></p>
            <small class="muted"><?= e(fdate($s['date_creation'], true)) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
