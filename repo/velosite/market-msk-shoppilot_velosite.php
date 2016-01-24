<?
header("content-type:application/xml;charset=windows-1251");
error_reporting(E_ERROR);
ini_set("display_errors", 0);
require ($_SERVER['DOCUMENT_ROOT']."/wcm/config.php");
require ($_SERVER['DOCUMENT_ROOT']."/wcm/library/db.php");


$q ='
    SELECT DISTINCT c.`id` AS cid, c.`nameed` AS cnameed, c.`name` AS cname, c.pid as cpid
    FROM `tovary` t
    INNER JOIN `cats` c
        ON c.`id` = t.`pid`
        AND c.`market` = "1"
    WHERE t.`vis` = "1"
        AND (t.price>0)
        AND (t.`arch` = "0" OR t.`arch` IS NULL)
    ORDER by t.pid, t.id
    ';
$query = db_q($q);


$cats = Array();

while ($row = mysql_fetch_assoc($query)) {
    if (!isset($cats[$row['cid']])) {
        $cats[$row['cid']] = '<category id="'.$row['cid'].'">'.$row['cname'].'</category>';
    }
}

if ($row['pic']){
    $img = '<picture>'.$fileurl.$row['pic']."</picture>\r\n";
} else {
    $img = "";
}

$q = '
    SELECT
            tg.id_tovar_1c AS id_1c,
            tg.razmer_koles AS razmer_koles,
            tg.razmer_rami AS razmer_rami,
            tg.color AS color,
            tg.osnov_sklad AS o_sklad,
            tg.marketplace_sklad AS m_sklad,
            tg.tranzit_sklad AS t_sklad,
            t.import_id as group_id,
             t.`pic`,t.alias, t.pic2, t.`small`, t.`name`, t.`id`, t.`price`, t.`pid`, t.`year`,t.sklad_osnov, t.sklad_tranzit, t.sklad_marketplace,
            b.id as brand_id,b.`name` AS brand,b.alias AS balias,t.avail,
            c.`id` AS cid, c.`nameed` AS cnameed, c.`name` AS cname, c.pid as cpid
    FROM `tovary_grouped` tg
    INNER JOIN `tovary` t
        ON tg.tid = t.id
    INNER JOIN `brands` b
        ON b.`id` = t.`brand`
    INNER JOIN `cats` c
        ON c.`id` = t.`pid`
        AND c.`market` = "1"
    WHERE tg.id_tovar_1c IS NOT NULL
        AND t.`vis` = "1"
        AND (t.price>0)
        AND (t.`arch` = "0" OR t.`arch` IS NULL)
    ORDER by t.pid, t.id
';
$query = db_q($q);

$lastSiteId = null;
$lastParamsString = '';

$step = 0;
while($row = mysql_fetch_assoc($query)) {

    if ($row['pic']){
        $img = '<picture>'.$fileurl.$row['pic']."</picture>\r\n";
    }
    else{
        $img = "";
    }

    if(!empty($row['pic2'])){
        $minsize=600;
        $mini = explode(";",trim($row['pic2'],';'));
        $num=2;
        foreach($mini as $img_add){
            if($num<=10){
                list($w,$h,$type) = getimagesize($filepath.$img_add);
                if($w>= $minsize || $h >=$minsize){
                    $img.= '<picture>'.$fileurl.$img_add."</picture>\r\n";
                    $num++;
                }
            }
        }
    }
    $row['small'] = htmlspecialchars(strip_tags($row['small']));
    $row['name']  = htmlspecialchars($row['name']);

    switch($row['cpid']){
        case '2':
            $category = 'zapchasti';
            $brand = 'brand';
            $year='year';
            break;
        case '3':
            $category = 'aksessuary';
            $brand = 'brand';
            $year='year';
            break;
        default:
            $category = 'velosipedy';
            $brand = $row['balias'];
            $year=$row['year'];
            break;
    }

    $chassisSize = '<param name="Размер рамы">' . htmlspecialchars($row['razmer_rami']) . '</param>' . PHP_EOL;
    $wheelSize   = '<param name="Размер колес">' . htmlspecialchars($row['razmer_koles']) . '</param>' . PHP_EOL;
    $color       = '<param name="Цвет">' . htmlspecialchars($row['color']) . '</param>' . PHP_EOL;

    if (is_null($lastSiteId) || $lastSiteId != $row['id'] || $lastParamsString == '') {
        $lastParamsString = '';
        $pq = '
            SELECT
                `l`.`name` AS `name`,
                `v`.`value` AS `value`
            FROM `prop_values` AS v
            INNER JOIN `prop_list` l
                ON l.id = v.pid
            WHERE v.tid = ' . $row['id'];

        $pquery = db_q($pq);

        while ($prow = mysql_fetch_assoc($pquery)) {
            if ($prow['name'] != '' && !is_null($prow['name']) && $prow['value'] != '' && !is_null($prow['value'])) {
                $lastParamsString .= '<param name="' . htmlspecialchars($prow['name'], ENT_COMPAT | ENT_HTML401, 'cp1251') . '">' . htmlspecialchars($prow['value'], ENT_COMPAT | ENT_HTML401, 'cp1251') . '</param>' . PHP_EOL;
            }
        }
    }

    $available = ($row['o_sklad'] + $row['t_sklad'] + $row['m_sklad'] > 0) ? 'true' : 'false';
    $availableInEShop = ($row['o_sklad'] + $row['t_sklad'] + $row['m_sklad'] > 0) ? 'true' : 'false';
    $availableInBrinkAndMortar = $row['m_sklad'] > 0 ? 'true' : 'false';

    $offers.="<offer id='{$row['id_1c']}' groupId='{$row['group_id']}' type='vendor.model' available='{$available}'>
            <url>http://www.velosite.ru/catalog/{$category}/{$brand}/{$year}/{$row['alias']}/?f=yama&amp;utm_source=Yandex.Market&amp;utm_medium=cpc&amp;utm_campaign=Market_MSK&amp;utm_content={$row['id']}</url>
            <price>".(int) $row['price']."</price>
            <currencyId>RUR</currencyId>
            <categoryId>{$row['pid']}</categoryId>
            {$img}
            <store>{$availableInEShop}</store>
            <pickup>{$availableInBrinkAndMortar}</pickup>
            <delivery>".(($row['sklad_osnov']+$row['sklad_tranzit']+$row['sklad_marketplace'])>0?'true':'false')."</delivery>
            <local_delivery_cost>0</local_delivery_cost>
            <typePrefix>".($category=='velosipedy' ? 'Велосипед':'')."</typePrefix>
            <vendor>{$row['brand']}</vendor>
            <model>{$row['name']} ". (!empty($row['year'])?' ('.$row['year'].')':'')."</model>
            <description>{$row['small']}</description>
            <year>{$row['year']}</year>
            {$chassisSize}
            {$wheelSize}
            {$color}
            {$lastParamsString}
    </offer>";
}


    

echo "<?xml version=\"1.0\" encoding=\"windows-1251\" ?>\n";
echo "<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n"?>
<yml_catalog date="<?=date('Y-m-d H:i:s')?>">
    <shop>
        <name>VeloSite.ru</name>
        <company>VeloSite</company>
        <url>http://www.velosite.ru/</url>
        <currencies>
            <currency id="RUR" rate="1" />
        </currencies>
        <categories>
        <? echo  join("\n",$cats)?>
	</categories>
      <offers>
	<? echo $offers ?>
      </offers>
    </shop>
</yml_catalog>
