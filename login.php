<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Renseignez votre adresse e-mail et votre mot de passe.';
    } elseif (login_attempt($email, $password)) {
        redirect('index.php');
    } else {
        usleep(400000); // ralentit légèrement les tentatives répétées
        $error = 'Identifiants incorrects, ou compte désactivé.';
    }
}
$appName = config()['app_name'];
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion · <?= e($appName) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
  <div class="auth">
    <section class="auth-hero">
      <div class="brand light"><span class="mark" aria-hidden="true"></span><span><?= e($appName) ?><small>Inventaire du parc</small></span></div>
      <h1>Chaque machine,<br>sa fiche d’identité.</h1>
      <p>Configuration matérielle, système et logiciels des postes de la salle, au même endroit.</p>
      <div class="scan" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
    </section>

    <section class="auth-form">
      <form method="post" class="card auth-card" novalidate>
        <h2>Connexion</h2>
        <p class="muted">Accédez à l’inventaire avec votre compte.</p>

        <?php if ($error !== ''): ?>
          <div class="flash error" role="alert"><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <?= csrf_field() ?>
        <label class="field">
          <span>Adresse e-mail</span>
          <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
        </label>
        <label class="field">
          <span>Mot de passe</span>
          <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn primary block" type="submit">Se connecter</button>
      </form>

      <details class="demo">
        <summary>Comptes de démonstration (V0)</summary>
        <table class="tbl compact">
          <tr><td><?= badge_niveau(1) ?></td><td>eleve@parc.local</td><td><code>eleve123</code></td></tr>
          <tr><td><?= badge_niveau(2) ?></td><td>prof@parc.local</td><td><code>prof1234</code></td></tr>
          <tr><td><?= badge_niveau(3) ?></td><td>admin@parc.local</td><td><code>admin123</code></td></tr>
        </table>
        <p class="muted small-text">À supprimer avant toute mise en ligne définitive.</p>
      </details>
    </section>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>
