<?php

/**
 * EXAMPLE: temporary "session bridge" for browser tests. Adapt to the project, run, and discard.
 *
 * Forges a logged-in Windwalker session for an EXISTING user so Playwright can skip the login form,
 * and removes it afterwards. It never changes the user's password.
 *
 *   php session-bridge.php create <email> [stage]   # prints the session id (stage: admin|front, default admin)
 *   php session-bridge.php destroy <session_id>
 *
 * Storage: follows SESSION_DEFAULT (`filesystem` default => <tmp>/sess/sess_<id>; `database` => `sessions` table).
 * Session data is `serialize(['login_user_id' => <id>])` (see Luna UserHandler::rememberLoginUserId()).
 * Cookie: front `WINDWALKER_SESSID`, admin `WINDWALKER_ADMIN_SESSID`.
 *
 * DB settings: read from phpunit.xml `<php><env|server name="DATABASE_*">`, falling back to `.env`.
 * Never echo the password. The user must be enabled=1 and verified=1 with a superuser/admin role.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2); // adjust: copied to <root>/tmp/e2e/

function loadEnv(string $root): array
{
    $env = [];

    // Later files override earlier ones. SESSION_DEFAULT usually lives in .env.base.
    foreach (['.env.base', '.env.dev', '.env'] as $file) {
        if (!is_file($root . '/' . $file)) {
            continue;
        }

        foreach (file($root . '/' . $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\"'");
        }
    }

    $phpunit = $root . '/phpunit.xml';

    if (is_file($phpunit)) {
        $xml = simplexml_load_file($phpunit);

        foreach ($xml->xpath('//php/env | //php/server') ?: [] as $node) {
            $env[(string) $node['name']] = (string) $node['value'];
        }
    }

    return $env;
}

function pdo(array $env): PDO
{
    // phpunit.xml: WINDWALKER_TEST_DB_DSN="pdo_mysql:host=..;port=..;dbname=..;user=..;******;prefix=.."
    if (!empty($env['WINDWALKER_TEST_DB_DSN'])) {
        [$scheme, $rest] = explode(':', $env['WINDWALKER_TEST_DB_DSN'], 2);
        parse_str(str_replace(';', '&', $rest), $p);
        $env = array_merge($env, [
            'DATABASE_DRIVER' => $scheme,
            'DATABASE_HOST' => $p['host'] ?? 'localhost',
            'DATABASE_NAME' => $p['dbname'] ?? '',
            'DATABASE_USER' => $p['user'] ?? 'root',
            'DATABASE_PASSWORD' => $p['password'] ?? '',
        ]);
    }

    $driver = $env['DATABASE_DRIVER'] ?? 'pdo_mysql';

    if ($driver !== 'pdo_mysql') {
        throw new RuntimeException("Adapt the DSN for driver $driver.");
    }

    return new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['DATABASE_HOST'] ?? 'localhost', $env['DATABASE_NAME']),
        $env['DATABASE_USER'] ?? 'root',
        $env['DATABASE_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function sessionFile(string $id): string
{
    if (!preg_match('/\A[a-zA-Z0-9,-]+\z/', $id)) {
        throw new InvalidArgumentException('Bad session id');
    }

    return sys_get_temp_dir() . '/sess/sess_' . $id;
}

$env = loadEnv($root);
$store = ($env['SESSION_DEFAULT'] ?? '') ?: 'filesystem';
[$cmd, $arg] = [$argv[1] ?? '', $argv[2] ?? ''];

if ($cmd === 'create') {
    $stage = $argv[3] ?? 'admin';
    $db = pdo($env);
    $stmt = $db->prepare('SELECT id, enabled, verified FROM users WHERE email = ?');
    $stmt->execute([$arg]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: throw new RuntimeException('User not found.');

    if ((int) $user['enabled'] !== 1 || (int) $user['verified'] !== 1) {
        throw new RuntimeException('User must be enabled=1 and verified=1.');
    }

    $id = bin2hex(random_bytes(16));
    $data = serialize(['login_user_id' => (int) $user['id']]);

    if ($store === 'database') {
        $db->prepare('INSERT INTO sessions (id, data, user_id, stage, time) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $data, (int) $user['id'], $stage, time()]);
    } elseif ($store === 'filesystem') {
        @mkdir(dirname(sessionFile($id)), 0777, true);
        file_put_contents(sessionFile($id), $data);
    } else {
        throw new RuntimeException("Unsupported session store: $store");
    }

    echo $id, PHP_EOL;
} elseif ($cmd === 'destroy') {
    if ($store === 'database') {
        pdo($env)->prepare('DELETE FROM sessions WHERE id = ?')->execute([$arg]);
    } else {
        @unlink(sessionFile($arg));
    }

    echo "destroyed\n";
} else {
    fwrite(STDERR, "Usage: create <email> [stage] | destroy <session_id>\n");
    exit(1);
}
