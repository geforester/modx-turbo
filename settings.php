<?php

//User's keys

$yandexKey = ""; // Yandex Translation key
$smtpUser = ""; // Email for sending by SMTP
$smtpPass = ""; // Password

require_once dirname(__FILE__).'/config.core.php';
include_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
header("Content-type: text/html");



// *************************************************************************************
echo "<br> Working on settings …<br>";
$settings = array(
	  'cultureKey' => 'ru'
	, 'fe_editor_lang' => 'ru'
	, 'publish_default' => 1
	, 'tvs_below_content' => 1
	, 'upload_maxsize' => '10485760'
	, 'manager_lang_attribute' => 'ru'
	, 'manager_language' => 'ru'
    , 'error_page' => 2
    , 'cache_disabled' => 1
    , 'cache_resource' => 0
    , 'pdotools_fenom_modx' => 1
    , 'pdotools_fenom_parser' => 1
    , 'fastuploadtv.translit' => 1

    //mail
    , 'mail_smtp_auth' => 1
    , 'mail_smtp_hosts' => 'smtp.yandex.ru'
    , 'mail_smtp_port' => 465
    , 'mail_smtp_prefix' => 'ssl'
    , 'mail_use_smtp' => 1
    , 'mail_smtp_user' => $smtpUser
    , 'mail_smtp_pass' => $smtpPass
    , 'emailsender' => $smtpUser

	//url
	, 'automatic_alias' => 1
	, 'friendly_alias_restrict_chars_pattern' => '/[\0\x0B\t\n\r\f\a&=«»№+%$*!#()<>"~:`@\?\[\]\{\}\|\^\'\\\]/'
	, 'friendly_alias_realtime' => 1
	, 'friendly_urls' => 1
	, 'friendly_alias_translit' => 'russian'
    , 'use_alias_path' => 1


);
foreach ($settings as $k => $v) {
	$opt = $modx->getObject('modSystemSetting', array('key' => $k));
	if (!empty($opt)){
		$opt->set('value', $v);
		$opt->save();
      	echo 'edited '.$k.' = '.$v."<br>";
    } else {
    	$newOpt = $modx->newObject('modSystemSetting');
    	$newOpt->set('key', $k);
    	$newOpt->set('value', $v);
    	$newOpt->save();
    	echo 'added '.$k.' = '.$v."<br>";
    }
}

echo "Done!<br><br>";




// *************************************************************************************
echo "Working on  resources …<br>";
$resources = array(
	  array('pagetitle' => 'Страница не найдена',
			'template' => 1,
			'published' => 1,
			'hidemenu' => 1,
			'alias' => '404',
			'content_type' => 1,
			'richtext' => 1,
			'content' => '
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
</div>
			'
			)
	, array('pagetitle' => 'sitemap',
	  		'template' => 0,
	  		'published' => 1,
	  		'hidemenu' => 1,
	  		'alias' => 'sitemap',
            'content_type' => 2,
	  		'richtext' => 0,
	  		'content' =>'[[!pdoSitemap]]'
	  		)
	, array('pagetitle' => 'robots',
			'template' => 0,
			'published' => 1,
			'hidemenu' => 1,
			'alias' => 'robots',
			'content_type' => 3,
			'richtext' => 0,
			'content' => 'User-agent: * Disallow: /manager/ Disallow: /assets/components/ Allow: /assets/uploads/ Disallow: /core/ Disallow: /connectors/ Disallow: /index.php Disallow: /search Disallow: /profile/ Disallow: *? Host: [[++site_url]] Sitemap: [[++site_url]]sitemap.xml'
			)
);
foreach ($resources as $attr) {
	$response = $modx->runProcessor('resource/create', $attr);
}
echo "Done!<br><br>";


// *************************************************************************************
echo "Working on categories …<br>";
$categories = array(
    'SEO',
    'Template'
);

foreach ($categories as $category) {

    $cat = array(
        'category' => $category
    );

    $response = $modx->runProcessor('element/category/create', $cat);
    echo "created: {$cat['category']} <br>";
}

echo "Done! <br><br>";


// *************************************************************************************
echo "<br>Working on filesystem …<br>";
$folders = array(
    'assets/templates',
    'assets/templates/chunks',
    'assets/templates/css',
    'assets/templates/fonts',
    'assets/templates/img',
    'assets/templates/js',
    'assets/images'
);

foreach ($folders as $item) {
    mkdir($item, 0755);
}

$files = array(
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
    'assets/templates/base.html'
);

foreach ($files as $item) {
    file_put_contents($item,'', FILE_APPEND);
    echo "created {$item}<br>";
}

echo "Done!<br><br> ";


// *************************************************************************************
echo "Working on static chunks …<br> ";
$staticChunks = array(
     'head'
    ,'header'
    ,'footer'
    ,'modal'
    ,'slider.row'
    ,'news.row'
    ,'item.row'
    ,'emailTplCallback'
    ,'tpl.AjaxForm.callback'
);

foreach ($staticChunks as $item) {
    $staticChunksParam = array(
        'name' => $item,
        'source' => 1,
        'static' => '1',
        'static_file' => "/assets/templates/chunks/{$item}.html"
    );
    $response = $modx->runProcessor('element/chunk/create', $staticChunksParam);
    echo "created static chunk: {$item}<br>";
}

echo "Done!<br><br>";



/* *************************************************************************************
echo "Working on chunks …<br>";
$chunks = array(
    array('name' => 'childs',
        'description' => '',
        'snippet' => '<div class="childs">[[pdoMenu? &parents=`[[*id]]` &level=`1`]]</div>'
    )

);
foreach ($chunks as $attr) {
    $response = $modx->runProcessor('element/chunk/create', $attr);
    echo "created chunk: {$attr['name']} <br>";
}

echo "Done!<br><br>";

*/

// *************************************************************************************

echo "Working on templates …<br>";
$crtTemplates = $modx->runProcessor('element/template/create', array(
    'templatename' => 'main',
    'static' => '1',
    'static_file' => "/assets/templates/main.html"
));

echo "Done!<br><br>";



// *************************************************************************************

echo "Working on creating TVs …<br>";
$crttv = $modx->runProcessor('element/tv/create', array(
    	'name' => 'img',
    	'caption' => 'Изображение',
    	'type' => 'image',

));

$crttv = $modx->runProcessor('element/tv/create', array(
    'name' => 'address',
    'caption' => 'Адрес',
    'type' => 'text',
));

$crttv = $modx->runProcessor('element/tv/create', array(
    'name' => 'phone',
    'caption' => 'Телефон',
    'type' => 'text',
));

$crttv = $modx->runProcessor('element/tv/create', array(
    'name' => 'email',
    'caption' => 'Email',
    'type' => 'text',
));

$crttv = $modx->runProcessor('element/tv/create', array(
    'name' => 'seoDesc',
    'caption' => 'Описание страницы',
    'type' => 'text',
    'category' => '9'
));

$crttv = $modx->runProcessor('element/tv/create', array(
    'name' => 'seoKeywords',
    'caption' => 'Ключевые слова',
    'type' => 'text',
    'category' => '9'
));

echo "Done!<br><br>";



echo "All right! Let's go!";
$modx->runProcessor('system/clearcache');

