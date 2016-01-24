<?
use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


class CImportKatemagic
{
    var $sXmlDataFile;
    var $sDir;
    var $LAST_ERROR = '';
    var $step = "";
    //var $sRelDir = "/upload/users_exchange/";
    var $sRelDir = "/upload/1c_catalog/unzip/";
    var $arProperties = Array();
    var $aHelp = array();
    var $PersonTypeID = 2;
    var $iMax_exec_time;

    // \Bitrix\Main\Loader::IncludeModule("iblock");

    //public function __construct($sXmlUsersFile, $sXmlCatalogFile, $sXmlOrdersFile, $sXmlSheduleFile, $sXmlMasterstatFile)
    public function __construct($sXmlDataFile)
    {
        $this->sDir = $_SERVER["DOCUMENT_ROOT"].$this->sRelDir;
        CheckDirPath($this->sDir);
        $this->sXmlDataFile = $this->sDir.$sXmlDataFile;
        if( intval(ini_get('max_execution_time'))>0 )
        {
            $this->iMax_exec_time = ini_get('max_execution_time')*0.9;
        }
        else
        {
            $this->iMax_exec_time = 160;
        }
    }
    
    
    public function Process()
    {
        if (!empty($this->LAST_ERROR)) echo $this->LAST_ERROR;

        if (!$_SESSION['sStep'])
            $_SESSION['sStep'] = 'parseData';
        $this->step=&$_SESSION["sStep"];

        switch ($_SESSION['sStep'])
        {
            case 'parseData':
                return $this->ParseXmlFileData($this->sXmlDataFile);
                break;
            case 'addUsers':
                return $this->AddUsers();
                break;
            case 'addCatStructs':
                return $this->AddCatStructs();
                break;
            case 'addCatalogs':
                return $this->AddCatalogs();
                break;
            case 'addOrders':
                return $this->AddOrders();
                break;
            case 'addSchedules':
                return $this->AddShedules();
                break;
            case 'addMasterstats':
                return $this->AddMasterstats();
                break;
        }
    }

    public function ParseXmlFileData($xml)
    {
        $obReader = new XMLReader;
        $obReader->open($xml, "utf-8");
        if (!$obReader)
        {
            $this->LAST_ERROR = "Ошибка чтения xml файла";
            return false;
        }
        $arDatum = array();
        $arCatStructs = array();
        $arCatalogs = array();
        $arOrders = array();
        $arSchedules = array();
        $arMasterstats = array();

        while ($obReader->read())
        {
            switch($obReader->nodeType)
            {
                case (XMLReader::ELEMENT):
                    // если users
                    if ($obReader->localName == 'Users')
                    {
                        $obReader->getAttribute("Full");
                        $arParams = Array("full"=>1);
                        if($obReader->getAttribute("Full")=="FALSE") $arParams = Array("full"=>0);
                        self::ArrayToFile($arParams, 'arParams', $this->sDir.'params.php');

                        $arUser = array();
                        $iCount=0;
                        while($obReader->read())
                        {
                            if ($obReader->nodeType == XMLReader::ELEMENT)
                            {
                                if ($obReader->localName == 'User')
                                {
                                    $arUser = array();
                                    //приходим в user
                                    $obReader->moveToNextAttribute();
                                    //берем атрибут id
                                    $arUser[$obReader->localName] = $obReader->value;
                                    while($obReader->read())
                                    {
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'User') break;
                                        if ($obReader->nodeType == XMLReader::ELEMENT)
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arUser[$sName] = $obReader->value;
                                        }
                                    }
                                }
                                //заносим user в массив $arUsers
                                $arDatum[$iCount] = $arUser;
                                $iCount++;
                            }
                        }
                        self::ArrayToFile($arDatum, 'arUsers', $this->sDir.'users.php');
                        $this->step = $_SESSION['sStep'] = 'addUsers';
                    }
                    /*** Catalog Structure ***/
                    if ($obReader->localName == 'CatalogStructures')
                    {
                        $obReader->getAttribute("Full");
                        $arParams = Array("full"=>1);
                        if($obReader->getAttribute("Full")=="FALSE") $arParams = Array("full"=>0);
                        self::ArrayToFile($arParams, 'arParams', $this->sDir.'params.php');

                        $arUser = array();
                        $iCount=0;
                        while($obReader->read())
                        {
                            if ($obReader->nodeType == XMLReader::ELEMENT)
                            {
                                if ($obReader->localName == 'CatalogStructure')
                                {
                                    $arCatStruct = array();
                                    //приходим в user
                                    $obReader->moveToNextAttribute();
                                    //берем атрибут id
                                    $arCatStruct[$obReader->localName] = $obReader->value;
                                    while($obReader->read())
                                    {
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'CatalogStructure') break;
                                        if ($obReader->nodeType == XMLReader::ELEMENT)
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arCatStruct[$sName] = $obReader->value;
                                        }
                                    }
                                }
                                //заносим user в массив $arCatStructs
                                $arCatStructs[$iCount] = $arCatStruct;
                                $iCount++;
                            }
                        }
                        self::ArrayToFile($arCatStructs, 'arCatStructs', $this->sDir.'catstruct.php');
                        $this->step = $_SESSION['sStep'] = 'addCatStructs';
                    }
                    /*** Catalog ***/
                    if ($obReader->localName == 'Catalogs')
                    {
                        $obReader->getAttribute("Full");
                        $arParams = Array("full"=>1);
                        if($obReader->getAttribute("Full")=="FALSE") $arParams = Array("full"=>0);
                        self::ArrayToFile($arParams, 'arParams', $this->sDir.'params.php');

                        $iCount=0;
                        while($obReader->read())
                        {
                            if ($obReader->nodeType == XMLReader::ELEMENT)
                            {
                                if ($obReader->localName == 'Catalog')
                                {
                                    $arCat = array();
                                    //приходим в user
                                    $obReader->moveToNextAttribute();
                                    //берем атрибут id
                                    $arCat[$obReader->localName] = $obReader->value;
                                    while($obReader->read())
                                    {
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'Catalog') break;
                                        if ($obReader->nodeType == XMLReader::ELEMENT)
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arCat[$sName] = $obReader->value;
                                        }
                                    }
                                }
                                //заносим user в массив $arCatalogs
                                $arCatalogs[$iCount] = $arCat;
                                $iCount++;
                            }
                        }
                        self::ArrayToFile($arCatalogs, 'arCatalogs', $this->sDir.'catalogs.php');
                        $this->step = $_SESSION['sStep'] = 'addCatalogs';
                    }
                    /*** Orders ***/
                    if ($obReader->localName == 'Orders')
                    {
                        $obReader->getAttribute("Full");
                        $arParams = Array("full"=>1);
                        if($obReader->getAttribute("Full")=="FALSE") $arParams = Array("full"=>0);
                        self::ArrayToFile($arParams, 'arParams', $this->sDir.'params.php');

                        $iCount=0;
                        while($obReader->read())
                        {
                            if ($obReader->nodeType == XMLReader::ELEMENT)
                            {
                                if ($obReader->localName == 'Order')
                                {
                                    $arOrder = array();
                                    //приходим в Order
                                    //$obReader->moveToNextAttribute();
                                    $attr_id = $obReader->getAttribute("id");
                                    if( $attr_id !==NULL )
                                    {
                                        $arOrder[$obReader->localName] = $attr_id;//$obReader->value;
                                    }
                                    //берем атрибут id
                                    while($obReader->read())
                                    {
                                        if ($obReader->nodeType == 14) $obReader->read();
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'Order') break;
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'OrderItems') break;

                                        if ($obReader->nodeType == XMLReader::ELEMENT && ($obReader->localName == 'ClientId' || $obReader->localName == 'Date'))
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arOrder[$sName] = $obReader->value;
                                            $obReader->read();$obReader->read();
                                            $obReader->read();//echo $obReader->localName.' - '.$obReader->value;
                                            if ($obReader->nodeType == XMLReader::ELEMENT && ($obReader->localName == 'ClientId' || $obReader->localName == 'Date'))
                                            {
                                                //сохраняем значения
                                                $sName = $obReader->localName;
                                                $obReader->read();
                                                if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                    $arOrder[$sName] = $obReader->value;

                                                $obReader->read();$obReader->read();
                                            }
                                        }

                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'OrderItems' )
                                        {
                                            $arOrder['OrderItems'] = array();
                                        }
                                        $arOrderItem = array();

                                        while($obReader->read()) //идем по OrderItem
                                        {
                                            if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'OrderItem') break;
                                            if ($obReader->nodeType == XMLReader::ELEMENT && ($obReader->localName == 'CatalogId' || $obReader->localName == 'MasterId'|| $obReader->localName == 'Quantity' || $obReader->localName == 'Price' ) )
                                            {
                                                //сохраняем значения
                                                $sName = $obReader->localName;
                                                $obReader->read();
                                                if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                    $arOrderItem[$sName] = $obReader->value;
                                            }
                                        }
                                        $arOrder['OrderItems'][] = $arOrderItem;
                                    }
                                }
                                //заносим user в массив $arCatalogs
                                $arOrders[$iCount] = $arOrder;
                                $iCount++;
                            }
                        }
                        self::ArrayToFile($arOrders, 'arOrders', $this->sDir.'orders.php');
                        $this->step = $_SESSION['sStep'] = 'addOrders';
                    }
                    /*** Shedule ***/
                    if ($obReader->localName == 'Schedules')
                    {
                        $obReader->getAttribute("Full");
                        $arParams = Array("full"=>1);
                        if($obReader->getAttribute("Full")=="FALSE") $arParams = Array("full"=>0);
                        self::ArrayToFile($arParams, 'arParams', $this->sDir.'params.php');

                        $iCount=0;
                        while($obReader->read())
                        {
                            if ($obReader->nodeType == XMLReader::ELEMENT)
                            {
                                if ($obReader->localName == 'Schedule')
                                {
                                    $arSchedule = array();
                                    //приходим в Schedule
                                    //$obReader->moveToNextAttribute();
                                    $attr_id = $obReader->getAttribute("id");
                                    if( $attr_id !==NULL )
                                    {
                                        $arSchedule[$obReader->localName] = $attr_id;//$obReader->value;
                                    }
                                    //берем атрибут id
                                    while($obReader->read())
                                    {
                                        if ($obReader->nodeType == 14) $obReader->read();
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'Schedule') break;
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'OrderItems') break;

                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'Date')
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arSchedule[$sName] = $obReader->value;
                                            $obReader->read();
                                            $obReader->read();
                                            $obReader->read();
                                        }

                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'ClientId')
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arSchedule[$sName] = $obReader->value;
                                            $obReader->read();
                                            $obReader->read();
                                            $obReader->read();
                                        }
                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'MasterId')
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arSchedule[$sName] = $obReader->value;
                                            $obReader->read();
                                            $obReader->read();
                                            $obReader->read();
                                        }
                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'DateTimeFrom')
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arSchedule[$sName] = $obReader->value;
                                            $obReader->read();
                                            $obReader->read();
                                            $obReader->read();
                                        }
                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'DateTimeTo')
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arSchedule[$sName] = $obReader->value;
                                        }

                                        if ($obReader->nodeType == XMLReader::ELEMENT && $obReader->localName == 'OrderItems' )
                                        {
                                            $arSchedule['OrderItems'] = array();
                                        }
                                        $arOrderItem = array();

                                        while($obReader->read()) //идем по OrderItem
                                        {
                                            if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'OrderItem') break;
                                            if ($obReader->nodeType == XMLReader::ELEMENT && ($obReader->localName == 'CatalogId' || $obReader->localName == 'MasterId' || $obReader->localName == 'Price' ) )
                                            {
                                                //сохраняем значения
                                                $sName = $obReader->localName;
                                                $obReader->read();
                                                if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                    $arOrderItem[$sName] = $obReader->value;
                                            }
                                        }
                                        $arSchedule['OrderItems'][] = $arOrderItem;
                                    }
                                }
                                //заносим user в массив $arCatalogs
                                $arSchedules[$iCount] = $arSchedule;
                                $iCount++;
                            }
                        }
                        self::ArrayToFile($arSchedules, 'arSchedules', $this->sDir.'schedules.php');
                        $this->step = $_SESSION['sStep'] = 'addSchedules';
                    }
                    /*** Masterstat ***/
                    if ($obReader->localName == 'Masterstats')
                    {
                        $obReader->getAttribute("Full");
                        $arParams = Array("full"=>1);
                        if($obReader->getAttribute("Full")=="FALSE") $arParams = Array("full"=>0);
                        self::ArrayToFile($arParams, 'arParams', $this->sDir.'params.php');

                        $iCount=0;
                        while($obReader->read())
                        {
                            if ($obReader->nodeType == XMLReader::ELEMENT)
                            {
                                if ($obReader->localName == 'Masterstat')
                                {
                                    $arMasterstat = array();
                                    //приходим в user
                                    $obReader->moveToNextAttribute();
                                    //берем атрибут id
                                    $arMasterstat[$obReader->localName] = $obReader->value;
                                    while($obReader->read())
                                    {
                                        if ($obReader->nodeType == XMLReader::END_ELEMENT && $obReader->localName == 'Masterstat') break;
                                        if ($obReader->nodeType == XMLReader::ELEMENT)
                                        {
                                            //сохраняем значения
                                            $sName = $obReader->localName;
                                            $obReader->read();
                                            if ($obReader->nodeType !== XMLReader::END_ELEMENT)
                                                $arMasterstat[$sName] = $obReader->value;
                                        }
                                    }
                                }
                                //заносим user в массив $arUsers
                                $arMasterstats[$iCount] = $arMasterstat;
                                $iCount++;
                            }
                        }
                        self::ArrayToFile($arMasterstats, 'arMasterstats', $this->sDir.'masterstats.php');
                        $this->step = $_SESSION['sStep'] = 'addMasterstats';
                    }
                    break;
            }
        }
        /* if (empty($arUsers)) {
            $this->LAST_ERROR = "Ошибка парсинга xml файла пользователей";
            return false;
        } */
        return true;
    }


	public function IsFull()
	{
		$bFull = true;
		if(file_exists($this->sDir.'params.php'))
		{
			require $this->sDir.'params.php';
			if(!IntVal($arParams["full"])) $bFull=false;
		}
		return $bFull;
	}

    public function AddUsers()
    {
        $iStarttime = time();
        require $this->sDir.'users.php';
        /**
         * @var array $arUsers
         */
        if(self::IsFull())
        {
            foreach ($arUsers as $arUserId=>$arUser)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arUsers,'arUsers', $this->sDir.'users.php' );
                    return true;
                }
                $gender = 'F';
                if( $arUser['Gender']==0 ) {$gender='M';}
                $master = '';
                if( isset($arUser['MasterSpec']) && $arUser['MasterSpec']!='' ) {$master = $arUser['MasterSpec'];}

                if( htmlspecialchars($arUser['Email'])!='' )
                {
                    $sEmail = $sLogin = $arUser['Email'];
                }
                else
                {
                    $sLogin = substr(self::Translit($arUser['UserName']), 0, 30);
                    $sEmail = $sLogin.'@katemagic.ru';
                }
                $arFields = array(
                    "LOGIN" => $sLogin,
                    "NAME" => $arUser["UserName"],
                    "LAST_NAME" => $arUser["FamilyId"],
                    "EMAIL" => $sEmail,
                    "PERSONAL_PHONE" => $arUser["PhoneNumber"],
                    "PERSONAL_GENDER" => $gender,
                    "PERSONAL_PROFESSION" => $master,
                    "ACTIVE"=>"Y",
                    "PERSONAL_BIRTHDAY" => $arUser["BirthDate"],
                    "DATE_REGISTER" => $arUser['RegDate'],
                    "UF_USER_ID" => $arUser['id']
                );
                $arParams["SELECT"] = array("UF_*");
                $rsUsers = new CUser;
                $obRes = $rsUsers->GetList(($by="ID"), ($order="desc"), Array("UF_USER_ID"=>$arUser['id']), $arParams);
                if($arResult = $obRes->fetch())
                {
                    if ($arResult["UF_USER_ID"])
                    {
                        $res = $rsUsers->Update($arResult['ID'], $arFields);
                        if (intval($res) > 0)
                        {
                            unset($arUsers[$arUserId]);
                        }
                        else
                        {
                            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/1c_catalog/error_log.txt', print_r($arFields, TRUE).PHP_EOL, FILE_APPEND);
                            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/1c_catalog/error_log.txt', $rsUsers->LAST_ERROR.PHP_EOL, FILE_APPEND);
                            $this->LAST_ERROR = $rsUsers->LAST_ERROR." ".serialize($arUser);
                            //return false;
                        }
                    }
                }
                else
                {
                    $sPassword = randString(10);
                    $arFields["GROUP_ID"]=Array(5);
                    $arFields["PASSWORD"]=$arFields["CONFIRM_PASSWORD"]=$sPassword;
                    $res = $rsUsers->Add($arFields);
                    if (intval($res) > 0)
                    {
                        unset($arUsers[$arUserId]);
                    }
                    else
                    {
                        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/1c_catalog/error_log.txt', print_r($arFields, TRUE).PHP_EOL, FILE_APPEND);
                        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/1c_catalog/error_log.txt', $rsUsers->LAST_ERROR.PHP_EOL, FILE_APPEND);
                        $this->LAST_ERROR = $rsUsers->LAST_ERROR;
                        //return false;
                    }
                }
            }
        }
        else
        {
            echo "update users";
            foreach ($arUsers as $arUserId=>$arUser)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arUsers,'arUsers', $this->sDir.'users.php' );
                    return true;
                }
                $arFields = array();

                if( isset($arUser['UserName']) && $arUser['UserName']!='' ) {$arFields['NAME']=$arUser['UserName'];}
                if( isset($arUser['FamilyId']) && $arUser['FamilyId']!='' ) {$arFields['LAST_NAME']=$arUser['FamilyId'];}
                if( isset($arUser['Email']) && $arUser['Email']!='' ) {$arFields['EMAIL']=$arUser['Email'];}
                if( isset($arUser['PhoneNumber']) && $arUser['PhoneNumber']!='' ) {$arFields['PERSONAL_PHONE']=$arUser['PhoneNumber'];}
                if( isset($arUser['Gender']) && $arUser['Gender']!='' ) {$arFields['PERSONAL_GENDER']=$arUser['Gender'];}
                if( isset($arUser['MasterSpec']) && $arUser['MasterSpec']!='' ) {$arFields['PERSONAL_PROFESSION']=$arUser['MasterSpec'];}
                if( isset($arUser['BirthDate']) && $arUser['BirthDate']!='' ) {$arFields['PERSONAL_BIRTHDAY']=$arUser['BirthDate'];}
                if( isset($arUser['RegDate']) && $arUser['RegDate']!='' ) {$arFields['DATE_REGISTER']=$arUser['RegDate'];}
                if( isset($arUser['id']) && $arUser['id']!='' ) {$arFields['UF_USER_ID']=$arUser['id'];}

                $arParams["SELECT"] = array("UF_*");
                $rsUsers = new CUser;
                $obRes = $rsUsers->GetList($a,$b, Array("UF_USER_ID"=>$arUser['id']), $arParams);
                if($arResult = $obRes->fetch())
                {
                    if ($arResult["UF_USER_ID"])
                    {
                        $res = $rsUsers->Update($arResult['ID'], $arFields);
                        if (intval($res) > 0)
                        {
                            unset($arUsers[$arUserId]);
                        }
                        else
                        {
                            $this->LAST_ERROR = $rsUsers->LAST_ERROR." ".serialize($arUser);
                            return false;
                        }
                    }
                }
            }
        }

        self::ArrayToFile($arUsers,'arUsers', $this->sDir.'users.php' );
        $this->step = $_SESSION['sStep'] = 'addCatStructs';
        return true;
    }

    public function AddCatStructs()
    {
        CModule::IncludeModule("iblock");
        $iStarttime = time();
        require $this->sDir.'catstruct.php';

        /**
         * @var array $arUsers
         */
        if(self::IsFull())
        {
            foreach ($arCatStructs as $arCSId=>$arCatStruct)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arCatStructs,'arCatStructs', $this->sDir.'catstruct.php' );
                    return true;
                }

                $sect_id = FALSE;
                if( isset($arCatStruct['ParentId']) && $arCatStruct['ParentId']!='' )
                {
                    $arFilter = array("UF_1C_ID" => $arCatStruct['ParentId'], "IBLOCK_ID"=>IB_CATALOG);
                    $db_SectList = CIBlockSection::GetList(Array("ID"=>"ASC"), $arFilter, true);
                    if( $ar_res=$db_SectList->GetNext() )
                    {
                        $sect_id = $ar_res['ID'];
                    }
                }

                $arFields = array(
                    "ACTIVE"=>"Y",
                    "IBLOCK_SECTION_ID" => $sect_id,
                    "IBLOCK_ID" => IB_CATALOG,
                    "NAME" => $arCatStruct['Name'],
                    "UF_PARENT_ID" => $arCatStruct['ParentId'],
                    "UF_1C_ID" => $arCatStruct['id']
                );

                $bs = new CIBlockSection;
                $ID = $bs->Add($arFields);
                if($ID===FALSE)
                {
                    $this->LAST_ERROR = $bs->LAST_ERROR." ".serialize($arCatStruct);
                    return false;
                }
                else
                {
                    unset($arCatStructs[$arCSId]);
                }
            }
        }
        else
        {
            foreach ($arCatStructs as $arCSId=>$arCatStruct)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arCatStructs,'arCatStructs', $this->sDir.'catstruct.php' );
                    return true;
                }
                $arFields = array();

                $arFields['IBLOCK_ID'] = IB_CATALOG;
                if( isset($arCatStruct['Name']) && $arCatStruct['Name']!='' ) {$arFields['NAME']=$arCatStruct['Name'];}
                if( isset($arCatStruct['ParentId']) && $arCatStruct['ParentId']!='' )
                {
                    if( $arCatStruct['ParentId']!=0 )
                    {
                        $arFilter = array("UF_1C_ID" => $arCatStruct['ParentId'], "IBLOCK_ID"=>IB_CATALOG);
                        $db_SectList = CIBlockSection::GetList(Array("ID"=>"ASC"), $arFilter);
                        if( $ar_res=$db_SectList->GetNext() )
                        {
                            $arFields['IBLOCK_SECTION_ID'] = $ar_res['ID'];
                        }
                    }
                    elseif( $arCatStruct['ParentId']==0 )
                    {
                        $arFields['IBLOCK_SECTION_ID']=0;
                    }
                }

                if( count($arFields)>1 )
                {
                    $arFilter = array("UF_1C_ID" => $arCatStruct['id'], "IBLOCK_ID"=>IB_CATALOG);

                    $db_SectList = CIBlockSection::GetList(Array("ID"=>"ASC"), $arFilter);
                    if( $ar_res=$db_SectList->GetNext() )
                    {
                        $bs = new CIBlockSection;
                        $res = $bs->Update($ar_res['ID'], $arFields);
                        if( $res===FALSE )
                        {
                            echo "<br/>UPDATE FALSE = ".$bs->LAST_ERROR;
                            $this->LAST_ERROR = $bs->LAST_ERROR." ".serialize($arCatStruct);
                            return FALSE;
                        }
                        else
                        {
                            unset($arCatStructs[$arCSId]);
                        }
                    }
                }
            }
        }

        self::ArrayToFile($arCatStructs,'arCutStructs', $this->sDir.'catstruct.php' );
        $_SESSION['sStep'] = 'addCatalogs';
        return true;
    }

    public function AddCatalogs()
    {
        CModule::IncludeModule("iblock");
        $iStarttime = time();
        require $this->sDir.'catalogs.php';
        /**
         * @var array $arUsers
         */
        if(self::IsFull())
        {
            foreach ($arCatalogs as $arCatId=>$arCat)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arCatalogs,'arCatalogs', $this->sDir.'catalogs.php' );
                    echo '<br/>class max_execution_time = '.ini_get('max_execution_time');
                    return true;
                }

                $arProperties = array(
                    "PRICE" => $arCat['Price'],
                    "ID_1C" => $arCat['id'],
                    "PARENT_ID" => $arCat['CatalogStructureId'],
                );

                $arFilter = array("UF_1C_ID" => $arCat['CatalogStructureId'], "IBLOCK_ID"=>IB_CATALOG);
                $db_SectList = CIBlockSection::GetList(Array("ID"=>"ASC"), $arFilter);
                if( $ar_res=$db_SectList->GetNext() )
                {
                    $SECTION_ID = $ar_res['ID'];
                }

                $arFields = array(
                    "ACTIVE"=>"Y",
                    "IBLOCK_ID" => IB_CATALOG,
                    "IBLOCK_SECTION_ID" => $SECTION_ID,
                    "NAME" => $arCat['Name'],
                    "PROPERTY_VALUES" => $arProperties
                );

                $obEl = new CIBlockElement;
                $ID = $obEl->Add($arFields);

                if($ID===FALSE)
                {
                    $this->LAST_ERROR = $obElb->LAST_ERROR." ".serialize($arCat);
                    return false;
                }
                else
                {
                    unset($arCatalogs[$arCatId]);
                }
            }
        }
        else
        {
            foreach ($arCatalogs as $arCatId=>$arCat)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arCatalogs,'arCatalogs', $this->sDir.'catalogs.php' );
                    return true;
                }
                $arFields = array();
                $arProperties = array();

                if( isset($arCat['Name']) && $arCat['Name']!='' ) {$arFields['NAME']=$arCat['Name'];}
                if( isset($arCat['CatalogStructureId']) && $arCat['CatalogStructureId']!='' )
                {
                    $arFilter = array("UF_1C_ID" => $arCat['CatalogStructureId'], "IBLOCK_ID"=>IB_CATALOG);
                    $db_SectList = CIBlockSection::GetList(Array("ID"=>"ASC"), $arFilter);
                    if( $ar_res=$db_SectList->GetNext() )
                    {
                        $arFields['IBLOCK_SECTION_ID'] = $ar_res['ID'];
                        $arProperties['PARENT_ID'] = $arCat['CatalogStructureId'];
                    }
                }
                if( isset($arCat['Price']) && $arCat['Price']!='' ) {$arProperties['PRICE']=$arCat['Price'];}

                if( count($arFields) )
                {
                    $arFilter = array("PROPERTY_ID_1C" => $arCat['id']);
                    $db_ElList = CIBlockElement::GetList(Array("ID"=>"ASC"), $arFilter, false, false, array("ID"));
                    if( $ar_res=$db_ElList->GetNext() )
                    {
                        $obEl = new CIBlockElement;
                        $res = $obEl->Update($ar_res['ID'], $arFields);
                        if( $res===FALSE )
                        {
                            $this->LAST_ERROR = $bs->LAST_ERROR." ".serialize($arCatalogs);
                            return FALSE;
                        }
                        else
                        {
                            unset($arCatalogs[$arCatId]);
                        }
                    }
                }
                if( count($arProperties) )
                {
                    $arFilter = array("PROPERTY_ID_1C" => $arCat['id']);
                    $db_ElList = CIBlockElement::GetList(Array("ID"=>"ASC"), $arFilter, false, false, array("ID"));
                    if( $ar_res=$db_ElList->GetNext() )
                    {
                        CIBlockElement::SetPropertyValuesEx($ar_res['ID'], 1, $arProperties);
                    }
                }
            }
        }

        self::ArrayToFile($arCatalogs,'arCatalogs', $this->sDir.'catalogs.php' );
        $_SESSION['sStep'] = 'addOrders';
        return true;
    }

    public function AddOrders()
    {
        CModule::IncludeModule("iblock");
        $iStarttime = time();
        require $this->sDir.'orders.php';
        /**
         * @var array $arUsers
         */
        if(self::IsFull())
        {
            foreach ($arOrders as $arOrdId=>$arOrder)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arOrders,'arOrders', $this->sDir.'orders.php' );
                    return true;
                }

                $detail = '';

                foreach($arOrder['OrderItems'] as $arOrderItem)
                {
                    $detail .=" Услуга ".$arOrderItem['CatalogId']." Мастер ".$arOrderItem['MasterId']." Количество ".$arOrderItem['Quantity']." Цена ".$arOrderItem['Price'];
                }

                $properties = array(
                    "CLIENT_ID" => $arOrder['ClientId'],
                    "ORDER_ID" => $arOrder['Order']
                );


                $arFields = array(
                    "ACTIVE"=>"Y",
                    "NAME" => "Заказ № ".$arOrder['Order'],
                    "IBLOCK_SECTION_ID" => NULL,
                    "IBLOCK_ID" => IB_ORDERS,
                    "PROPERTY_VALUES" => $properties,
                    "DETAIL_TEXT" => $detail,
                    "DATE_CREATE" => $arOrder['Date']
                );

                $obEl = new CIBlockElement;
                $ID = $obEl->Add($arFields);

                if($ID===FALSE)
                {
                    $this->LAST_ERROR = $obEl->LAST_ERROR." ".serialize($arOrder);
                    return false;
                }
                else
                {
                    unset($arOrders[$arOrdId]);
                }
            }
        }
        else
        {
            foreach ($arOrders as $arOrdId=>$arOrder)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arOrders,'arOrders', $this->sDir.'orders.php' );
                    return true;
                }
                $arFields = array();

                $properties = array();
                if( isset($arOrder['ClientId']) && $arOrder['ClientId']!='' ) {$properties['CLIENT_ID']=$arOrder['ClientID'];}
                if( isset($arOrder['OrderItems']) && $arOrder['OrderItems']!='' )
                {
                    $detail = '';
                    foreach($arOrder['OrderItems'] as $arOrderItem)
                    {
                        $detail .=" Услуга ".$arOrderItem['CatalogId']." Мастер ".$arOrderItem['MasterId']." Количество ".$arOrderItem['Quantity']." Цена ".$arOrderItem['Price'];
                    }
                    $arFields['DETAIL_TEXT'] = $detail;
                }
                if(intval($arOrder['Date'])>0)
                {
                    $arFields["DATE_CREATE"]= $arOrder['Date'];
                }

                if( count($arFields) || count($properties) )
                {
                    $arFilter = array("PROPERTY_ORDER_ID" => $arOrder['order']);
                    $db_ElList = CIBlockElement::GetList(Array("ID"=>"ASC"), $arFilter, false, false, array("ID"));
                    if( $ar_res=$db_ElList->GetNext() )
                    {
                        if(count($arFields))
                        {
                            $obEl = new CIBlockElement;
                            $res = $obEl->Update($ar_res['ID'], $arFields);
                            if( $res===FALSE )
                            {
                                $this->LAST_ERROR = $obEl->LAST_ERROR." ".serialize($arOrder);
                                return FALSE;
                            }
                            else
                            {
                                unset($arOrders[$arOrdId]);
                            }
                        }
                        if(count($properties))
                        {
                            CIBlockElement::SetPropertyValuesEx($ar_res['ID'], 2, $properties);
                        }

                    }
                }
            }
        }

        self::ArrayToFile($arOrders,'arOrders', $this->sDir.'orders.php' );
        $_SESSION['sStep'] = 'addSchedules';
        return true;
    }

    public function AddShedules()
    {
        CModule::IncludeModule("iblock");
        $iStarttime = time();
        require $this->sDir.'schedules.php';
        /**
         * @var array $arUsers
         */
        if(self::IsFull())
        {
            foreach ($arSchedules as $arShId=>$arShedule)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arShedules,'arShedules', $this->sDir.'schedules.php' );
                    return true;
                }

                $detail = '';

                $properties = array(
                    "CLIENT_ID" => $arShedule['ClientId'],
                    "MASTER_ID" => $arShedule['MasterId'],
                    "SHEDULE_ID" => $arShedule['Schedule'],

                );

                foreach($arShedule['OrderItems'] as $arOrderItem)
                {
                    //$detail .=" Услуга ".$arOrderItem['CatalogId']." Мастер ".$arOrderItem['MasterId']." Цена ".$arOrderItem['Price'];
                    $detail .=" Услуга ".$arOrderItem['CatalogId']." Цена ".$arOrderItem['Price'];
                }

                $arFields = array(
                    "ACTIVE"=>"Y",
                    "IBLOCK_SECTION_ID" => NULL,
                    "IBLOCK_ID" => IB_SHEDULES,
                    "NAME" => "Запись № ".$arShedule['Schedule'],
                    "DATE_ACTIVE_FROM" => $arShedule['DateTimeFrom'],
                    "DATE_ACTIVE_TO" => $arShedule['DateTimeTo'],
                    "PROPERTY_VALUES" => $properties,
                    "DETAIL_TEXT" => $detail,
                    "DATE_CREATE" => $arShedule['Date']
                );

                $obEl = new CIBlockElement;
                $ID = $obEl->Add($arFields);

                if($ID===FALSE)
                {
                    echo "<br/>ERROR = ".$obEl->LAST_ERROR;
                    $this->LAST_ERROR = $obEl->LAST_ERROR." ".serialize($arShedule);
                    return false;
                }
                else
                {
                    unset($arShedules[$arShId]);
                }
            }
        }
        else
        {
            foreach ($arShedules as $arShId=>$arShedule)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arShedules,'arShedules', $this->sDir.'schedules.php' );
                    return true;
                }
                $arFields = array();

                $properties = array();
                if( isset($arShedule['ClientId']) && $arShedule['ClientId']!='' ) {$properties['CLIENT_ID']=$arShedule['ClientID'];}
                if( isset($arShedule['MasterId']) && $arShedule['MasterId']!='' ) {$properties['MASTER_ID']=$arShedule['MasterId'];}
                if( isset($arShedule['DateTimeFrom']) && $arShedule['DateTimeFrom']!='' ) {$arFields['DATE_ACTIVE_FROM']=$arShedule['DateTimeFrom'];}
                if( isset($arShedule['DateTimeTo']) && $arShedule['DateTimeTo']!='' ) {$arFields['DATE_ACTIVE_TO']=$arShedule['DateTimeTo'];}

                if( isset($arShedule['OrderItems']) && $arShedule['OrderItems']!='' )
                {
                    $detail = '';
                    foreach($arShedule['OrderItems'] as $arOrderItem)
                    {
                        //$detail .=" Услуга ".$arOrderItem['CatalogId']." Мастер ".$arOrderItem['MasterID']." Цена ".$arOrderItem['PRICE'];
                        $detail .=" Услуга ".$arOrderItem['CatalogId']." Цена ".$arOrderItem['PRICE'];
                    }
                    $arFields['DETAIL_TEXT'] = $detail;
                }
                if( intval($arShedule['Date'])>0 )
                {
                    $arFields['DATE_CREATE'] = $arShedule['Date'];
                }

                if( count($arFields) || count($properties) )
                {
                    $arFilter = array("PROPERTY_SHEDULE_ID" => $arShedule['Shedule']);
                    $db_ElList = CIBlockElement::GetList(Array("ID"=>"ASC"), $arFilter, false, false, array("ID"));
                    if( $ar_res=$db_ElList->GetNext() )
                    {
                        if(count($arFields))
                        {
                            $obEl = new CIBlockElement;
                            $res = $obEl->Update($ar_res['ID'], $arFields);
                            if( $res===FALSE )
                            {
                                $this->LAST_ERROR = $bs->LAST_ERROR." ".serialize($arShedule);
                                return FALSE;
                            }
                            else
                            {
                                unset($arShedules[$arShId]);
                            }
                        }
                        if(count($properties))
                        {
                            CIBlockElement::SetPropertyValuesEx($ar_res['ID'], 3, $properties);
                        }

                    }
                }
            }
        }

        self::ArrayToFile($arShedules,'arShedules', $this->sDir.'schedules.php' );
        $_SESSION['sStep'] = 'addMasterstats';
        return true;
    }

    public function AddMasterstats()
    {
        CModule::IncludeModule("iblock");
        $iStarttime = time();
        require $this->sDir.'masterstats.php';

        //print_r($arMasterstats);
        /**
         * @var array $arUsers
         */
        if(self::IsFull())
        {
            echo"<BR/> START AddMasterstats FULL";
            foreach ($arMasterstats as $arMSId=>$arMasterstat)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arMasterstats,'arMasterstats', $this->sDir.'masterstats.php' );
                    return true;
                }

                $arProperties = array(
                    "AVGORDER" => $arMasterstat['AvgOrder'],
                    "MASTER_ID" => $arMasterstat['MasterId'],
                    "PLAN_1" => $arMasterstat['Plan1'],
                    "PLAN_2" => $arMasterstat['Plan2'],
                    "PLAN_3" => $arMasterstat['Plan3'],
                    "PLAN_4" => $arMasterstat['Plan4'],
                    "PLAN_5" => $arMasterstat['Plan5'],
                    "ID_1C" => $arMasterstat['id']
                );

                $obRes = CUser::GetList(($by="ID"),($order="desc"), Array("UF_USER_ID"=>$arMasterstat['MasterId']));
                if($arResult = $obRes->fetch())
                {
                    $name = $arResult['NAME'].' - '.$arMasterstat['id'];
                    echo "<br/> Name = ".$name;
                }

                $arFields = array(
                    "ACTIVE"=>"Y",
                    "IBLOCK_SECTION_ID" => NULL,
                    "IBLOCK_ID" => IB_MASTERSTATS,
                    "NAME" => $name,
                    "PROPERTY_VALUES" => $arProperties

                );

                $obEl = new CIBlockElement;
                $ID = $obEl->Add($arFields);

                if($ID===FALSE)
                {
                    echo "<br/>ERROR = ".$obEl->LAST_ERROR;
                    $this->LAST_ERROR = $obEl->LAST_ERROR." ".serialize($arMasterstat);
                    return false;
                }
                else
                {
                    unset($arMasterstats[$arMSId]);
                }
            }
        }
        else
        {
            foreach ($arMasterstats as $arMSId=>$arMasterstat)
            {
                if (time() - $iStarttime > $this->iMax_exec_time)
                {
                    self::ArrayToFile($arMasterstats,'arMasterstats', $this->sDir.'masterstats.php' );
                    return true;
                }
                $arProps = array();

                if( isset($arMasterstat['AvgOrde']) && $arMasterstat['AvgOrde']!='' ) {$arProps['AVGORDER']=$arMasterstat['AvgOrde'];}
                if( isset($arMasterstat['MasterId']) && $arMasterstat['MasterId']!='' ) {$arProps['MASTER_ID']=$arMasterstat['MasterId'];}
                if( isset($arMasterstat['Plan1']) && $arMasterstat['Plan1']!='' ) {$arProps['PLAN_1']=$arMasterstat['Plan1'];}
                if( isset($arMasterstat['Plan2']) && $arMasterstat['Plan2']!='' ) {$arProps['PLAN_2']=$arMasterstat['Plan2'];}
                if( isset($arMasterstat['Plan3']) && $arMasterstat['Plan3']!='' ) {$arProps['PLAN_3']=$arMasterstat['Plan3'];}
                if( isset($arMasterstat['Plan4']) && $arMasterstat['Plan4']!='' ) {$arProps['PLAN_4']=$arMasterstat['Plan4'];}
                if( isset($arMasterstat['Plan5']) && $arMasterstat['Plan5']!='' ) {$arProps['PLAN_5']=$arMasterstat['Plan5'];}

                if( count($arProps) )
                {
                    $arFilter = array("PROPERTY_ID_1C" => $arMasterstat['id'], "IBLOCK_ID"=>IB_MASTERSTATS);
                    $db_ElList = CIBlockElement::GetList(Array("ID"=>"ASC"), $arFilter, false, false, array("ID"));
                    if( $ar_res=$db_ElList->GetNext() )
                    {
                        CIBlockElement::SetPropertyValuesEx($ar_res['ID'], 4,$arProps);
                        if( $res===FALSE )
                        {
                            $this->LAST_ERROR = $bs->LAST_ERROR." ".serialize($arMasterstat);
                            return FALSE;
                        }
                        else
                        {
                            unset($arMasterstats[$arMSId]);
                        }
                    }
                }
            }
        }

        self::ArrayToFile($arMasterstats,'arMasterstats', $this->sDir.'masterstats.php' );
        $_SESSION['sStep'] = 'done';
        return true;
    }
    
    public static function ArrayToFile($array,$name,$filename)
    {
		file_put_contents($filename,"<?\${$name} = ".var_export($array,true)."?>");
    }

    public static  function Translit($str)
    {
        $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', ' ', '(', ')');
        $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya', '_', '', '');
        return str_replace($rus, $lat, $str);
    }
}


class CBitrixCatalogImport1C extends CBitrixComponent
{
	public function checkDatabaseServerTime($secondsDrift = 600)
	{
		global $DB;

		CTimeZone::Disable();
		$sql = "select ".$DB->DateFormatToDB("YYYY-MM-DD HH:MI:SS", $DB->GetNowFunction())." DB_TIME from b_user";
		$query = $DB->Query($DB->TopSql($sql, 1));
		$record = $query->Fetch();
		CTimeZone::Enable();

		$dbTime = $record? MakeTimeStamp($record["DB_TIME"], "YYYY-MM-DD HH:MI:SS"): 0;
		$webTime = time();

		if ($dbTime)
		{
			if ($dbTime > ($webTime + $secondsDrift))
				return false;
			elseif ($dbTime < ($webTime - $secondsDrift))
				return false;
			else
				return true;
		}

		return true;
	}

	public function cleanUpDirectory($directoryName)
	{
		//Cleanup previous import files
		$directory = new \Bitrix\Main\IO\Directory($directoryName);
		if ($directory->isExists())
		{
			if (defined("BX_CATALOG_IMPORT_1C_PRESERVE"))
			{
				$i = 0;
				while (\Bitrix\Main\IO\Directory::isDirectoryExists($directory->getPath().$i))
				{
					$i++;
				}
				$directory->rename($directory->getPath().$i);
			}
			else
			{
				foreach ($directory->getChildren() as $directoryEntry)
				{
					$match = array();
					if ($directoryEntry->isDirectory() && $directoryEntry->getName() === "Reports")
					{
						$emptyDirectory = true;
						$reportsDirectory = new \Bitrix\Main\IO\Directory($directoryEntry->getPath());
						foreach ($reportsDirectory->getChildren() as $reportsEntry)
						{
							$match = array();
							if (preg_match("/(\\d\\d\\d\\d-\\d\\d-\\d\\d)\\./", $reportsEntry->getName(), $match))
							{
								if (
									$match[1] >= date("Y-m-d", time()-5*24*3600) //no more than 5 days old
									&& $match[1] < date("Y-m-d") //not today or future
								)
								{
									//Preserve the file
									$emptyDirectory = false;
								}
								else
								{
									$reportsEntry->delete();
								}
							}
							else
							{
								$reportsEntry->delete();
							}
						}

						if ($emptyDirectory)
						{
							$directoryEntry->delete();
						}
					}
					else
					{
						$directoryEntry->delete();
					}
				}
			}
		}
	}
}