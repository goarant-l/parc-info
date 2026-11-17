<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$q = trim((string) ($_GET['q'] ?? ''));
$statut = (string) ($_GET['statut'] ?? '');
if (!isset(STATUTS_MACHINE[$statut])) {
    $statut = '';
}

$where = [];
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(m.nom LIKE ? OR m.marque LIKE ? OR m.modele LIKE ? OR m.os LIKE ? OR m.ip LIKE ? OR m.numero_serie LIKE ?)';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($statut !== '') {
    $where[] = 'm.statut = ?';
    $params[] = $statut;
}

$sql = 'SELECT m.*, s.nom AS salle FROM machines m JOIN salles s ON s.id = m.salle_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY m.nom';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$machines = $stmt->fetchAll();

$pageTitle = 'Machines';
$active = 'machines';
require __DIR__ . '/includes/header.php';
?>
<header class="page-head">
  <div>
    <h1>Machines</h1>
    <p class="muted"><?= count($machines) ?> machine<?= count($machines) > 1 ? 's' : '' ?><?= ($q !== '' || $statut !== '') ? ' correspondant à votre recherche' : ' dans le parc' ?>.</p>
  </div>
  <?php if (has_level(NIVEAU_PROF)): ?>
    <a class="btn primary" href="machine_form.php"><?= icon('plus') ?>Ajouter une machine</a>
  <?php endif; ?>
</header>

<form class="toolbar" method="get" action="machines.php">
  <label class="search">
    <?= icon('search') ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, marque, système, IP, n° de série…" aria-label="Rechercher">
  </label>
  <select name="statut" aria-label="Filtrer par statut">
    <option value="">Tous les statuts</option>
    <?php foreach (STATUTS_MACHINE as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $statut === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Filtrer</button>
  <?php if ($q !== '' || $statut !== ''): ?>
    <a class="btn ghost" href="machines.php">Réinitialiser</a>
  <?php endif; ?>
</form>

<?php if (!$machines): ?>
  <div class="empty">
    <h2>Aucune machine trouvée</h2>
    <p>Modifiez votre recherche<?= has_level(NIVEAU_PROF) ? ' ou ajoutez une nouvelle machine.' : '.' ?></p>
  </div>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Nom</th><th>Modèle</th><th>Système</th><th>Processeur</th>
            <th class="num">RAM</th><th class="num">Stockage</th><th>Adresse IP</th><th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($machines as $m): ?>
            <tr>
              <td>
                <a class="strong" href="machine.php?id=<?= (int) $m['id'] ?>"><?= e($m['nom']) ?></a>
                <small class="muted block"><?= e($m['type']) ?> · <?= e($m['salle']) ?></small>
              </td>
              <td><?= val(trim(($m['marque'] ?? '') . ' ' . ($m['modele'] ?? ''))) ?></td>
              <td><?= val(trim(($m['os'] ?? '') . ' ' . ($m['os_version'] ?? ''))) ?></td>
              <td><?= val($m['cpu']) ?></td>
              <td class="num"><?= e(fsize($m['ram_go'] !== null ? (int) $m['ram_go'] : null)) ?></td>
              <td class="num"><?= e(fsize($m['disque_go'] !== null ? (int) $m['disque_go'] : null)) ?> <small class="muted"><?= e($m['disque_type']) ?></small></td>
              <td><code><?= val($m['ip']) ?></code></td>
              <td><?= badge_statut_machine($m['statut']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
