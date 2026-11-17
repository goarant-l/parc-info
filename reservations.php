<?php
require __DIR__ . '/includes/bootstrap.php';
// Réservation de matériel : professeurs (niveau 2) et administrateur (niveau 3) uniquement.
require_level(NIVEAU_PROF);

$pdo = db();
$me = current_user();
$now = date('Y-m-d H:i:s');

// Seules les machines en service peuvent être réservées
$machines = $pdo->query("SELECT id, nom, type, marque, modele FROM machines WHERE statut = 'actif' ORDER BY nom")->fetchAll();
$machineIds = array_map(fn($m) => (string) $m['id'], $machines);

$vue = (string) ($_GET['vue'] ?? 'avenir');
if (!in_array($vue, ['avenir', 'passees', 'annulees'], true)) {
    $vue = 'avenir';
}

$form = [
    'machine_id' => (string) ($_GET['machine'] ?? ''),
    'date'       => date('Y-m-d'),
    'debut'      => '08:00',
    'fin'        => '10:00',
    'motif'      => '',
];
if (!in_array($form['machine_id'], $machineIds, true)) {
    $form['machine_id'] = '';
}
$errors = [];

function valid_time(string $t): bool
{
    $d = DateTime::createFromFormat('H:i', $t);
    return $d !== false && $d->format('H:i') === $t;
}

function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    // ----- Créer une réservation -----
    if ($action === 'creer') {
        foreach (['machine_id', 'date', 'debut', 'fin', 'motif'] as $k) {
            $form[$k] = trim((string) ($_POST[$k] ?? ''));
        }

        if (!in_array($form['machine_id'], $machineIds, true)) {
            $errors['machine_id'] = 'Choisissez une machine disponible (en service).';
        }
        if (!valid_date($form['date'])) {
            $errors['date'] = 'Date invalide.';
        }
        if (!valid_time($form['debut'])) {
            $errors['debut'] = 'Heure de début invalide.';
        }
        if (!valid_time($form['fin'])) {
            $errors['fin'] = 'Heure de fin invalide.';
        }
        $len = mb_strlen($form['motif']);
        if ($len < 3 || $len > 200) {
            $errors['motif'] = 'Indiquez le motif en 3 à 200 caractères.';
        }

        if (!$errors) {
            $debut = $form['date'] . ' ' . $form['debut'] . ':00';
            $fin = $form['date'] . ' ' . $form['fin'] . ':00';

            if ($fin <= $debut) {
                $errors['fin'] = 'L’heure de fin doit être après l’heure de début.';
            } elseif ($fin <= $now) {
                $errors['date'] = 'Ce créneau est déjà passé.';
            } elseif ($form['date'] > date('Y-m-d', strtotime('+1 year'))) {
                $errors['date'] = 'Réservation possible jusqu’à un an à l’avance.';
            }
        }

        if (!$errors) {
            // Un créneau ne peut pas chevaucher une réservation active de la même machine.
            // (Vérification puis insertion : le risque de collision simultanée est négligeable pour la V0.)
            $stmt = $pdo->prepare(
                "SELECT r.debut, r.fin, u.prenom, u.nom
                   FROM reservations r LEFT JOIN utilisateurs u ON u.id = r.utilisateur_id
                  WHERE r.machine_id = ? AND r.statut = 'active' AND r.debut < ? AND r.fin > ?
               ORDER BY r.debut LIMIT 1"
            );
            $stmt->execute([(int) $form['machine_id'], $fin, $debut]);
            $conflit = $stmt->fetch();

            if ($conflit) {
                $errors['conflit'] = 'Cette machine est déjà réservée le ' . fjour($conflit['debut'])
                    . ' de ' . fheure($conflit['debut']) . ' à ' . fheure($conflit['fin'])
                    . ($conflit['prenom'] !== null ? ' par ' . $conflit['prenom'] . ' ' . $conflit['nom'] : '') . '.';
            } else {
                $pdo->prepare(
                    "INSERT INTO reservations (machine_id, utilisateur_id, debut, fin, motif, statut, date_creation)
                     VALUES (?, ?, ?, ?, ?, 'active', ?)"
                )->execute([(int) $form['machine_id'], $me['id'], $debut, $fin, $form['motif'], $now]);

                $nom = '';
                foreach ($machines as $m) {
                    if ((string) $m['id'] === $form['machine_id']) {
                        $nom = $m['nom'];
                    }
                }
                flash('success', 'Réservation enregistrée : ' . $nom . ', ' . fcreneau($debut, $fin) . '.');
                redirect('reservations.php');
            }
        }

    // ----- Annuler une réservation -----
    } elseif ($action === 'annuler') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ?');
        $stmt->execute([$id]);
        $r = $stmt->fetch();

        if (!$r || $r['statut'] !== 'active') {
            flash('error', 'Réservation introuvable ou déjà annulée.');
        } elseif (!has_level(NIVEAU_ADMIN) && (int) $r['utilisateur_id'] !== $me['id']) {
            flash('error', 'Vous ne pouvez annuler que vos propres réservations.');
        } elseif ($r['fin'] < $now) {
            flash('error', 'Cette réservation est terminée, elle ne peut plus être annulée.');
        } else {
            $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?")->execute([$id]);
            flash('success', 'Réservation annulée.');
        }
        redirect('reservations.php?vue=' . $vue);
    }
}

// ----- Compteurs et liste -----
$stmt = $pdo->prepare(
    "SELECT SUM(CASE WHEN statut = 'active' AND fin >= ? THEN 1 ELSE 0 END) AS avenir,
            SUM(CASE WHEN statut = 'active' AND fin <  ? THEN 1 ELSE 0 END) AS passees,
            SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) AS annulees
       FROM reservations"
);
$stmt->execute([$now, $now]);
$counts = array_map('intval', $stmt->fetch());

$base = "SELECT r.*, m.nom AS machine, m.statut AS machine_statut, u.prenom, u.nom AS user_nom
           FROM reservations r
           JOIN machines m ON m.id = r.machine_id
           LEFT JOIN utilisateurs u ON u.id = r.utilisateur_id";
if ($vue === 'avenir') {
    $stmt = $pdo->prepare("$base WHERE r.statut = 'active' AND r.fin >= ? ORDER BY r.debut ASC");
    $stmt->execute([$now]);
} elseif ($vue === 'passees') {
    $stmt = $pdo->prepare("$base WHERE r.statut = 'active' AND r.fin < ? ORDER BY r.debut DESC");
    $stmt->execute([$now]);
} else {
    $stmt = $pdo->prepare("$base WHERE r.statut = 'annulee' ORDER BY r.debut DESC");
    $stmt->execute();
}
$liste = $stmt->fetchAll();

$vues = ['avenir' => 'À venir', 'passees' => 'Passées', 'annulees' => 'Annulées'];

$pageTitle = 'Réservations';
$active = 'reservations';
require __DIR__ . '/includes/header.php';
?>
<header class="page-head">
  <div>
    <h1>Réservations de matériel</h1>
    <p class="muted">Réservez une machine pour un TP, une démonstration ou un contrôle. Accès réservé aux professeurs et à l’administrateur.</p>
  </div>
</header>

<form method="post" action="reservations.php" class="card form" novalidate>
  <h2>Nouvelle réservation</h2>
  <?php if (!$machines): ?>
    <p class="muted">Aucune machine n’est disponible (en service) pour le moment.</p>
  <?php else: ?>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="creer">

    <?php if ($errors): ?>
      <div class="flash error" role="alert">
        <span><?= isset($errors['conflit']) ? e($errors['conflit']) : 'Le formulaire contient ' . count($errors) . ' erreur' . (count($errors) > 1 ? 's' : '') . ', vérifiez les champs en rouge.' ?></span>
      </div>
    <?php endif; ?>

    <div class="grid-3">
      <label class="field<?= isset($errors['machine_id']) ? ' invalid' : '' ?>"><span>Machine *</span>
        <select name="machine_id" required>
          <option value="">Choisir une machine…</option>
          <?php foreach ($machines as $m): ?>
            <option value="<?= (int) $m['id'] ?>" <?= $form['machine_id'] === (string) $m['id'] ? 'selected' : '' ?>>
              <?= e($m['nom'] . ' · ' . trim(($m['marque'] ?? '') . ' ' . ($m['modele'] ?? '')) ) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['machine_id'])): ?><small class="err"><?= e($errors['machine_id']) ?></small><?php endif; ?>
      </label>
      <label class="field<?= isset($errors['date']) ? ' invalid' : '' ?>"><span>Date *</span>
        <input type="date" name="date" value="<?= e($form['date']) ?>" min="<?= e(date('Y-m-d')) ?>" required>
        <?php if (isset($errors['date'])): ?><small class="err"><?= e($errors['date']) ?></small><?php endif; ?>
      </label>
      <label class="field<?= isset($errors['debut']) ? ' invalid' : '' ?>"><span>Début *</span>
        <input type="time" name="debut" value="<?= e($form['debut']) ?>" step="900" required>
        <?php if (isset($errors['debut'])): ?><small class="err"><?= e($errors['debut']) ?></small><?php endif; ?>
      </label>
      <label class="field<?= isset($errors['fin']) ? ' invalid' : '' ?>"><span>Fin *</span>
        <input type="time" name="fin" value="<?= e($form['fin']) ?>" step="900" required>
        <?php if (isset($errors['fin'])): ?><small class="err"><?= e($errors['fin']) ?></small><?php endif; ?>
      </label>
      <label class="field span<?= isset($errors['motif']) ? ' invalid' : '' ?>"><span>Motif *</span>
        <input type="text" name="motif" value="<?= e($form['motif']) ?>" maxlength="200" placeholder="Ex. TP développement web – groupe SLAM" required>
        <?php if (isset($errors['motif'])): ?><small class="err"><?= e($errors['motif']) ?></small><?php endif; ?>
      </label>
    </div>
    <div class="form-actions">
      <button class="btn primary" type="submit"><?= icon('calendar') ?>Réserver</button>
    </div>
  <?php endif; ?>
</form>

<div class="chips">
  <?php foreach ($vues as $key => $label): ?>
    <a class="chip <?= $vue === $key ? 'on' : '' ?>" href="reservations.php?vue=<?= e($key) ?>"><?= e($label) ?> <span class="count"><?= $counts[$key] ?></span></a>
  <?php endforeach; ?>
</div>

<?php if (!$liste): ?>
  <div class="empty">
    <h2>Aucune réservation <?= e(['avenir' => 'à venir', 'passees' => 'passée', 'annulees' => 'annulée'][$vue]) ?></h2>
    <p><?= $vue === 'avenir' ? 'Utilisez le formulaire ci-dessus pour réserver une machine.' : 'Rien à afficher dans cette liste pour le moment.' ?></p>
  </div>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr><th>Créneau</th><th>Machine</th><th>Réservé par</th><th>Motif</th><th>Statut</th><?php if ($vue === 'avenir'): ?><th>Action</th><?php endif; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($liste as $r): ?>
            <?php $mine = (int) $r['utilisateur_id'] === $me['id']; ?>
            <tr>
              <td class="nowrap strong"><?= e(fcreneau($r['debut'], $r['fin'])) ?></td>
              <td>
                <a class="strong" href="machine.php?id=<?= (int) $r['machine_id'] ?>"><?= e($r['machine']) ?></a>
                <?php if ($vue === 'avenir' && $r['machine_statut'] !== 'actif'): ?>
                  <span class="block"><?= badge_statut_machine($r['machine_statut']) ?></span>
                <?php endif; ?>
              </td>
              <td><?= $r['prenom'] !== null ? e($r['prenom'] . ' ' . $r['user_nom']) : '<span class="muted">Compte supprimé</span>' ?><?= $mine ? ' <small class="muted">(vous)</small>' : '' ?></td>
              <td class="wide"><?= e($r['motif']) ?></td>
              <td>
                <?php if ($vue === 'annulees'): ?>
                  <span class="badge neutral"><i></i>Annulée</span>
                <?php elseif ($vue === 'passees'): ?>
                  <span class="badge neutral"><i></i>Terminée</span>
                <?php elseif ($r['debut'] <= $now): ?>
                  <span class="badge ok"><i></i>En cours</span>
                <?php else: ?>
                  <span class="badge lvl2"><i></i>À venir</span>
                <?php endif; ?>
              </td>
              <?php if ($vue === 'avenir'): ?>
                <td class="nowrap">
                  <?php if ($mine || has_level(NIVEAU_ADMIN)): ?>
                    <form class="inline" method="post" action="reservations.php?vue=<?= e($vue) ?>" data-confirm="Annuler la réservation de <?= e($r['machine']) ?> (<?= e(fcreneau($r['debut'], $r['fin'])) ?>) ?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="annuler">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <button class="btn small danger" type="submit"><?= icon('x') ?>Annuler</button>
                    </form>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
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
