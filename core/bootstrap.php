<?php
// Adjust these connection settings to match your XAMPP MySQL configuration.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'repairshop_db';
const DB_USER = 'root';
const DB_PASS = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check core/bootstrap.php and import the SQL schema.');
}
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function go(string $url): void { header('Location: '.$url); exit; }
function signed_in(): bool { return isset($_SESSION['user_id']); }
function require_login(): void { if (!signed_in()) go('login.php'); }
function csrf(): string { if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function validate_csrf(): void {
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request token.'); }
}
function alert(string $message): void { $_SESSION['flash'] = $message; }
function flash(): string { $m = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return $m; }
function record_audit(PDO $pdo, string $entity, int $id, string $action, string $description): void {
    $stmt=$pdo->prepare('INSERT INTO audit_logs (user_id,username,entity_type,record_id,action_type,description) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id'],$_SESSION['username'],$entity,$id,$action,$description]);
}
function ui_icon(string $name, int $size = 18): string {
    $paths = [
        'tool' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.6 6.6a2 2 0 0 0 2.8 2.8l6.6-6.6a4 4 0 0 0 5.4-5.4l-2.9 2.9-2.6-2.6z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'monitor' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'activity' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-7"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow-right' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5m6 6-6-6 6-6"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 10v7M14 10v7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'check' => '<path d="m4 12 5 5L20 6"/>',
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M16 11h6"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/>',
    ];
    $size = max(12, min(48, $size));
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['tool']).'</svg>';
}
function page_header(string $title): void {
    $loggedIn = signed_in();
    $username = h($_SESSION['username'] ?? 'Guest');
    $initial = h(strtoupper(substr((string)($_SESSION['username'] ?? 'G'), 0, 1)));
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $heading = $title;
    $eyebrow = 'PDO project / Computer repair';
    $subtitle = '';
    if ($title === 'Customer Management') {
        $heading = 'Customers';
        $subtitle = 'Add customers, check their devices, and keep track of changes.';
    } elseif (strpos($title, 'Devices of ') === 0) {
        $heading = 'Device management';
        $subtitle = 'View and update devices registered to this customer.';
    } elseif ($title === 'Activity Log — Who Did It') {
        $heading = 'Activity log';
        $subtitle = 'See which user added, edited, or deleted a record.';
    }
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#524639">
    <title><?= h($title) ?> | Rainhart&#039;s PC Repair</title>
    <link rel="stylesheet" href="styles.css?v=rainhart1">
    <script src="ui.js?v=rainhart1" defer></script>
</head>
<body class="<?= $loggedIn ? 'workspace-body' : 'auth-body' ?>">
<header class="site-header">
    <div class="nav-shell">
        <a class="brand" href="<?= $loggedIn ? 'index.php' : 'login.php' ?>" aria-label="Rainhart's PC Repair">
            <span class="brand-mark"><?= ui_icon('tool', 23) ?></span>
            <span class="brand-wordmark"><strong>Rainhart's PC Repair</strong><small>Customer and device records</small></span>
        </a>
        <nav class="site-nav" aria-label="Main navigation">
            <?php if ($loggedIn): ?>
                <a class="nav-link <?= $script === 'index.php' || $script === 'devices.php' ? 'active' : '' ?>" href="index.php"><?= ui_icon('users', 15) ?> Customers</a>
                <a class="nav-link <?= $script === 'audit.php' ? 'active' : '' ?>" href="audit.php"><?= ui_icon('activity', 15) ?> Activity log</a>
                <span class="nav-divider" aria-hidden="true"></span>
                <span class="user-pill"><span class="avatar"><?= $initial ?></span><?= $username ?></span>
                <form method="post" action="logout.php" class="logout-form">
                    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
                    <button class="logout-btn" type="submit"><?= ui_icon('logout', 14) ?> Log out</button>
                </form>
            <?php else: ?>
                <a class="nav-link <?= $script === 'login.php' ? 'active' : '' ?>" href="login.php">Log in</a>
                <a class="nav-link <?= $script === 'register.php' ? 'active' : '' ?>" href="register.php">Create account</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="<?= $loggedIn ? 'workspace-main' : 'auth-main' ?>">
    <?php if ($loggedIn): ?>
        <div class="page-heading">
            <div>
                <div class="eyebrow"><span class="dash" aria-hidden="true"></span><?= h($eyebrow) ?></div>
                <h1><?= h($heading) ?></h1>
                <?php if ($subtitle): ?><p class="page-subtitle"><?= h($subtitle) ?></p><?php endif; ?>
            </div>
            <span class="page-tag">PDO / SCHOOL PROJECT</span>
        </div>
    <?php endif; ?>
    <?php if ($notice = flash()): ?><div class="notice" role="status"><?= h($notice) ?></div><?php endif; ?>
    <?php
}
function page_footer(): void { ?>
    <footer class="footer">Rainhart's PC Repair &middot; PDO School Project &middot; UCOS 3-2</footer>
</main>
</body>
</html>
<?php }
