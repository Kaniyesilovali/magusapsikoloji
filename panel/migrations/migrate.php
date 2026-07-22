<?php
declare(strict_types=1);

/**
 * Şema kurulum/yükseltme aracı — YALNIZCA komut satırından çalışır.
 *
 *   php panel/migrations/migrate.php                 # bekleyen migration'ları uygula
 *   php panel/migrations/migrate.php --create-admin  # süper admin oluştur
 *
 * SSH yoksa aynı işi tarayıcıdan yapabilirsiniz: /panel/kurulum
 * (bu ekran yalnızca hiç kullanıcı yokken açılır).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu betik yalnızca komut satırından çalıştırılabilir. Tarayıcıdan kurulum için /panel/kurulum adresini kullanın.\n");
}

define('PANEL_BASE', '');
require dirname(__DIR__) . '/src/bootstrap.php';

use Panel\Auth;
use Panel\Db;
use Panel\Migrator;
use Panel\Rbac;

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function prompt(string $question, bool $hidden = false): string
{
    fwrite(STDOUT, $question);
    if ($hidden && DIRECTORY_SEPARATOR !== '\\') {
        shell_exec('stty -echo');
        $value = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
        return $value;
    }
    return trim((string) fgets(STDIN));
}

function createSuperAdmin(): void
{
    $existing = (int) Db::value('SELECT COUNT(*) FROM users WHERE role = ?', [Rbac::SUPER_ADMIN]);
    if ($existing > 0) {
        out("⚠ Zaten {$existing} süper admin hesabı var.");
        if (mb_strtolower(prompt('Yine de yeni bir tane oluşturulsun mu? (e/h) ')) !== 'e') {
            return;
        }
    }

    $name  = prompt('Ad Soyad: ');
    $email = mb_strtolower(prompt('E-posta: '));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        out('✗ Geçersiz e-posta.');
        exit(1);
    }
    if (Db::value('SELECT id FROM users WHERE email = ?', [$email])) {
        out('✗ Bu e-posta zaten kayıtlı.');
        exit(1);
    }

    $password = prompt('Şifre: ', true);
    $confirm  = prompt('Şifre (tekrar): ', true);
    if ($problem = Auth::passwordProblem($password, $confirm)) {
        out('✗ ' . $problem);
        exit(1);
    }

    Db::run(
        'INSERT INTO users (email, password_hash, full_name, role, status, created_at)
         VALUES (?, ?, ?, ?, \'active\', NOW())',
        [$email, password_hash($password, PASSWORD_DEFAULT), $name, Rbac::SUPER_ADMIN]
    );

    out("✓ Süper admin oluşturuldu: {$email}");
}

try {
    if (in_array('--create-admin', array_slice($argv, 1), true)) {
        createSuperAdmin();
        exit(0);
    }

    $ran = Migrator::run();
    out($ran === [] ? '✓ Şema güncel, uygulanacak migration yok.' : '✓ Uygulandı: ' . implode(', ', $ran));

    if ((int) Db::value('SELECT COUNT(*) FROM users') === 0) {
        out('');
        out('Hiç kullanıcı yok. İlk süper admini oluşturmak için:');
        out('  php panel/migrations/migrate.php --create-admin');
        out('veya tarayıcıdan: /panel/kurulum');
    }
} catch (Throwable $e) {
    out('✗ Hata: ' . $e->getMessage());
    exit(1);
}
