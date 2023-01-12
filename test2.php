<?

use Bitrix\Main\Mail\Event;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Главная');
?>

<?php

\Bitrix\Main\Loader::includeModule('main');
\Bitrix\Main\Loader::includeModule('iblock');

use Bitrix\Highloadblock as HL;
//var_dump(_CIBElement::GetProperty(4541));


$sectionElements = \CIBlockElement::GetList(
    [
       'propertysort_HIT' => 'asc,nulls'
    ],
    [
        'IBLOCK_ID' => 5,
        'ACTIVE' => 'Y',
        //'PROPERTY_HIT' => 4543,
        //'!SECTION_CODE' => ['smesi', 'napitki'], //'detskoe_pitanie',
        //'!SECTION_ID' => [144, 145],
//        [
//            'LOGIC' => 'AND',
//            ['SECTION_CODE' => 'detskoe_pitanie'],
//            ['!SECTION_ID' => [144, 145]],
//        ],
//        'IBLOCK_SECTION_ID' => 138,
//        'IBLOCK_SECTION' => 13,
//        [
//            'LOGIC' => 'AND',
//            ['IBLOCK_SECTION_ID' => 138],
//            [
//                'LOGIC' => 'OR',
//                [
//                    'IBLOCK_SECTION' => [13]
//                ],
//                [
//                    'ID' => [
//                        11996,
//                        8516,
//                        8514,
//                        13130
//                    ],
//                    //'IBLOCK_SECTION_ID' => 13
//                    //'SECTION_ID' => [138]
//                ]
//            ]
//        ],
//        [
//            'LOGIC' => 'AND',
//            ['SECTION_ID' => [13]],
//            ['SECTION_ID' => [138]],
//        ],
    //['']
    //['SECTION_ID' => 13, 138],
//    [
//        'LOGIC' => 'AND',
//        [
//            // родительский раздел
//            'SECTION_CODE' => 'detskoe_pitanie',
//            'INCLUDE_SUBSECTIONS' => 'Y'
//        ],
//        [
//            'LOGIC' => 'OR',
//            ['ID' => [
//                11996,
//                8516,
//                8514,
//                13130
//            ]],
//            ['SECTION_ID' => [138]]
//        ]
//    ],
//        [
//            'LOGIC' => 'OR',
//            [
//                'LOGIC' => 'AND',
//                ['SECTION_ID' => [13]],
//                ['SECTION_ID' => [138]],
//            ],
//            [
//                'LOGIC' => 'AND',
//                ['SECTION_ID' => [138]],
//                ['ID' => [
//                    11996,
//                    8516,
//                    8514,
//                    13130
//                ]],
//                //'IBLOCK_SECTION_ID' => 13
//                //'SECTION_ID' => [138]
//            ]
//        ],
        //'INCLUDE_SUBSECTIONS' => 'Y',
        //'PROPERTY_BREND' => [3695, 3702],
//        # лейблы
//        // акция
//        '!PROPERTY_STOCK' => false,
//        // хит
//        '!PROPERTY_HIT' => false,
//        // новинка
//        '!PROPERTY_NEW' => false,
//        // фасовка
//        '!PROPERTY_FASOVKA' => false,
//        // упаковка
//        '!PROPERTY_PACKING' => false,
//        # лейблы
//        // вес
//        '!PROPERTY_WEIGHT' => false,
//        // срок годности
//        '!PROPERTY_EXPERATION_DATE' => false,
//        // фасовка
//        '!PROPERTY_FASOVKA_2' => false,
    ],
    false,
    false,
    [
        'ID',
        'NAME',
        'SECTION_CODE',
//        // акция
        'PROPERTY_STOCK',
        // хит
        'PROPERTY_HIT',
//        // новинка
//        'PROPERTY_NEW',
//        // фасовка
//        'PROPERTY_FASOVKA',
//        // упаковка
//        'PROPERTY_PACKING',
//        # лейблы
//        // вес
//        'PROPERTY_WEIGHT',
//        // срок годности
//        'PROPERTY_EXPERATION_DATE',
//        // фасовка
//        'PROPERTY_FASOVKA_2',
    ],
);
//
//
//$count = 0;
//echo '----111';
//while ($row = $sectionElements->Fetch()) {
//    $count++;
//    echo '<pre>'; print_r($row); echo '</pre>';
//}
//
//echo $count;

//$prop = \CIBlockElement::GetProperty(
//    5,
//    13130,
//    [
//        'ACTIVE' => 'Y',
//    ],
//    [
//        'CODE' => 'MORE_PHOTO'
//    ]
//);

//$prop = \CIBlockElement::GetPropertyValues(
//    5,
//    [
//        'ACTIVE' => 'Y'
//    ],
//    true,
//    false
//);

//$props = \Bitrix\Iblock\PropertyTable::getList([
//    'filter' => [
//        'IBLOCK_ID' => 5,
//        'ACTIVE' => 'Y',
//    ],
//    'select' => ['*']
//]);
//
//echo '444';
//
//while ($row = $props->Fetch()) {
//    echo '<pre>'; print_r($row); echo '</pre>';
//}

//echo COption::GetOptionString("main", "agents_use_crontab", "N");
//
//$result = Event::send([
//        'EVENT_NAME' => 'ADD_IDEA',
//        'MESSAGE_ID' => 39,
//        'LID' => 's1',
//        'C_FIELDS' => [
//            'TITLE' => 'dssdf',
//            'AUTHOR' => 'sdfsdf',
//            'DATE_PUBLISH' => '3453453',
//            'IDEA_TEXT' => '1212',
//            'FULL_PATH' => 'dssdf'
//        ],
//    ]
//);
//
//var_dump($result->getErrors());

global $USER;

//echo 'Хит: ' . $hits = \CIBlockPropertyEnum::GetList(false, [
//    'VALUE' => 'ХИТ',
//    'CODE' => 'HIT'
//])->Fetch()['ID'];

//while ($row = $hits->Fetch()) {
//    echo '<pre>'; print_r($row); echo '</pre>';
//}

//$count = 0;
//while ($row = $sectionElements->Fetch()) {
//    $count++;
//    echo '<pre>'; print_r($row); echo '</pre>';
//}
//
//echo $count;
//
//$props = [];
//$propsObj = \CIBlockElement::GetProperty(5, false, [], []);
//
//while ($row = $propsObj->Fetch()) {
//    //echo '<pre>'; print_r($row); echo '</pre>';
//
//    $props[] = [
//        'code' => $row['CODE'],
//        'name' => $row['NAME']
//    ];
//}
//
//echo '<pre>'; print_r($props); echo '</pre>';
use Bitrix\Main\Loader;
Loader::includeModule('sale');

//$basket = \CSaleBasket::GetList(
//    false,
//    [
//        'USER_ID' => 7
//    ],
//    false,
//    false,
//    [
//        '*'
//    ]
//);

//$rs_price = \Bitrix\Catalog\PriceTable::getList([
//    'filter' => [
//        'PRODUCT_ID' => 13130,
//        'CATALOG_GROUP_ID' => 214
//    ]
//]);
//
//while ($row1 = $rs_price->Fetch()) {
//    echo '<pre>'; print_r($row1); echo '</pre>';
//}
//
////Bitrix\Catalog\GroupTable
//$objs = Bitrix\Catalog\GroupTable::getList([
//    'select' => ['*'],
//    'filter' => [
//        '=NAME' => 'ОД Крым ОПТ с НДС'
//    ]
//]);
//
//while ($row2 = $objs->Fetch()) {
//    echo '<pre>'; print_r($row2); echo '</pre>';
//}

//\Bitrix\Main\Loader::IncludeModule('catalog');
//
//$amount = \Bitrix\Catalog\ProductTable::getList([
//    'filter' => ['ID' => 13130]
//])->fetch();

//Loader::includeModule('highloadblock');
//
//$hlblock = HL\HighloadBlockTable::getById(10)->fetch();
//$entity = HL\HighloadBlockTable::compileEntity($hlblock);
//
//$entityDataClass = $entity->getDataClass();
//
//$arr = [];
//
//$datesStr = $entityDataClass::getList([
//    'select' => ['*'],
//    'filter' => [
//        'UF_IDRASPREDELITELNOGOCENTRA' => 123,
//        'UF_IDNOMENKLATURY' => 	13130
//    ]
//])->Fetch();
//
//
//echo '<pre>'; print_r($datesStr); echo '</pre>';

//use Bitrix\Main\Type\DateTime;
//$sendTime = DateTime::createFromTimestamp(1670792400);
//echo '<pre>'; print_r($sendTime); echo '</pre>';

//echo \Bitrix\Catalog\GroupTable::getList([
//    'select' => ['*'],
//    'filter' => [
//        '=XML_ID' => '991497db-79d3-11e3-8986-782bcb24e027'
//    ]
//])->Fetch()['ID'];

//Loader::includeModule('form');
//echo '<pre>';
//print_r(\CFormResult::GetList(3, ($by="s_id"), ($order="desc"), false, false, 'N')->Fetch());
//echo '</pre>';

$objs = \CIBlockElement::GetList(
    false,
    [
        'IBLOCK_ID' => '',
        'ACTIVE' => 'Y',
        'XML_ID' => [
            "fac29524-65cb-11e0-a1ac-003048619700",
            "8a6336fc-37f8-11e8-ab34-00155d07b505",
            "a163ae99-0083-11e6-88eb-782bcb24e027",
            "be7cb6dd-7d7d-11ea-ab72-00155d0a8003",
            "adfba911-d3c9-11e9-ab69-00155d0a8003",
            "52c91db0-6e85-11e6-90c4-782bcb24e027",
            "6c2edd63-40bd-11e8-ab34-00155d07b505",
            "bacc9985-bc68-11eb-ab81-00155d0a8003",
            "ab4c2db8-f1ad-11ea-ab76-00155d0a8003",
            "d7f4a9a4-27d1-11e6-ae31-782bcb24e027",
            "67077e97-3927-11ea-ab6e-00155d0a8003",
            "a734023c-6212-11eb-ab7c-00155d0a8003",
            "4b66425b-e5de-11ea-ab76-00155d0a8003",
            "9cde5692-596e-11eb-ab7c-00155d0a8003",
            "2c44f5ef-d454-11e9-ab69-00155d0a8003",
            "10779211-c4d6-11ea-ab76-00155d0a8003",
            "574f7b0e-5f39-11e9-ab57-00155d0a8003",
            "24bc7349-7905-11e8-ab41-00155d07b505",
            "b8d80d0f-86e8-11eb-ab7e-00155d0a8003",
            "18e73e46-d936-11e9-ab69-00155d0a8003",
            "26c3610f-c448-11eb-ab81-00155d0a8003",
            "b66682e4-e8f2-11ea-ab76-00155d0a8003",
            "ecd93e56-d87a-11e9-ab69-00155d0a8003",
            "48f82db4-24c8-11eb-ab78-00155d0a8003",
            "4085a653-1d22-11ed-ab8d-00155d0a8003",
            "23247fcd-2d52-11e6-ae31-782bcb24e027",
            "3feb15e3-7b66-11e8-ab45-00155d0a8003",
            "9cf50e4a-b879-11e7-ab4a-00155d07b500",
            "d543b85d-fbde-11ea-ab76-00155d0a8003",
            "9ba84c1a-5156-11e9-ab57-00155d0a8003",
            "18a47ee2-ef22-11e5-8000-782bcb24e027",
            "a2c1ef27-39ef-11eb-ab78-00155d0a8003",
            "b711877b-39f1-11eb-ab78-00155d0a8003",
            "23a9e7de-4372-11eb-ab78-00155d0a8003",
            "23a9e7e4-4372-11eb-ab78-00155d0a8003",
            "faf369ed-ec82-11e8-ab4d-00155d0a8003",
            "97dc9cbc-0186-11eb-ab76-00155d0a8003",
            "13a458fb-4176-11e8-ab34-00155d07b505",
            "d6953bb1-02d7-11eb-ab76-00155d0a8003",
            "c0d0e1e4-42c3-11e8-ab34-00155d07b505",
            "58daa07b-f0e0-11ea-ab76-00155d0a8003",
            "7700873b-fe4d-11ea-ab76-00155d0a8003",
            "77008731-fe4d-11ea-ab76-00155d0a8003",
            "ba924a93-c368-11e6-9373-782bcb24e027",
            "daed2eae-46b9-11ec-ab84-00155d0a8003",
            "9c951294-4a99-11eb-ab79-00155d0a8003",
            "249e1c0b-64cc-11e0-9df4-003048619700",
            "b15eb8e0-6404-11e0-8515-003048619700",
            "d03995fb-4cd1-11ea-ab6e-00155d0a8003",
            "520f1209-e09c-11ec-ab8c-00155d0a8003",
            "0d7ac2a1-2bea-11e3-b970-782bcb24e027"
        ]
    ],
    false,
    false,
    [
        'ID',
        'CODE',
        'XML_ID',
        'NAME'
    ]
);

while ($row = $objs->Fetch()) {
    echo '<pre>'; print_r($row); echo '</pre>';
}

?>