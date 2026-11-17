<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();
$user = current_user();

/**
 * Périmètre de visibilité selon le niveau :
 *  - élève : ses propres signalements
 *  - professeur : ceux de sa salle (ou tous s'il n'a pas de salle attribuée)
 *  - administrateur : tous
 */
function scope(array $user): array
{
    if ((int) $user['niveau'] === NIVEAU_ELEVE) {
        return ['s.utilisateur_id = ?', [$user['id']]];
    }
    if ((int) $user['niveau'] === NIVEAU_PROF && $user['salle_id'] !== null) {
        return ['m.salle_id = ?', [$user['salle_id']]];
    }
    return ['1 = 1', []];
}
[$scopeSql, $scopeParams] = scope($user);

// ----- Traitement d'un signalement (professeur / administrateur) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_level(NIVEAU_PROF);
    csrf_check();

    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $transitions = [
        'valider'  => ['ouvert', 'valide'],
        'rejeter'  => ['ouvert', 'rejete'],
        'resoudre' => ['valide', 'resolu'],
    ];

    $stmt = $pdo->prepare(
        "SELECT s.id, s.statut, s.machine_id FROM signalements s JOIN machines m ON m.id = s.machine_id
          WHERE s.id = ? AND $scopeSql"
    );
    $stmt->execute(array_merge([$id], $scopeParams));
    $s = $stmt->fetch();

    if (!$s || !isset($transitions[$action]) || $s['statut'] !== $transitions[$action][0]) {
        flash('error', 'Action impossible sur ce signalement.');
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE signalements SET statut = ?, date_traitement = ? WHERE id = ?')
            ->execute([$transitions[$action][1], date('Y-m-d H:i:s'), $id]);
        if ($action === 'valider') {
            $pdo->prepare("UPDATE machines SET statut = 'panne' WHERE id = ?")->execute([$s['machine_id']]);
        } elseif ($action === 'resoudre') {
            // La machine repasse en service s'il ne reste aucune autre panne validée
            $autres = $pdo->prepare("SELECT COUNT(*) FROM signalements WHERE machine_id = ? AND statut = 'valide' AND id <> ?");
            $autres->execute([$s['machine_id'], $id]);
            if ((int) $autres->fetchColumn() === 0) {
                $pdo->prepare("UPDATE machines SET statut = 'actif' WHERE id = ? AND statut = 'panne'")->execute([$s['machine_id']]);
            }
        }
        $pdo->commit();
        flash('success', [
            'valider'  => 'Signalement validé : la machine est marquée « en panne ».',
            'rejeter'  => 'Signalement rejeté.',
            'resoudre' => 'Signalement résolu.',
        ][$action]);
    }
    redirect('signalements.php' . (isset($_POST['filtre']) && $_POST['filtre'] !== '' ? '?statut=' . urlencode((string) $_POST['filtre']) : ''));
}

// ----- Liste -----
$filtre = (string) ($_GET['statut'] ?? '');
if (!isset(STATUTS_SIGNALEMENT[$filtre])) {
    $filtre = '';
}
$sql = "SELECT s.*, m.nom AS machine, u.prenom, u.nom AS user_nom
          FROM signalements s
          JOIN machines m ON m.id = s.machine_id
          LEFT JOIN utilisateurs u ON u.id = s.utilisateur_id
         WHERE $scopeSql";
$params = $scopeParams;
if ($filtre !== '') {
    $sql .= ' AND s.statut = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY s.date_creation DESC, s.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$liste = $stmt->fetchAll();

$pageTitle = 'Signalements';
$active = 'signalements';
require __DIR__ . '/includes/header.php';
?>
<header class="page-head">
  <div>
    <h1>Signalements</h1>
    <p class="muted"><?= (int) $user['niveau'] === NIVEAU_ELEVE ? 'Les pannes que vous avez signalées.' : 'Pannes signalées sur le parc, à valider ou à clôturer.' ?></p>
  </div>
</header>

<div class="chips">
  <a class="chip <?= $filtre === '' ? 'on' : '' ?>" href="signalements.php">Tous</a>
  <?php foreach (STATUTS_SIGNALEMENT as $key => $label): ?>
    <a class="chip <?= $filtre === $key ? 'on' : '' ?>" href="signalements.php?statut=<?= e($key) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$liste): ?>
  <div class="empty">
    <h2>Aucun signalement</h2>
    <p>Pour signaler une panne, ouvrez la fiche d’une machine, onglet « Signalements ».</p>
    <a class="btn" href="machines.php">Voir les machines</a>
  </div>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr><th>Date</th><th>Machine</th><th>Description</th><th>Signalé par</th><th>Statut</th><?php if (has_level(NIVEAU_PROF)): ?><th>Actions</th><?php endif; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($liste as $s): ?>
            <tr>
              <td class="nowrap"><?= e(fdate($s['date_creation'], true)) ?></td>
              <td><a class="strong" href="machine.php?id=<?= (int) $s['machine_id'] ?>#signalements"><?= e($s['machine']) ?></a></td>
              <td class="wide"><?= e($s['description']) ?></td>
              <td><?= $s['prenom'] !== null ? e($s['prenom'] . ' ' . $s['user_nom']) : '<span class="muted">Compte supprimé</span>' ?></td>
              <td><?= badge_statut_signalement($s['statut']) ?></td>
              <?php if (has_level(NIVEAU_PROF)): ?>
                <td class="nowrap">
                  <?php
                    $acts = [];
                    if ($s['statut'] === 'ouvert') { $acts = ['valider' => ['Valider', 'check', ''], 'rejeter' => ['Rejeter', 'x', 'ghost']]; }
                    if ($s['statut'] === 'valide') { $acts = ['resoudre' => ['Résoudre', 'check', '']]; }
                  ?>
                  <?php foreach ($acts as $act => [$label, $ico, $cls]): ?>
                    <form class="inline" method="post" action="signalements.php">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                      <input type="hidden" name="action" value="<?= e($act) ?>">
                      <input type="hidden" name="filtre" value="<?= e($filtre) ?>">
                      <button class="btn small <?= e($cls) ?>" type="submit"><?= icon($ico) ?><?= e($label) ?></button>
                    </form>
                  <?php endforeach; ?>
                  <?php if (!$acts): ?><span class="muted">—</span><?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
