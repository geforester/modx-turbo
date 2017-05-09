// Получение любого поля ресурса произвольного ресурса
{1 | resource : 'pagetitle'}

// Текущий год
{'' | date : 'Y'}

//Условие IF
{$id == '1' ? '' : $url}

//Получение изображения через переменную с точкой ($_pls)
{$_pls["tv.img"] | phpthumbon : "w=300&h=200&zc=1"}
//Без префикса
{$img | phpthumbon : "w=300&h=200&zc=1"}

//Информация о прозводителе
{$_modx->makeUrl($_pls['vendor.resource'])}
{$_pls['vendor.name']})
{$_modx->getPlaceholder('vendor.name')}

//Условие с массивом
{if $_modx->resource.template in [3, 4] }
{include 'myChank'}
{/if}
{$_modx->resource.template | in : [3, 4] ? '[[$myChunk]]' : ''}

//Вывод MIGX через fenom
//Текущего ресурса
{set $rows = json_decode($_modx->resource.tv_product, true)}
//Произвольного ресурса
{set $rows = json_decode( 1 | resource: 'tv_product', true)}
{foreach $rows as $row}
{$row.image}
{/foreach}

{set $rows = json_decode($_modx->resource.preimuschestva, true)}
{foreach $rows as $idx => $row}
{if $idx < 5}
<div class="feature">
    <div class="feature-image"><img src="{$row.img}" /></div>
    <h3>{$row.title}</h3>
    <p>{$row.introtext}</p>
</div>
{/if}
{/foreach}

//Модификатор дата
//Источник - https://modx.pro/components/7461-pdotools-version-2-of-2-c-modifiers-fenom/
{$_modx->resource.publishedon | date_format:"%d-%m-%Y %H:%M:%S"}