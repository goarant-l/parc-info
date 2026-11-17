<?php
/**
 * Authentification et gestion des droits (niveaux 1 à 3).
 *
 *  Niveau 1 - Élève          : se connecter, consulter le matériel, signaler une panne
 *  Niveau 2 - Professeur     : + ajouter / modifier des machines, valider les signalements
 *  Niveau 3 - Administrateur : + supprimer des machines, gérer les comptes
 */
const NIVEAU_ELEVE = 1;
const NIVEAU_PROF = 2;
const NIVEAU_ADMIN = 3;

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function has_level(int $niveau): bool
{
    $user = current_user();
    return $user !== null && (int) $user['niveau'] >= $niveau;
}

/** Tente une connexion. Retourne true en cas de succès. */
function login_attempt(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        return false;
    }

    session_regenerate_id(true); // évite la fixation de session
    $_SESSION['user'] = [
        'id'       => (int) $user['id'],
        'nom'      => $user['nom'],
        'prenom'   => $user['prenom'],
        'email'    => $user['email'],
        'niveau'   => (int) $user['niveau'],
        'salle_id' => $user['salle_id'] !== null ? (int) $user['salle_id'] : null,
    ];

    $upd = db()->prepare('UPDATE utilisateurs SET derniere_connexion = ? WHERE id = ?');
    $upd->execute([date('Y-m-d H:i:s'), $user['id']]);
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (current_user() === null) {
        redirect('login.php');
    }
}

function require_level(int $niveau): void
{
    require_login();
    if (!has_level($niveau)) {
        forbidden();
    }
}

function forbidden(): void
{
    http_response_code(403);
    $pageTitle = 'Accès refusé';
    $active = '';
    require __DIR__ . '/header.php';
    echo '<div class="empty"><h2>Accès refusé</h2>'
        . '<p>Votre niveau d’accès (' . e(NIVEAUX[current_user()['niveau']] ?? '?') . ') ne permet pas d’effectuer cette action.</p>'
        . '<a class="btn" href="index.php">Retour au tableau de bord</a></div>';
    require __DIR__ . '/footer.php';
    exit;
}
/** IP du poste qui consulte le site (avec une IP simulée possible en développement). */
function client_ip(): string
{
    $simulee = config()['dev_ip_simulee'] ?? '';
    if ($simulee !== '') {
        return $simulee;   // dev uniquement
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

/** Condition SQL limitant les machines visibles selon le niveau. */
function machine_scope(array $user, string $alias = 'm'): array
{
    if ((int) $user['niveau'] === NIVEAU_ELEVE) {
        return ["{$alias}.ip = ?", [client_ip()]];
    }
    return ['1 = 1', []];
}