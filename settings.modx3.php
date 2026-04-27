<?php
/*
Начальная настройка сайта на MODX 3.x:
- системные настройки (язык, кеш, SMTP, ЧПУ);
- базовые ресурсы (404, sitemap, robots);
- категории, шаблон, TV-поля;
- структура каталога assets/templates с пустыми html-файлами;
- статические чанки, привязанные к этим файлам.

Запуск (от владельца сайта, чтобы созданные папки/файлы не принадлежали root):
$ sudo -u USERNAME php /var/www/USERNAME/www/setup.php

Скрипт идемпотентен: повторный запуск не дублирует настройки и не падает на существующих папках.
*/

use MODX\Revolution\modX;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modCategory;
use MODX\Revolution\modTemplate;

// >> User keys

$smtpUser = ''; // Email для отправки через SMTP
$smtpPass = ''; // Пароль SMTP

// << User keys


// >> Бутстрап MODX
require_once dirname(__FILE__) . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

if (!XPDO_CLI_MODE) {
    header('Content-type: text/html; charset=utf-8');
}
// << Бутстрап MODX


// >> Системные настройки
_log('Working on settings...');

$settings = [
    'cultureKey'                            => 'ru',
    'fe_editor_lang'                        => 'ru',
    'publish_default'                       => 1,
    'tvs_below_content'                     => 1,
    'upload_maxsize'                        => '10485760',
    'manager_lang_attribute'                => 'ru',
    'manager_language'                      => 'ru',
    'error_page'                            => 2,
    'cache_disabled'                        => 1,
    'cache_resource'                        => 0,
    'pdotools_fenom_modx'                   => 1,
    'pdotools_fenom_parser'                 => 1,
    'fastuploadtv.translit'                 => 1,

    // mail
    'mail_smtp_auth'                        => 1,
    'mail_smtp_hosts'                       => 'smtp.yandex.ru',
    'mail_smtp_port'                        => 465,
    'mail_smtp_prefix'                      => 'ssl',
    'mail_use_smtp'                         => 1,
    'mail_smtp_user'                        => $smtpUser,
    'mail_smtp_pass'                        => $smtpPass,
    'emailsender'                           => $smtpUser,

    // url
    'automatic_alias'                       => 1,
    'friendly_alias_restrict_chars_pattern' => '/[\0\x0B\t\n\r\f\a&=«»№+%$*!#()<>"~:`@\?\[\]\{\}\|\^\'\\\]/',
    'friendly_alias_realtime'               => 1,
    'friendly_urls'                         => 1,
    'friendly_alias_translit'               => 'russian',
    'use_alias_path'                        => 1,
];

foreach ($settings as $key => $value) {
    /** @var modSystemSetting $opt */
    $opt = $modx->getObject(modSystemSetting::class, ['key' => $key]);
    if ($opt) {
        $opt->set('value', $value);
        $opt->save();
        _log('  edited ' . $key . ' = ' . $value);
    } else {
        $opt = $modx->newObject(modSystemSetting::class);
        $opt->set('key', $key);
        $opt->set('value', $value);
        $opt->save();
        _log('  added ' . $key . ' = ' . $value);
    }
}
_log('Done!');
// << Системные настройки


// >> Категории (создаём первыми — id SEO нужен для TV ниже)
_log('Working on categories...');

$categoryIds = [];
foreach (['SEO', 'Template'] as $catName) {
    /** @var modCategory $existing */
    $existing = $modx->getObject(modCategory::class, ['category' => $catName]);
    if ($existing) {
        $categoryIds[$catName] = (int)$existing->get('id');
        _log('  exists: ' . $catName . ' (id=' . $categoryIds[$catName] . ')');
        continue;
    }

    $response = $modx->runProcessor('element/category/create', ['category' => $catName]);
    if ($response->isError()) {
        _log('  ERROR creating "' . $catName . '": ' . $response->getMessage());
        continue;
    }
    $obj = $response->getObject();
    $categoryIds[$catName] = (int)($obj['id'] ?? 0);
    _log('  created: ' . $catName . ' (id=' . $categoryIds[$catName] . ')');
}
_log('Done!');
// << Категории


// >> Файловая структура (относительно MODX_BASE_PATH)
_log('Working on filesystem...');

$folders = [
    'assets/templates',
    'assets/templates/chunks',
    'assets/templates/css',
    'assets/templates/fonts',
    'assets/templates/img',
    'assets/templates/js',
    'assets/images',
];

foreach ($folders as $rel) {
    $abs = MODX_BASE_PATH . $rel;
    if (is_dir($abs)) {
        _log('  exists: ' . $rel);
        continue;
    }
    if (mkdir($abs, 0755, true)) {
        _log('  created: ' . $rel);
    } else {
        _log('  ERROR creating: ' . $rel);
    }
}

$files = [
    'assets/templates/chunks/head.html',
    'assets/templates/chunks/header.html',
    'assets/templates/chunks/footer.html',
    'assets/templates/chunks/modal.html',
    'assets/templates/chunks/slider.row.html',
    'assets/templates/chunks/news.row.html',
    'assets/templates/chunks/item.row.html',
    'assets/templates/chunks/emailTplCallback.html',
    'assets/templates/chunks/tpl.AjaxForm.callback.html',
    'assets/templates/main.html',
    'assets/templates/base.html',
];

foreach ($files as $rel) {
    $abs = MODX_BASE_PATH . $rel;
    if (file_exists($abs)) {
        _log('  exists: ' . $rel);
        continue;
    }
    if (touch($abs)) {
        _log('  created: ' . $rel);
    } else {
        _log('  ERROR creating: ' . $rel);
    }
}
_log('Done!');
// << Файловая структура


// >> Шаблон (создаём ДО ресурсов — нужен id для 404)
_log('Working on template...');

$templateId = 0;
/** @var modTemplate $existingTpl */
$existingTpl = $modx->getObject(modTemplate::class, ['templatename' => 'MainTemplate']);
if ($existingTpl) {
    $templateId = (int)$existingTpl->get('id');
    _log('  exists: MainTemplate (id=' . $templateId . ')');
} else {
    $response = $modx->runProcessor('element/template/create', [
        'templatename' => 'MainTemplate',
        'static'       => 1,
        'static_file'  => '/assets/templates/main.html',
        'source'       => 1,
        'category'     => $categoryIds['Template'] ?? 0,
    ]);
    if ($response->isError()) {
        _log('  ERROR creating MainTemplate: ' . $response->getMessage());
    } else {
        $obj = $response->getObject();
        $templateId = (int)($obj['id'] ?? 0);
        _log('  created: MainTemplate (id=' . $templateId . ')');
    }
}
_log('Done!');
// << Шаблон


// >> Ресурсы (404, sitemap, robots)
_log('Working on resources...');

$resources = [
    [
        'pagetitle'    => 'Страница не найдена',
        'template'     => $templateId,
        'published'    => 1,
        'hidemenu'     => 1,
        'alias'        => '404',
        'content_type' => 1,
        'richtext'     => 1,
        'content'      => '
<div style="width: 500px; margin: -30px auto 0; overflow: hidden;padding-top: 25px;">
    <div style="float: left; width: 100px; margin-right: 50px; font-size: 75px;margin-top: 45px;">404</div>
    <div style="float: left; width: 350px; padding-top: 30px; font-size: 14px;">
        <h2>Страница не найдена</h2>
        <p style="margin: 8px 0 0;">Страница, на которую вы зашли, вероятно, была удалена с сайта, либо ее здесь никогда не было.</p>
        <p style="margin: 8px 0 0;">Возможно, вы ошиблись при наборе адреса или перешли по неверной ссылке.</p>
        <h3 style="margin: 15px 0 0;">Что делать?</h3>
        <ul style="margin: 5px 0 0 15px;">
            <li>проверьте правильность написания адреса,</li>
            <li>перейдите на <a href="[[++site_url]]">главную страницу</a> сайта,</li>
            <li>или <a href="javascript:history.go(-1);">вернитесь на предыдущую страницу</a>.</li>
        </ul>
    </div>
</div>',
    ],
    [
        'pagetitle'    => 'sitemap',
        'template'     => 0,
        'published'    => 1,
        'hidemenu'     => 1,
        'alias'        => 'sitemap',
        'content_type' => 2,
        'richtext'     => 0,
        'content'      => '[[!pdoSitemap]]',
    ],
    [
        'pagetitle'    => 'robots',
        'template'     => 0,
        'published'    => 1,
        'hidemenu'     => 1,
        'alias'        => 'robots',
        'content_type' => 3,
        'richtext'     => 0,
        'content'      => 'User-agent: * Disallow: /manager/ Disallow: /assets/components/ Allow: /assets/uploads/ Disallow: /core/ Disallow: /connectors/ Disallow: /index.php Disallow: /search Disallow: /profile/ Disallow: *? Host: [[++site_url]] Sitemap: [[++site_url]]sitemap.xml',
    ],
];

foreach ($resources as $attr) {
    $response = $modx->runProcessor('resource/create', $attr);
    if ($response->isError()) {
        _log('  ERROR creating "' . $attr['pagetitle'] . '": ' . $response->getMessage());
    } else {
        $obj = $response->getObject();
        _log('  created: ' . $attr['pagetitle'] . ' (id=' . ($obj['id'] ?? '?') . ')');
    }
}
_log('Done!');
// << Ресурсы


// >> Статические чанки
_log('Working on static chunks...');

$staticChunks = [
    'head',
    'header',
    'footer',
    'modal',
    'slider.row',
    'news.row',
    'item.row',
    'emailTplCallback',
    'tpl.AjaxForm.callback',
];

foreach ($staticChunks as $name) {
    $response = $modx->runProcessor('element/chunk/create', [
        'name'        => $name,
        'source'      => 1,
        'static'      => 1,
        'static_file' => '/assets/templates/chunks/' . $name . '.html',
    ]);
    if ($response->isError()) {
        _log('  ERROR creating "' . $name . '": ' . $response->getMessage());
    } else {
        _log('  created: ' . $name);
    }
}
_log('Done!');
// << Статические чанки


// >> TV-поля
_log('Working on TVs...');

$tvs = [
    ['name' => 'img',         'caption' => 'Изображение',       'type' => 'image', 'category' => 0],
    ['name' => 'address',     'caption' => 'Адрес',             'type' => 'text',  'category' => 0],
    ['name' => 'phone',       'caption' => 'Телефон',           'type' => 'text',  'category' => 0],
    ['name' => 'email',       'caption' => 'Email',             'type' => 'text',  'category' => 0],
    ['name' => 'seoDesc',     'caption' => 'Описание страницы', 'type' => 'text',  'category' => $categoryIds['SEO'] ?? 0],
    ['name' => 'seoKeywords', 'caption' => 'Ключевые слова',    'type' => 'text',  'category' => $categoryIds['SEO'] ?? 0],
];

foreach ($tvs as $tv) {
    $response = $modx->runProcessor('element/tv/create', $tv);
    if ($response->isError()) {
        _log('  ERROR creating "' . $tv['name'] . '": ' . $response->getMessage());
    } else {
        _log('  created: ' . $tv['name']);
    }
}
_log('Done!');
// << TV-поля


// >> Очистка кеша
_log('Clearing cache...');
$modx->cacheManager->refresh();
_log('Done!');
// << Очистка кеша


_log('All right! Let\'s go!');


/* >> Логирование для CLI и веба */
function _log($str)
{
    if (XPDO_CLI_MODE) {
        fwrite(STDOUT, $str . "\n");
    } else {
        print $str . '<br>';
    }
}
/* << */
