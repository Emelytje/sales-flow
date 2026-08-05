<?php
/**
 * SalesFlow Enterprise — Installation wizard.
 *
 * Self-contained, dependency-free setup: checks requirements, tests the
 * database connection, imports the schema + seed data, creates the first admin
 * account and writes the .env file. Delete this file after installation.
 */

declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Detect project root: flat htdocs layout (app next to this file) or a
// dedicated /public web root (app one level up).
define('ROOT', is_dir(__DIR__ . '/database') ? __DIR__ : dirname(__DIR__));
$envPath = ROOT . '/.env';
$step = (int) ($_GET['step'] ?? 1);
$errors = [];

/* Already installed? */
if (is_file($envPath) && !isset($_GET['force']) && $step === 1) {
    $step = 99;
}

function req(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $_SESSION['install'][$key] ?? $default));
}

/* ---- Step handlers -------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], $_POST);

    if ($step === 2) {
        // Test DB connection.
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', req('db_host', 'localhost'), req('db_port', '3306'));
            $pdo = new PDO($dsn, req('db_user'), req('db_pass'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $db = req('db_name');
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db}`");
            header('Location: ?step=3');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Databaseverbinding mislukt: ' . $e->getMessage();
            $step = 2;
        }
    } elseif ($step === 3) {
        // Import schema + seed.
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', req('db_host'), req('db_port', '3306'), req('db_name'));
            $pdo = new PDO($dsn, req('db_user'), req('db_pass'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec((string) file_get_contents(ROOT . '/database/schema.sql'));
            $pdo->exec((string) file_get_contents(ROOT . '/database/seed.sql'));
            header('Location: ?step=4');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Import mislukt: ' . $e->getMessage();
            $step = 3;
        }
    } elseif ($step === 4) {
        // Create admin + write .env.
        $name = req('admin_name');
        $email = strtolower(req('admin_email'));
        $pass = (string) ($_POST['admin_pass'] ?? '');
        $company = req('company_name', 'SalesFlow Enterprise');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10) {
            $errors[] = 'Vul een geldige naam, e-mail en een wachtwoord van minstens 10 tekens in.';
            $step = 4;
        } else {
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', req('db_host'), req('db_port', '3306'), req('db_name'));
                $pdo = new PDO($dsn, req('db_user'), req('db_pass'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?: 'admin';
                $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,booking_slug,status,created_at,updated_at)
                    VALUES (?,?,?,"admin",?,"active",NOW(),NOW())');
                $stmt->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]), trim($slug, '-')]);
                $pdo->prepare('UPDATE settings SET value = ? WHERE setting_key = "company_name"')->execute([$company]);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $appUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $appKey = bin2hex(random_bytes(24));

                $env = <<<ENV
APP_NAME="{$company}"
APP_ENV=production
APP_DEBUG=false
APP_URL={$appUrl}
APP_TIMEZONE=Europe/Brussels
APP_LOCALE=nl
APP_KEY={$appKey}

DB_HOST={$_SESSION['install']['db_host']}
DB_PORT={$_SESSION['install']['db_port']}
DB_NAME={$_SESSION['install']['db_name']}
DB_USER={$_SESSION['install']['db_user']}
DB_PASSWORD="{$_SESSION['install']['db_pass']}"

SESSION_LIFETIME=7200
REMEMBER_DAYS=30
SESSION_SECURE=true

MAIL_DRIVER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_FROM=no-reply@{$_SERVER['HTTP_HOST']}
MAIL_FROM_NAME="{$company}"
ENV;
                if (@file_put_contents($envPath, $env) === false) {
                    $errors[] = 'Kon .env niet schrijven. Controleer de schrijfrechten op de hoofdmap.';
                    $step = 4;
                } else {
                    unset($_SESSION['install']);
                    header('Location: ?step=5');
                    exit;
                }
            } catch (Throwable $e) {
                $errors[] = 'Adminaanmaak mislukt: ' . $e->getMessage();
                $step = 4;
            }
        }
    }
}

/* ---- Requirements --------------------------------------------------------- */
$checks = [
    'PHP 8.1 of hoger'          => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL-extensie'        => extension_loaded('pdo_mysql'),
    'OpenSSL-extensie'          => extension_loaded('openssl'),
    'mbstring-extensie'         => extension_loaded('mbstring'),
    'Hoofdmap schrijfbaar'      => is_writable(ROOT),
    'storage/ schrijfbaar'      => is_writable(ROOT . '/storage'),
];
$allOk = !in_array(false, $checks, true);
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SalesFlow — Installatie</title>
<style>
    :root{--rose:#E98CAB;--rose-dark:#B33B62;--bg:#FFFDFB;--surface:#fff;--border:#EFE3D8;--text:#2C2230;--muted:#9C8F98}
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',system-ui,sans-serif}
    body{background:linear-gradient(150deg,#F5EBDD,#FFFDFB);color:var(--text);min-height:100vh;display:grid;place-items:center;padding:2rem}
    .wiz{width:100%;max-width:560px;background:var(--surface);border-radius:24px;box-shadow:0 18px 48px rgba(120,60,90,.16);overflow:hidden}
    .wiz-head{background:linear-gradient(135deg,var(--rose),var(--rose-dark));color:#fff;padding:2rem}
    .wiz-head .logo{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-weight:800;font-size:20px;margin-bottom:1rem}
    .wiz-head h1{font-size:1.5rem}.wiz-head p{opacity:.9;margin-top:.25rem}
    .steps{display:flex;gap:6px;margin-top:1.25rem}
    .steps i{flex:1;height:5px;border-radius:9px;background:rgba(255,255,255,.3)}
    .steps i.on{background:#fff}
    .wiz-body{padding:2rem}
    .field{margin-bottom:1rem}
    label{display:block;font-size:13px;font-weight:600;color:#6B5E68;margin-bottom:5px}
    input{width:100%;padding:11px 14px;border:1px solid #E2D2C2;border-radius:12px;font-size:15px}
    input:focus{outline:none;border-color:var(--rose);box-shadow:0 0 0 4px rgba(233,140,171,.18)}
    .btn{display:inline-flex;align-items:center;gap:8px;justify-content:center;width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--rose),var(--rose-dark));color:#fff;font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 8px 30px rgba(233,140,171,.35)}
    .check{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border);font-size:14px}
    .ok{color:#2FA36B;font-weight:700}.bad{color:#D8476A;font-weight:700}
    .alert{background:#FBE7EC;color:#D8476A;padding:12px 16px;border-radius:12px;font-size:14px;margin-bottom:1rem}
    .row{display:grid;grid-template-columns:2fr 1fr;gap:12px}
    .note{font-size:13px;color:var(--muted);margin-top:1rem;line-height:1.6}
    .done{text-align:center;padding:1rem 0}
    .done .big{font-size:3.5rem}
</style>
</head>
<body>
<div class="wiz">
    <div class="wiz-head">
        <div class="logo">SF</div>
        <h1>SalesFlow Enterprise</h1>
        <p>Installatie-assistent</p>
        <div class="steps">
            <?php for ($i = 1; $i <= 5; $i++): ?><i class="<?= $i <= min($step, 5) ? 'on' : '' ?>"></i><?php endfor; ?>
        </div>
    </div>
    <div class="wiz-body">
        <?php foreach ($errors as $err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

        <?php if ($step === 99): ?>
            <div class="done"><div class="big">✅</div><h2>Al geïnstalleerd</h2>
            <p class="note">Er bestaat al een <code>.env</code>. Verwijder <code>public/install.php</code> voor de veiligheid, of ga verder met <a href="?step=1&force=1">opnieuw installeren</a>.</p>
            <a class="btn" href="/login" style="margin-top:1rem;">Naar aanmelden</a></div>

        <?php elseif ($step === 1): ?>
            <h2 style="margin-bottom:1rem;">Systeemvereisten</h2>
            <?php foreach ($checks as $label => $ok): ?>
                <div class="check"><span><?= $label ?></span><span class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓ OK' : '✗ Ontbreekt' ?></span></div>
            <?php endforeach; ?>
            <?php if ($allOk): ?>
                <a class="btn" href="?step=2" style="margin-top:1.5rem;">Verder naar database →</a>
            <?php else: ?>
                <p class="note">Los de ontbrekende punten op en herlaad deze pagina.</p>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <h2 style="margin-bottom:1rem;">Databaseverbinding</h2>
            <form method="post" action="?step=2">
                <div class="row">
                    <div class="field"><label>Host</label><input name="db_host" value="<?= htmlspecialchars(req('db_host', 'localhost')) ?>" required></div>
                    <div class="field"><label>Poort</label><input name="db_port" value="<?= htmlspecialchars(req('db_port', '3306')) ?>"></div>
                </div>
                <div class="field"><label>Databasenaam</label><input name="db_name" value="<?= htmlspecialchars(req('db_name', 'salesflow')) ?>" required></div>
                <div class="field"><label>Gebruiker</label><input name="db_user" value="<?= htmlspecialchars(req('db_user', 'root')) ?>" required></div>
                <div class="field"><label>Wachtwoord</label><input type="password" name="db_pass" value="<?= htmlspecialchars(req('db_pass')) ?>"></div>
                <button class="btn">Verbinding testen →</button>
            </form>

        <?php elseif ($step === 3): ?>
            <h2 style="margin-bottom:.5rem;">Database opbouwen</h2>
            <p class="note" style="margin-bottom:1rem;">We maken alle tabellen aan en laden de standaardgegevens (rollen, rechten, sectoren, instellingen).</p>
            <form method="post" action="?step=3"><button class="btn">Tabellen importeren →</button></form>

        <?php elseif ($step === 4): ?>
            <h2 style="margin-bottom:1rem;">Beheerdersaccount</h2>
            <form method="post" action="?step=4">
                <div class="field"><label>Bedrijfsnaam</label><input name="company_name" value="<?= htmlspecialchars(req('company_name', 'SalesFlow Enterprise')) ?>" required></div>
                <div class="field"><label>Jouw naam</label><input name="admin_name" value="<?= htmlspecialchars(req('admin_name')) ?>" required></div>
                <div class="field"><label>E-mail</label><input type="email" name="admin_email" value="<?= htmlspecialchars(req('admin_email')) ?>" required></div>
                <div class="field"><label>Wachtwoord (min. 10 tekens)</label><input type="password" name="admin_pass" required></div>
                <button class="btn">Installatie voltooien →</button>
            </form>

        <?php elseif ($step === 5): ?>
            <div class="done">
                <div class="big">🎉</div>
                <h2>Installatie voltooid!</h2>
                <p class="note">Verwijder nu <code>public/install.php</code> van de server voor de veiligheid.</p>
                <a class="btn" href="/login" style="margin-top:1.5rem;">Aanmelden bij SalesFlow →</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
