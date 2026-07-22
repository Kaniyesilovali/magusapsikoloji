<?php
/**
 * Panel yapılandırma örneği.
 *
 * Bu dosya bir ŞABLONDUR; olduğu yerde kullanılmaz. Kopyası daima panel/ dizininin
 * DIŞINDA durur — aksi hâlde `npm run build` onu _site/panel/ içine kopyalar ve
 * veritabanı şifresi yayına çıkar.
 *
 * ÜRETİM (cPanel):  ana dizin (public_html'in ÜSTÜ) → magusa-panel-config/config.php
 *                   yani /home/<sizin_cpanel_kullaniciniz>/magusa-panel-config/config.php
 * YEREL (php -S -t panel):  <repo>/magusa-panel-config/config.php   (.gitignore'da)
 *
 * Anahtar üretimi (yerelde bir kez):
 *     openssl rand -base64 32
 * ⚠ security.note_key kaybolursa şifreli seans notları KALICI olarak kurtarılamaz.
 *   Bu değeri parola yöneticisinde ayrıca yedekleyin ve sonradan değiştirmeyin.
 *
 * Dosya izni 0600 olmalı (cPanel → dosyayı seç → İzinler).
 */

return [
    'app' => [
        'name'     => 'Mağusa Psikoloji Yönetim Paneli',
        'url'      => 'https://magusapsikoloji.com/panel',
        'env'      => 'production',      // 'production' | 'local'
        'debug'    => false,             // üretimde daima false
        'timezone' => 'Europe/Nicosia',
    ],

    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'cpaneluser_panel',
        'user'    => 'cpaneluser_panel',
        'pass'    => 'BURAYA_VERITABANI_SIFRESI',
        'charset' => 'utf8mb4',
    ],

    // 32 baytlık rastgele anahtarların base64 hâli
    'security' => [
        'note_key'          => 'BURAYA_BASE64_ANAHTAR',  // seans notu şifreleme
        'session_lifetime'  => 1800,   // saniye — 30 dk hareketsizlik
        'max_login_attempts' => 5,
        'lockout_minutes'   => 15,
        'password_min'      => 10,
    ],

    'mail' => [
        'driver'    => 'smtp',          // 'smtp' | 'mail' | 'log'
        'from'      => 'panel@magusapsikoloji.com',
        'from_name' => 'Mağusa Psikoloji',
        'smtp' => [
            'host'       => 'mail.magusapsikoloji.com',
            'port'       => 465,
            'encryption' => 'ssl',      // 'ssl' | 'tls' | ''
            'user'       => 'panel@magusapsikoloji.com',
            'pass'       => 'BURAYA_EPOSTA_SIFRESI',
        ],
        // driver 'log' iken e-postalar bu dosyaya yazılır (yerel geliştirme)
        'log_path' => __DIR__ . '/mail.log',
    ],

    // Panelden site içeriği düzenlemek için. Boş bırakılırsa içerik ekranları
    // kurulum yönergesi gösterir, panelin geri kalanı etkilenmez.
    // Fine-grained token: yalnız bu depo + "Contents: Read and write".
    'github' => [
        'token'  => '',                                   // github_pat_...
        'repo'   => 'Kaniyesilovali/magusapsikoloji',
        'branch' => 'main',
    ],
];
