<?php
/**
 * Fonctions utilitaires : échappement, messages flash, CSRF, libellés, icônes.
 */

const NIVEAUX = [1 => 'Élève', 2 => 'Professeur', 3 => 'Administrateur'];
const STATUTS_MACHINE = ['actif' => 'En service', 'panne' => 'En panne', 'maintenance' => 'Maintenance'];
const STATUTS_SIGNALEMENT = ['ouvert' => 'Ouvert', 'valide' => 'Validé', 'rejete' => 'Rejeté', 'resolu' => 'Résolu'];
const TYPES_MACHINE = ['Poste fixe', 'Portable', 'Serveur'];
const TYPES_DISQUE = ['SSD', 'NVMe', 'HDD'];

/** Échappe une valeur pour l'affichage HTML (protection XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ----- Messages flash ----- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ----- Protection CSRF ----- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        exit('Requête invalide (jeton de sécurité manquant ou expiré). Retournez en arrière et réessayez.');
    }
}

/* ----- Formatage ----- */
function fdate(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($withTime ? 'd/m/Y à H:i' : 'd/m/Y', $ts) : '—';
}

function fsize(?int $go): string
{
    if ($go === null || $go === 0) {
        return '—';
    }
    return $go >= 1000 ? rtrim(rtrim(number_format($go / 1000, 1, ',', ''), '0'), ',') . ' To' : $go . ' Go';
}

/** « mar. 10/11/2026 » */
function fjour(string $date): string
{
    $jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    $ts = strtotime($date);
    return $ts ? $jours[(int) date('w', $ts)] . ' ' . date('d/m/Y', $ts) : '—';
}

/** « 08:00 » */
function fheure(string $datetime): string
{
    $ts = strtotime($datetime);
    return $ts ? date('H:i', $ts) : '—';
}

/** « mar. 10/11/2026 · 08:00 – 10:00 » */
function fcreneau(string $debut, string $fin): string
{
    return fjour($debut) . ' · ' . fheure($debut) . ' – ' . fheure($fin);
}

function val($value, string $fallback = '—'): string
{
    return ($value === null || $value === '') ? $fallback : e($value);
}

/* ----- Badges ----- */
function badge_statut_machine(string $statut): string
{
    $label = STATUTS_MACHINE[$statut] ?? $statut;
    $class = ['actif' => 'ok', 'panne' => 'bad', 'maintenance' => 'warn'][$statut] ?? 'neutral';
    return '<span class="badge ' . $class . '"><i></i>' . e($label) . '</span>';
}

function badge_statut_signalement(string $statut): string
{
    $label = STATUTS_SIGNALEMENT[$statut] ?? $statut;
    $class = ['ouvert' => 'warn', 'valide' => 'bad', 'rejete' => 'neutral', 'resolu' => 'ok'][$statut] ?? 'neutral';
    return '<span class="badge ' . $class . '"><i></i>' . e($label) . '</span>';
}

function badge_niveau(int $niveau): string
{
    return '<span class="badge lvl' . $niveau . '">Niveau ' . $niveau . ' · ' . e(NIVEAUX[$niveau] ?? '?') . '</span>';
}

/* ----- Icônes (SVG en ligne, trait 1.8) ----- */
function icon(string $name): string
{
    $paths = [
        'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'alert'   => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4.5M12 17.5v.01"/>',
        'users'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.4c2 .8 3.5 2.6 3.5 5.6"/>',
        'logout'  => '<path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M16 8l4 4-4 4M20 12H9"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'trash'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/>',
        'edit'    => '<path d="M4 20h4L19 9l-4-4L4 16v4ZM13.5 6.5l4 4"/>',
        'search'  => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'back'    => '<path d="M15 5l-7 7 7 7"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}
