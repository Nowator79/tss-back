<?php

namespace Godra\Api\Information;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
Loader::includeModule("highloadblock");
Loader::includeModule("main");
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class PriceList
{
    public function getContragentID(){
        global $USER;
        $rsUser = \CUser::GetByID($USER->GetID());
        $arUser = $rsUser->Fetch();
        return $arUser['XML_ID'];
    }

    public function getPriceList(){
        $result = [];
        if(!empty($userCon = $this->getContragentID())){

            $hlbl = 71; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array("ID","UF_TITLE", "UF_FILE"),
                "order" => array("ID" => "ASC"),
                // Задаем параметры фильтра выборки
            ));

            while($arData = $rsData->Fetch()){
                if (!empty($arData['UF_FILE'])){
                    $result[$arData['ID']]['TITLE'] = $arData['UF_TITLE'];
                    $result[$arData['ID']]['FILE'] =  \CFile::GetPath($arData['UF_FILE']);
                }
            }
            $result = array_values($result);
        }
        return $result;
    }
}