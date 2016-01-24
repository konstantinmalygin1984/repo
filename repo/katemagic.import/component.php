<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 09.01.16
 * Time: 16:28
 */

$sImportLog = $_SERVER['DOCUMENT_ROOT']."/upload/1c_catalog/import_log.txt";
$sImportArch = $_SERVER['DOCUMENT_ROOT']."/upload/1c_catalog/export1c.zip";
$sOldImportArch = $_SERVER['DOCUMENT_ROOT']."/upload/1c_catalog/old/";
$sErrorLog = $_SERVER['DOCUMENT_ROOT']."/upload/1c_catalog/error_log.txt";
$arHelper = array();

$fErrorLog = fopen($sErrorLog, 'a');
if( $fErrorLog===FALSE ) die();
$iCurFileTime = filemtime($sImportArch);

$arHelper = unserialize(file_get_contents($sImportLog));

if( htmlspecialchars($arHelper['sStep'])=='' )
{
    $arHelper['sStep']='parseOrders';
    $_SESSION['sStep'] = 'parseData';
}

if( $arHelper['inProgress']!='TRUE' )
{
    if( intval($arHelper['prevImportTime']) < $iCurFileTime )
    {
        $zip = new ZipArchive();
        if ($zip->open($sImportArch) === true)
        {
            $zip->extractTo($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/");
            $zip->close();
            $arHelper['inProgress'] = 'TRUE';
            $arHelper['curImportTime'] = $iCurFileTime;
            $arHelper['sStep'] = 'parseUsers';
            file_put_contents($sImportLog, serialize($arHelper));
            copy( $sImportArch , $sOldImportArch.'/export1c'.date("Y_m_d", $iCurFileTime).'.zip' );
        }
        else
        {
            fwrite($fErrorLog, 'Ошибка открытия архива'.PHP_EOL);
        }
    }
}

if( $arHelper['inProgress']=='TRUE' )
{
    if( file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/Users.xml") && ($arHelper['sStep']=='addUsers' || $arHelper['sStep']=='parseUsers' ) )
    {
        $obImportHelper = new CImportKatemagic("Users.xml");


        if (!$obImportHelper->Process())
        {
            fwrite($fErrorLog, $obImportHelper->LAST_ERROR.PHP_EOL);
        }
        elseif($obImportHelper->step!="addCatStructs")
        {
            $arHelper['sStep'] = 'addUsers';
            file_put_contents($sImportLog, serialize($arHelper));
        }
        else
        {
            $_SESSION['sStep'] = 'parseData';
            $arHelper['sStep'] = 'parseCatStruct';
            echo "success Users<br/>";
        }
    }

    /* Catalog structure */

    if( file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/CatalogStructures.xml") && ($arHelper['sStep']=='addCatStructs' || $arHelper['sStep']=='parseCatStruct') )
    {
        $obImportHelper = new CImportKatemagic("CatalogStructures.xml");

        if (!$obImportHelper->Process())
        {
            fwrite($fErrorLog, $obImportHelper->LAST_ERROR.PHP_EOL);
        }
        elseif($obImportHelper->step!="addCatalogs")
        {
            $arHelper['sStep'] = 'addCatStructs';
            file_put_contents($sImportLog, serialize($arHelper));
        }
        else
        {
            $_SESSION['sStep'] = 'parseData';
            $arHelper['sStep'] = 'parseCatalog';
            file_put_contents($sImportLog, serialize($arHelper));
            echo "success CatalogStructures<br/>";
        }
    }

    /* Catalog elements */

    if( file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/Catalogs.xml") && ($arHelper['sStep']=='addCatalogs' || $arHelper['sStep']=='parseCatalog') )
    {
        $obImportHelper = new CImportKatemagic("Catalogs.xml");

        if (!$obImportHelper->Process())
        {
            fwrite($fErrorLog, $obImportHelper->LAST_ERROR.PHP_EOL);
        }
        elseif($obImportHelper->step!="addOrders")
        {
            $arHelper['sStep'] = 'addCatalogs';
            file_put_contents($sImportLog, serialize($arHelper));
        }
        else
        {
            $_SESSION['sStep'] = 'parseData';
            $arHelper['sStep'] = 'parseOrders';
            file_put_contents($sImportLog, serialize($arHelper));
            echo "success CatalogStructures<br/>";
        }
    }

    /* Orders */

    if( file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/Orders.xml") && ($arHelper['sStep']=='addOrders' || $arHelper['sStep']=='parseOrders') )
    {
        $obImportHelper = new CImportKatemagic("Orders.xml");

        if (!$obImportHelper->Process())
        {
            fwrite($fErrorLog, $obImportHelper->LAST_ERROR.PHP_EOL);
        }
        elseif($obImportHelper->step!="addSchedules")
        {
            $arHelper['sStep'] = 'addOrders';
            file_put_contents($sImportLog, serialize($arHelper));
        }
        else
        {
            $_SESSION['sStep'] = 'parseData';
            $arHelper['sStep'] = 'parseShedules';
            file_put_contents($sImportLog, serialize($arHelper));
            echo "success Orders<br/>";
        }
    }

    /* Shedules */

    if( file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/schedules.xml") && ($arHelper['sStep']=='addShedules' || $arHelper['sStep']=='parseShedules') )
    {
        $obImportHelper = new CImportKatemagic("schedules.xml");

        if (!$obImportHelper->Process())
        {
            fwrite($fErrorLog, $obImportHelper->LAST_ERROR.PHP_EOL);
        }
        elseif($obImportHelper->step!="addMasterstats")
        {
            $arHelper['sStep'] = 'addShedules';
            file_put_contents($sImportLog, serialize($arHelper));
            echo '<br/>component max_execution_time = '.ini_get('max_execution_time');
        }
        else
        {
            $_SESSION['sStep'] = 'parseData';
            $arHelper['sStep'] = 'parseMasterstats';
            file_put_contents($sImportLog, serialize($arHelper));
            echo "success Shedules<br/>";
        }
    }

    /* Masterstats */

    if( file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/masterstats.xml") && ($arHelper['sStep']=='addMasterstats' || $arHelper['sStep']=='parseMasterstats') )
    {
        $obImportHelper = new CImportKatemagic("masterstats.xml");

        if (!$obImportHelper->Process())
        {
            fwrite($fErrorLog, $obImportHelper->LAST_ERROR.PHP_EOL);
        }
        elseif($obImportHelper->step!="done")
        {
            $arHelper['sStep'] = 'addMasterstats';
            file_put_contents($sImportLog, serialize($arHelper));
        }
        else
        {
            $_SESSION['sStep'] = 'done';
            $arHelper['sStep'] = 'done';
            file_put_contents($sImportLog, serialize($arHelper));
            echo "success Masterstats<br/>";
        }
    }

    if( $obImportHelper->step=='done' )
    {
        $arHelper['inProgress'] = 'FALSE';
        $arHelper['prevImportTime']= $iCurFileTime;
        unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/Users.xml");
        unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/CatalogStructures.xml");
        unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/Catalogs.xml");
        unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/Orders.xml");
        unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/schedules.xml");
        unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c_catalog/unzip/masterstats.xml");
    }

    file_put_contents($sImportLog, serialize($arHelper));
}

fclose($fErrorLog);

?>