<?php
require __DIR__ . '/includes/bootstrap.php';
require_level(NIVEAU_PROF);

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;

$fields = ['nom', 'type', 'salle_id', 'marque', 'modele', 'numero_serie', 'cpu', 'ram_go', 'disque_go',
           'disque_type', 'os', 'os_version', 'ip', 'mac', 'statut', 'date_achat'];

$salles = $pdo->query('SELECT id, nom FROM salles ORDER BY nom')->fetchAll();

$data = array_fill_keys($fields, '');
$data['type'] = 'Poste fixe';
$data['statut'] = 'actif';
$data['salle_id'] = $salles ? (string) $salles[0]['id'] : '';

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM machines WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash('error', 'Cette machine n’existe plus.');
        redirect('machines.php');
    }
    foreach ($fields as $f) {
        $data[$f] = (string) ($existing[$f] ?? '');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($fields as $f) {
        $data[$f] = trim((string) ($_POST[$f] ?? ''));
    }

    // ----- Validation -----
    if ($data['nom'] === '' || mb_strlen($data['nom']) > 60) {
        $errors['nom'] = 'Le nom est obligatoire (60 caractères maximum).';
    } else {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM machines WHERE nom = ? AND id <> ?');
        $chk->execute([$data['nom'], $id]);
        if ((int) $chk->fetchColumn() > 0) {
            $errors['nom'] = 'Une machine porte déjà ce nom.';
        }
    }
    if (!in_array($data['type'], TYPES_MACHINE, true)) {
        $errors['type'] = 'Type invalide.';
    }
    if (!in_array($data['salle_id'], array_map(fn($s) => (string) $s['id'], $salles), true)) {
        $errors['salle_id'] = 'Choisissez une salle.';
    }
    if (!isset(STATUTS_MACHINE[$data['statut']])) {
        $errors['statut'] = 'Statut invalide.';
    }
    foreach (['ram_go' => 65535, 'disque_go' => 4000000] as $f => $max) {
        if ($data[$f] !== '' && (!ctype_digit($data[$f]) || (int) $data[$f] > $max)) {
            $errors[$f] = 'Entrez un nombre entier positif.';
        }
    }
    if ($data['disque_type'] !== '' && !in_array($data['disque_type'], TYPES_DISQUE, true)) {
        $errors['disque_type'] = 'Type de disque invalide.';
    }
    if ($data['ip'] !== '' && filter_var($data['ip'], FILTER_VALIDATE_IP) === false) {
        $errors['ip'] = 'Adresse IP invalide.';
    }
    if ($data['mac'] !== '' && !preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $data['mac'])) {
        $errors['mac'] = 'Format attendu : 00:1A:2B:3C:4D:5E';
    }
    if ($data['date_achat'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $data['date_achat']);
        if (!$d || $d->format('Y-m-d') !== $data['date_achat']) {
            $errors['date_achat'] = 'Date invalide.';
        }
    }
    foreach (['marque' => 60, 'modele' => 80, 'numero_serie' => 60, 'cpu' => 100, 'os' => 60, 'os_version' => 60] as $f => $max) {
        if (mb_strlen($data[$f]) > $max) {
            $errors[$f] = "$max caractères maximum.";
        }
    }

    // ----- Enregistrement -----
    if (!$errors) {
        $nullable = fn(string $k) => $data[$k] === '' ? null : $data[$k];
        $values = [
            'nom'          => $data['nom'],
            'type'         => $data['type'],
            'salle_id'     => (int) $data['salle_id'],
            'marque'       => $nullable('marque'),
            'modele'       => $nullable('modele'),
            'numero_serie' => $nullable('numero_serie'),
            'cpu'          => $nullable('cpu'),
            'ram_go'       => $data['ram_go'] === '' ? null : (int) $data['ram_go'],
            'disque_go'    => $data['disque_go'] === '' ? null : (int) $data['disque_go'],
            'disque_type'  => $nullable('disque_type'),
            'os'           => $nullable('os'),
            'os_version'   => $nullable('os_version'),
            'ip'           => $nullable('ip'),
            'mac'          => $data['mac'] === '' ? null : strtoupper($data['mac']),
            'statut'       => $data['statut'],
            'date_achat'   => $nullable('date_achat'),
        ];

        if ($editing) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($values)));
            $stmt = $pdo->prepare("UPDATE machines SET $set WHERE id = :id");
            $stmt->execute($values + ['id' => $id]);
            flash('success', 'La machine « ' . $data['nom'] . ' » a été mise à jour.');
        } else {
            $cols = implode(', ', array_keys($values));
            $marks = implode(', ', array_map(fn($k) => ":$k", array_keys($values)));
            $stmt = $pdo->prepare("INSERT INTO machines ($cols, date_ajout) VALUES ($marks, :date_ajout)");
            $stmt->execute($values + ['date_ajout' => date('Y-m-d H:i:s')]);
            $id = (int) $pdo->lastInsertId();
            flash('success', 'La machine « ' . $data['nom'] . ' » a été ajoutée à l’inventaire.');
        }
        redirect('machine.php?id=' . $id);
    }
}

/** Petit helper d'affichage d'un champ texte. */
function input(string $name, string $label, array $data, array $errors, string $type = 'text', string $extra = '', string $hint = ''): void
{
    echo '<label class="field' . (isset($errors[$name]) ? ' invalid' : '') . '"><span>' . e($label) . '</span>';
    echo '<input type="' . e($type) . '" name="' . e($name) . '" value="' . e($data[$name]) . '" ' . $extra . '>';
    if ($hint !== '' && !isset($errors[$name])) {
        echo '<small class="muted">' . e($hint) . '</small>';
    }
    if (isset($errors[$name])) {
        echo '<small class="err">' . e($errors[$name]) . '</small>';
    }
    echo '</label>';
}

function select(string $name, string $label, array $options, array $data, array $errors, bool $blank = false): void
{
    echo '<label class="field' . (isset($errors[$name]) ? ' invalid' : '') . '"><span>' . e($label) . '</span><select name="' . e($name) . '">';
    if ($blank) {
        echo '<option value="">—</option>';
    }
    foreach ($options as $value => $text) {
        echo '<option value="' . e($value) . '"' . ((string) $data[$name] === (string) $value ? ' selected' : '') . '>' . e($text) . '</option>';
    }
    echo '</select>';
    if (isset($errors[$name])) {
        echo '<small class="err">' . e($errors[$name]) . '</small>';
    }
    echo '</label>';
}

$pageTitle = $editing ? 'Modifier ' . $data['nom'] : 'Ajouter une machine';
$active = 'machines';
require __DIR__ . '/includes/header.php';
?>
<a class="back" href="<?= $editing ? 'machine.php?id=' . $id : 'machines.php' ?>"><?= icon('back') ?>Retour</a>
<header class="page-head">
  <div>
    <h1><?= $editing ? 'Modifier la machine' : 'Ajouter une machine' ?></h1>
    <p class="muted">Les champs marqués d’une étoile sont obligatoires.</p>
  </div>
</header>

<form method="post" class="card form" novalidate>
  <?= csrf_field() ?>
  <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <?php if ($errors): ?>
    <div class="flash error" role="alert"><span>Le formulaire contient <?= count($errors) ?> erreur<?= count($errors) > 1 ? 's' : '' ?>, vérifiez les champs en rouge.</span></div>
  <?php endif; ?>

  <fieldset>
    <legend>Identification</legend>
    <div class="grid-3">
      <?php input('nom', 'Nom *', $data, $errors, 'text', 'maxlength="60" required'); ?>
      <?php select('type', 'Type', array_combine(TYPES_MACHINE, TYPES_MACHINE), $data, $errors); ?>
      <?php select('salle_id', 'Salle', array_column($salles, 'nom', 'id'), $data, $errors); ?>
      <?php input('marque', 'Marque', $data, $errors, 'text', 'maxlength="60"'); ?>
      <?php input('modele', 'Modèle', $data, $errors, 'text', 'maxlength="80"'); ?>
      <?php input('numero_serie', 'Numéro de série', $data, $errors, 'text', 'maxlength="60"'); ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Matériel</legend>
    <div class="grid-3">
      <?php input('cpu', 'Processeur', $data, $errors, 'text', 'maxlength="100"'); ?>
      <?php input('ram_go', 'RAM (Go)', $data, $errors, 'number', 'min="0" max="65535"'); ?>
      <?php input('disque_go', 'Stockage (Go)', $data, $errors, 'number', 'min="0"'); ?>
      <?php select('disque_type', 'Type de stockage', array_combine(TYPES_DISQUE, TYPES_DISQUE), $data, $errors, true); ?>
      <?php input('date_achat', 'Date d’achat', $data, $errors, 'date'); ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Système &amp; réseau</legend>
    <div class="grid-3">
      <?php input('os', 'Système d’exploitation', $data, $errors, 'text', 'maxlength="60"'); ?>
      <?php input('os_version', 'Version', $data, $errors, 'text', 'maxlength="60"'); ?>
      <?php select('statut', 'Statut', STATUTS_MACHINE, $data, $errors); ?>
      <?php input('ip', 'Adresse IP', $data, $errors, 'text', 'maxlength="45" placeholder="192.168.20.21"'); ?>
      <?php input('mac', 'Adresse MAC', $data, $errors, 'text', 'maxlength="17" placeholder="00:1A:2B:3C:4D:5E"'); ?>
    </div>
  </fieldset>

  <div class="form-actions">
    <button class="btn primary" type="submit"><?= icon('check') ?><?= $editing ? 'Enregistrer' : 'Ajouter la machine' ?></button>
    <a class="btn ghost" href="<?= $editing ? 'machine.php?id=' . $id : 'machines.php' ?>">Annuler</a>
  </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
