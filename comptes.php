<?php
require __DIR__ . '/includes/bootstrap.php';
require_level(NIVEAU_ADMIN);

$pdo = db();
$me = current_user();
$errors = [];
$form = ['prenom' => '', 'nom' => '', 'email' => '', 'niveau' => '1', 'salle_id' => '', 'password' => ''];

$salles = $pdo->query('SELECT id, nom FROM salles ORDER BY nom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $targetId = (int) ($_POST['id'] ?? 0);

    if ($action === 'creer') {
        foreach ($form as $k => $_) {
            $form[$k] = trim((string) ($_POST[$k] ?? ''));
        }
        $form['password'] = (string) ($_POST['password'] ?? '');

        if ($form['prenom'] === '' || mb_strlen($form['prenom']) > 60) { $errors['prenom'] = 'Prénom obligatoire.'; }
        if ($form['nom'] === '' || mb_strlen($form['nom']) > 60)       { $errors['nom'] = 'Nom obligatoire.'; }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 120) {
            $errors['email'] = 'Adresse e-mail invalide.';
        } else {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
            $chk->execute([$form['email']]);
            if ((int) $chk->fetchColumn() > 0) { $errors['email'] = 'Cette adresse est déjà utilisée.'; }
        }
        if (!in_array($form['niveau'], ['1', '2', '3'], true)) { $errors['niveau'] = 'Niveau invalide.'; }
        if (mb_strlen($form['password']) < 8) { $errors['password'] = '8 caractères minimum.'; }
        $salleId = null;
        if ($form['salle_id'] !== '') {
            if (!in_array($form['salle_id'], array_map(fn($s) => (string) $s['id'], $salles), true)) {
                $errors['salle_id'] = 'Salle invalide.';
            } else {
                $salleId = (int) $form['salle_id'];
            }
        }

        if (!$errors) {
            $pdo->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, niveau, salle_id, actif) VALUES (?, ?, ?, ?, ?, ?, 1)')
                ->execute([$form['nom'], $form['prenom'], $form['email'], password_hash($form['password'], PASSWORD_DEFAULT), (int) $form['niveau'], $salleId]);
            flash('success', 'Le compte de ' . $form['prenom'] . ' ' . $form['nom'] . ' a été créé.');
            redirect('comptes.php');
        }
    } elseif (in_array($action, ['basculer', 'supprimer', 'niveau'], true)) {
        if ($targetId === $me['id']) {
            flash('error', 'Vous ne pouvez pas modifier votre propre compte depuis cette page.');
        } else {
            $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
            $stmt->execute([$targetId]);
            $target = $stmt->fetch();
            if (!$target) {
                flash('error', 'Compte introuvable.');
            } elseif ($action === 'basculer') {
                $pdo->prepare('UPDATE utilisateurs SET actif = ? WHERE id = ?')->execute([$target['actif'] ? 0 : 1, $targetId]);
                flash('success', 'Compte de ' . $target['prenom'] . ' ' . $target['nom'] . ($target['actif'] ? ' désactivé.' : ' réactivé.'));
            } elseif ($action === 'supprimer') {
                $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$targetId]);
                flash('success', 'Compte de ' . $target['prenom'] . ' ' . $target['nom'] . ' supprimé.');
            } else {
                $niveau = (int) ($_POST['niveau'] ?? 0);
                if ($niveau < 1 || $niveau > 3) {
                    flash('error', 'Niveau invalide.');
                } else {
                    $pdo->prepare('UPDATE utilisateurs SET niveau = ? WHERE id = ?')->execute([$niveau, $targetId]);
                    flash('success', 'Niveau de ' . $target['prenom'] . ' ' . $target['nom'] . ' : ' . NIVEAUX[$niveau] . '.');
                }
            }
        }
        redirect('comptes.php');
    }
}

$users = $pdo->query(
    'SELECT u.*, s.nom AS salle FROM utilisateurs u LEFT JOIN salles s ON s.id = u.salle_id ORDER BY u.niveau DESC, u.nom, u.prenom'
)->fetchAll();

$pageTitle = 'Comptes';
$active = 'comptes';
require __DIR__ . '/includes/header.php';
?>
<header class="page-head">
  <div>
    <h1>Comptes utilisateurs</h1>
    <p class="muted">Créez les comptes et attribuez les niveaux d’accès.</p>
  </div>
</header>

<div class="card flush">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Utilisateur</th><th>E-mail</th><th>Niveau</th><th>Salle</th><th>Dernière connexion</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): $isMe = (int) $u['id'] === $me['id']; ?>
          <tr class="<?= $u['actif'] ? '' : 'off' ?>">
            <td class="strong"><?= e($u['prenom'] . ' ' . $u['nom']) ?><?= $isMe ? ' <small class="muted">(vous)</small>' : '' ?><?= $u['actif'] ? '' : ' <span class="badge neutral"><i></i>Désactivé</span>' ?></td>
            <td><?= e($u['email']) ?></td>
            <td>
              <?php if ($isMe): ?>
                <?= badge_niveau((int) $u['niveau']) ?>
              <?php else: ?>
                <form class="inline" method="post" action="comptes.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="niveau">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <select name="niveau" class="mini" aria-label="Niveau de <?= e($u['prenom']) ?>">
                    <?php foreach (NIVEAUX as $n => $label): ?>
                      <option value="<?= $n ?>" <?= (int) $u['niveau'] === $n ? 'selected' : '' ?>>Niveau <?= $n ?> · <?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn small ghost" type="submit">OK</button>
                </form>
              <?php endif; ?>
            </td>
            <td><?= val($u['salle']) ?></td>
            <td class="nowrap"><?= e(fdate($u['derniere_connexion'], true)) ?></td>
            <td class="nowrap">
              <?php if (!$isMe): ?>
                <form class="inline" method="post" action="comptes.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="basculer">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="btn small" type="submit"><?= $u['actif'] ? 'Désactiver' : 'Réactiver' ?></button>
                </form>
                <form class="inline" method="post" action="comptes.php" data-confirm="Supprimer le compte de <?= e($u['prenom'] . ' ' . $u['nom']) ?> ?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="supprimer">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="btn small danger" type="submit" aria-label="Supprimer"><?= icon('trash') ?></button>
                </form>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="post" action="comptes.php" class="card form" novalidate>
  <h2>Créer un compte</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="creer">
  <div class="grid-3">
    <label class="field<?= isset($errors['prenom']) ? ' invalid' : '' ?>"><span>Prénom *</span>
      <input type="text" name="prenom" value="<?= e($form['prenom']) ?>" maxlength="60" required>
      <?php if (isset($errors['prenom'])): ?><small class="err"><?= e($errors['prenom']) ?></small><?php endif; ?></label>
    <label class="field<?= isset($errors['nom']) ? ' invalid' : '' ?>"><span>Nom *</span>
      <input type="text" name="nom" value="<?= e($form['nom']) ?>" maxlength="60" required>
      <?php if (isset($errors['nom'])): ?><small class="err"><?= e($errors['nom']) ?></small><?php endif; ?></label>
    <label class="field<?= isset($errors['email']) ? ' invalid' : '' ?>"><span>Adresse e-mail *</span>
      <input type="email" name="email" value="<?= e($form['email']) ?>" maxlength="120" required>
      <?php if (isset($errors['email'])): ?><small class="err"><?= e($errors['email']) ?></small><?php endif; ?></label>
    <label class="field<?= isset($errors['niveau']) ? ' invalid' : '' ?>"><span>Niveau d’accès</span>
      <select name="niveau">
        <?php foreach (NIVEAUX as $n => $label): ?>
          <option value="<?= $n ?>" <?= $form['niveau'] === (string) $n ? 'selected' : '' ?>>Niveau <?= $n ?> · <?= e($label) ?></option>
        <?php endforeach; ?>
      </select></label>
    <label class="field<?= isset($errors['salle_id']) ? ' invalid' : '' ?>"><span>Salle suivie</span>
      <select name="salle_id">
        <option value="">Aucune</option>
        <?php foreach ($salles as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= $form['salle_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <small class="muted">Pour un professeur : limite les signalements à sa salle.</small></label>
    <label class="field<?= isset($errors['password']) ? ' invalid' : '' ?>"><span>Mot de passe provisoire *</span>
      <input type="password" name="password" autocomplete="new-password" minlength="8" required>
      <?php if (isset($errors['password'])): ?><small class="err"><?= e($errors['password']) ?></small><?php endif; ?></label>
  </div>
  <div class="form-actions">
    <button class="btn primary" type="submit"><?= icon('plus') ?>Créer le compte</button>
  </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
