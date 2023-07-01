<?php
/*
Скрипт надо запускать от юзера - владельца сайта, чтобы созданные файлы пакетов не принадлежали юзеру root
$ sudo -u USERNAME php /var/www/USERNAME/packages.php /var/www/USERNAME/www/

Или от root, а после выставить владельца:
$ php /root/scripts/modx/packages.php /var/www/USERNAME/www/ && /var/www/USERNAME/chmod

Чтобы запустить из веба, просто положите скрипт в корень или куда-нибудь глубже и вызовите по HTTP
*/

// >> Список репозиториев с компонентами для установки

$modstoreKey = "";
$modstoreUser = "54@5444.ru";


// Check appstore keys
if ($modstoreKey == "" || $modstoreUser == "") {
    exit("Ошибка: не указан ключ или пользователь modstore!");
}

$providers = array(
    array(
        'name'		=> 'modx.com',
        'service_url'	=> 'https://rest.modx.com/extras/',
        'username'	=> '',
        'api_key'	=> '',
        'packages'	=> array(
            'formit',
            'MIGX',
            'TinyMCE Rich Text Editor',
            'simpleUpdater',
            'phpThumbOn',
            'QuickEmail',
            'reCaptchaV3',
            'translit'
        ),
    ),
    array(
        'name'		=> 'Modstore',
        'service_url'	=> 'https://modstore.pro/extras/',
        'username'	=> $modstoreUser,
        'api_key'	=> $modstoreKey,
        'packages'	=> array(
            'ace',
            'autoRedirector',
            'pdotools',
            'AjaxForm',
            'minifyx',
            'mixedImage'
        ),
    ),
);
// << Список репозиториев с компонентами для установки


$countInstalled = 0;

$console = !empty($argv) ? true : false; // Узнаём из консоли запустили или из веба

if( $console )
{
    if( !empty($argv[1]) && file_exists( $argv[1] .'index.php' ) )
    {
        $current_dir = $argv[1];
    }
}


// >> Подключаем
define('MODX_API_MODE', true);

$current_dir = !empty($current_dir) ? $current_dir : dirname(__FILE__) .'/';
$index_php = $current_dir .'index.php';

$i=0;
while( !file_exists( $index_php ) && $i < 9 )
{
    $current_dir = dirname(dirname($index_php)) .'/';
    $index_php = $current_dir .'index.php';
    $i++;
}

if( file_exists($index_php) )
{
    require_once $index_php;
}

if( !is_object($modx) )
{
    _print('ERROR: Не удалось подгрузить MODX');
    die;
}
// << Подключаем


// >> Включаем обработку ошибок
$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
// << Включаем обработку ошибок


$modx->switchContext('mgr');
$modx->addPackage('modx.transport', MODX_CORE_PATH .'model/');

$modx->getVersionData();
$productVersion = $modx->version['code_name'] .'-'. $modx->version['full_version'];

foreach( $providers as $prov )
{
    if( !$provider = $modx->getObject('transport.modTransportProvider', array('service_url' => $prov['service_url']) ) )
    {
        $provider = $modx->newObject('transport.modTransportProvider');

        $provider->fromArray($prov);
        $provider->save();
    }

    $provider->getClient();

    foreach( $prov['packages'] as $packageName )
    {
        $response = $provider->request('package', 'GET',
            array(
                'query' => $packageName
            ));

        if( !empty($response) )
        {
            $foundPackages = simplexml_load_string($response->response);

            if( $foundPackages['total'] > 0 )
            {
                foreach( $foundPackages as $foundPackage )
                {

                    if( strtolower((string)$foundPackage->name) == strtolower($packageName) )
                    {
                        if( !$modx->getCount('transport.modTransportPackage', array('signature' => (string)$foundPackage->signature)) )
                        {
                            $sig = explode('-', $foundPackage->signature);
                            $versionSignature = explode('.', $sig[1]);

                            file_put_contents( MODX_CORE_PATH .'packages/'. $foundPackage->signature .'.transport.zip', file_get_contents($foundPackage->location) );

                            $package = $modx->newObject('transport.modTransportPackage');

                            $package->set('signature', $foundPackage->signature);

                            $package->fromArray(
                                array(
                                    'created'	=> date('Y-m-d h:i:s'),
                                    'updated'	=> null,
                                    'state'		=> 1,
                                    'workspace'	=> 1,
                                    'provider'	=> $provider->id,
                                    'source'	=> $foundPackage->signature .'.transport.zip',
                                    'package_name'	=> (string)$foundPackage->name,
                                    'version_major'	=> $versionSignature[0],
                                    'version_minor'	=> !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                                    'version_patch'	=> !empty($versionSignature[2]) ? $versionSignature[2] : 0,
                                ));

                            if( !empty($sig[2]) )
                            {
                                $r = preg_split('/([0-9]+)/', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);

                                if( is_array($r) && !empty($r) )
                                {
                                    $package->set('release', $r[0]);
                                    $package->set('release_index', (isset($r[1]) ? $r[1] : '0'));
                                }
                                else {
                                    $package->set('release', $sig[2]);
                                }

                                if( $success = $package->save() )
                                {
                                    $package->install();
                                    _print('Installed: "'. (string)$foundPackage->name .'"');
                                    $countInstalled++;
                                }
                                else {
                                    _print('Could not save: "'. (string)$foundPackage->name .'"');
                                }
                            }
                        }
                        else {
                            _print('Already exists: "'. (string)$foundPackage->name .'"');
                        }
                    }
                }
            }
            else {
                _print('Not found: "'. $packageName .'"');
            }
        }
    }
}

if( $countInstalled > 0 )
{
    _print('Done!', 0);
}


/* >> Функция принта для консоли и веба */
function _print( $str='', $br=true )
{
    global $console;

    if( empty($str) ) {return;}

    if( $console )
    {
        fwrite(STDOUT, $str . ($br ? "\n" : ""));
    }
    else {
        print $str . ($br ? "<br />" : "");
    }
}
/* << */

exit;
