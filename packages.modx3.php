<?php
/*
Массовая установка пакетов из modx.com и Modstore для MODX 3.x

Запуск от владельца сайта (чтобы файлы пакетов не принадлежали root):
$ sudo -u USERNAME php /var/www/USERNAME/packages.php /var/www/USERNAME/www/

Или из веба — положить в корень и открыть по HTTP.
*/

use MODX\Revolution\Transport\modTransportProvider;
use MODX\Revolution\Transport\modTransportPackage;

// >> Настройки

$modstoreKey  = '';
$modstoreUser = '54@5444.ru';

if ($modstoreKey === '' || $modstoreUser === '') {
    exit('Ошибка: не указан ключ или пользователь modstore!');
}

$providers = [
    [
        'name'        => 'modx.com',
        'service_url' => 'https://rest.modx.com/extras/',
        'username'    => '',
        'api_key'     => '',
        'packages'    => [
            'mixedImage',
            'TinyMCE Rich Text Editor',
            'MIGX',
            'QuickEmail',
            'translit',
            'formit',
            'autoRedirector',
        ],
    ],
    [
        'name'        => 'Modstore',
        'service_url' => 'https://modstore.pro/extras/',
        'username'    => $modstoreUser,
        'api_key'     => $modstoreKey,
        'packages'    => [
            'ace',
            'pdotools',
            'AjaxForm',
            'minifyx',
            'reCaptchaV3',
            'phpThumbOn',
        ],
    ],
];
// << Настройки


$console = !empty($argv);

// >> Поиск index.php и подгрузка MODX

if ($console && !empty($argv[1]) && file_exists($argv[1] . 'index.php')) {
    $current_dir = $argv[1];
} else {
    $current_dir = dirname(__FILE__) . '/';
}

$index_php = $current_dir . 'index.php';

$i = 0;
while (!file_exists($index_php) && $i < 9) {
    $current_dir = dirname(dirname($index_php)) . '/';
    $index_php   = $current_dir . 'index.php';
    $i++;
}

define('MODX_API_MODE', true);

if (file_exists($index_php)) {
    require_once $index_php;
}

if (!is_object($modx)) {
    _print('ERROR: Не удалось подгрузить MODX');
    die;
}
// << Подгрузка MODX


// >> Логирование
$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
// <<

$modx->switchContext('mgr');

$countInstalled = 0;

foreach ($providers as $prov) {
    /** @var modTransportProvider $provider */
    $provider = $modx->getObject(modTransportProvider::class, ['service_url' => $prov['service_url']]);

    if (!$provider) {
        $provider = $modx->newObject(modTransportProvider::class);
        $provider->fromArray($prov);
        $provider->save();
    }

    $provider->getClient();

    foreach ($prov['packages'] as $packageName) {
        // Modstore чувствителен к регистру в query — посылаем lowercase
        $found = $provider->find([
            'query' => strtolower($packageName),
            'limit' => 50,
        ]);

        // Modstore возвращает [0 => total, 1 => packages].
        // На случай других провайдеров поддерживаем и формат ['results' => [...]].
        $results = [];
        if (is_array($found)) {
            if (isset($found[1]) && is_array($found[1])) {
                $results = $found[1];
            } elseif (isset($found['results']) && is_array($found['results'])) {
                $results = $found['results'];
            }
        }

        if (empty($results)) {
            _print('Not found: "' . $packageName . '"');
            continue;
        }

        // Точное совпадение по name (без учёта регистра) —
        // find() возвращает пачку по подстроке, нужный пакет может быть не первым
        $match = null;
        foreach ($results as $pkg) {
            $name = is_array($pkg) ? ($pkg['name'] ?? null) : (is_object($pkg) ? ($pkg->name ?? null) : null);
            if ($name !== null && strtolower($name) === strtolower($packageName)) {
                $match = $pkg;
                break;
            }
        }

        if (!$match) {
            _print('Not found (no exact name match): "' . $packageName . '"');
            continue;
        }

        $signature = is_array($match) ? ($match['signature'] ?? null) : ($match->signature ?? null);

        if (!$signature) {
            _print('Bad response for: "' . $packageName . '"');
            continue;
        }

        if ($modx->getCount(modTransportPackage::class, ['signature' => $signature])) {
            _print('Already exists: "' . $packageName . '" (' . $signature . ')');
            continue;
        }

        /** @var modTransportPackage|false $package */
        $package = $provider->transfer($signature);

        if (!$package) {
            _print('Could not download: "' . $packageName . '" (' . $signature . ')');
            continue;
        }

        if ($package->install()) {
            _print('Installed: "' . $packageName . '" (' . $signature . ')');
            $countInstalled++;
        } else {
            _print('Could not install: "' . $packageName . '" (' . $signature . ')');
        }
    }
}

if ($countInstalled > 0) {
    _print('Done! Installed: ' . $countInstalled, false);
}


/* >> Принт для консоли и веба */
function _print($str = '', $br = true)
{
    global $console;

    if ($str === '') {
        return;
    }

    if ($console) {
        fwrite(STDOUT, $str . ($br ? "\n" : ''));
    } else {
        print $str . ($br ? '<br />' : '');
    }
}
/* << */

exit;
