<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT m.*, s.nom AS salle FROM machines m JOIN salles s ON s.id = m.salle_id WHERE m.id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) {
    http_response_code(404);
    $pageTitle = 'Machine introuvable';
    $active = 'machines';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><h2>Machine introuvable</h2><p>Cette fiche n’existe pas (ou a été supprimée).</p>'
       . '<a class="btn" href="machines.php">Retour à la liste</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Signalement d'une panne (tous les niveaux)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'signaler') {
    csrf_check();
    $description = trim((string) ($_POST['description'] ?? ''));
    $len = mb_strlen($description);
    if ($len < 10 || $len > 1000) {
        flash('error', 'Décrivez la panne en 10 à 1000 caractères.');
    } else {
        $ins = $pdo->prepare('INSERT INTO signalements (machine_id, utilisateur_id, description, statut, date_creation) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([$id, current_user()['id'], $description, 'ouvert', date('Y-m-d H:i:s')]);
        flash('success', 'Votre signalement a été enregistré. Un professeur va le traiter.');
    }
    redirect('machine.php?id=' . $id . '#signalements');
}

$stmt = $pdo->prepare(
    'SELECT l.nom, l.editeur, ml.version
       FROM machine_logiciel ml JOIN logiciels l ON l.id = ml.logiciel_id
      WHERE ml.machine_id = ? ORDER BY l.nom'
);
$stmt->execute([$id]);
$logiciels = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT s.*, u.prenom, u.nom
       FROM signalements s LEFT JOIN utilisateurs u ON u.id = s.utilisateur_id
      WHERE s.machine_id = ? ORDER BY s.date_creation DESC, s.id DESC'
);
$stmt->execute([$id]);
$signalements = $stmt->fetchAll();

$pageTitle = $m['nom'];
$active = 'machines';
require __DIR__ . '/includes/header.php';
?>
<a class="back" href="machines.php"><?= icon('back') ?>Toutes les machines</a>

<header class="page-head">
  <div>
    <h1><?= e($m['nom']) ?> <?= badge_statut_machine($m['statut']) ?></h1>
    <p class="muted"><?= e($m['type']) ?> · <?= val(trim(($m['marque'] ?? '') . ' ' . ($m['modele'] ?? ''))) ?> · <?= e($m['salle']) ?></p>
  </div>
  <div class="actions">
    <?php if (has_level(NIVEAU_PROF) && $m['statut'] === 'actif'): ?>
      <a class="btn" href="reservations.php?machine=<?= $id ?>"><?= icon('calendar') ?>Réserver</a>
    <?php endif; ?>
    <?php if (has_level(NIVEAU_PROF)): ?>
      <a class="btn" href="machine_form.php?id=<?= $id ?>"><?= icon('edit') ?>Modifier</a>
    <?php endif; ?>
    <?php if (has_level(NIVEAU_ADMIN)): ?>
      <form method="post" action="machine_delete.php" data-confirm="Supprimer définitivement la machine « <?= e($m['nom']) ?> » ainsi que ses signalements ?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn danger" type="submit"><?= icon('trash') ?>Supprimer</button>
      </form>
    <?php endif; ?>
  </div>
</header>

<div class="tabs" role="tablist">
  <button type="button" role="tab" data-tab="materiel" class="on">Matériel</button>
  <button type="button" role="tab" data-tab="systeme">Système &amp; réseau</button>
  <button type="button" role="tab" data-tab="logiciels">Logiciels <span class="count"><?= count($logiciels) ?></span></button>
  <button type="button" role="tab" data-tab="signalements">Signalements <span class="count"><?= count($signalements) ?></span></button>
</div>

<section class="card panel" data-panel="materiel">
  <dl class="kv">
    <div><dt>Processeur</dt><dd><?= val($m['cpu']) ?></dd></div>
    <div><dt>Mémoire vive</dt><dd><?= e(fsize($m['ram_go'] !== null ? (int) $m['ram_go'] : null)) ?></dd></div>
    <div><dt>Stockage</dt><dd><?= e(fsize($m['disque_go'] !== null ? (int) $m['disque_go'] : null)) ?> <?= e($m['disque_type']) ?></dd></div>
    <div><dt>Marque</dt><dd><?= val($m['marque']) ?></dd></div>
    <div><dt>Modèle</dt><dd><?= val($m['modele']) ?></dd></div>
    <div><dt>Numéro de série</dt><dd><code><?= val($m['numero_serie']) ?></code></dd></div>
    <div><dt>Date d’achat</dt><dd><?= e(fdate($m['date_achat'])) ?></dd></div>
    <div><dt>Ajoutée à l’inventaire le</dt><dd><?= e(fdate($m['date_ajout'])) ?></dd></div>
  </dl>
</section>

<section class="card panel" data-panel="systeme" hidden>
  <dl class="kv">
    <div><dt>Système d’exploitation</dt><dd><?= val($m['os']) ?></dd></div>
    <div><dt>Version</dt><dd><?= val($m['os_version']) ?></dd></div>
    <div><dt>Adresse IP</dt><dd><code><?= val($m['ip']) ?></code></dd></div>
    <div><dt>Adresse MAC</dt><dd><code><?= val($m['mac']) ?></code></dd></div>
    <div><dt>Salle</dt><dd><?= e($m['salle']) ?></dd></div>
    <div><dt>Statut</dt><dd><?= badge_statut_machine($m['statut']) ?></dd></div>
  </dl>
</section>

<section class="card panel flush" data-panel="logiciels" hidden>
  <?php if (!$logiciels): ?>
    <p class="muted pad">Aucun logiciel recensé sur cette machine.</p>
  <?php else: ?>
    <table class="tbl">
      <thead><tr><th>Logiciel</th><th>Éditeur</th><th>Version</th></tr></thead>
      <tbody>
        <?php foreach ($logiciels as $l): ?>
          <tr><td class="strong"><?= e($l['nom']) ?></td><td><?= val($l['editeur']) ?></td><td><code><?= val($l['version']) ?></code></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="panel" data-panel="signalements" id="signalements" hidden>
  <div class="cols">
    <div class="card">
      <h2>Historique</h2>
      <?php if (!$signalements): ?>
        <p class="muted">Aucun signalement pour cette machine.</p>
      <?php else: ?>
        <ul class="feed">
          <?php foreach ($signalements as $s): ?>
            <li>
              <div class="feed-top">
                <strong><?= $s['prenom'] !== null ? e($s['prenom'] . ' ' . $s['nom']) : 'Compte supprimé' ?></strong>
                <?= badge_statut_signalement($s['statut']) ?>
              </div>
              <p><?= nl2br(e($s['description'])) ?></p>
              <small class="muted"><?= e(fdate($s['date_creation'], true)) ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <form class="card" method="post" action="machine.php?id=<?= $id ?>">
      <h2>Signaler une panne</h2>
      <p class="muted">Décrivez ce que vous observez (symptôme, message d’erreur, depuis quand…).</p>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="signaler">
      <label class="field">
        <span>Description</span>
        <textarea name="description" rows="5" minlength="10" maxlength="1000" required></textarea>
      </label>
      <button class="btn primary" type="submit"><?= icon('alert') ?>Envoyer le signalement</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
