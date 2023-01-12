<?php

namespace Godra\Api\Basket;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\UserTable;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Sale;
use Godra\Api\Helpers\Utility\Misc;
use Bitrix\Highloadblock as HL;

class Update
{
    protected $user;
    protected $fuser;
    protected $basket;
    protected $site_id;
    protected $post_data;

    public function __construct()
    {
        Misc::includeModules(['iblock', 'catalog', 'sale', 'currency']);

        $this->site_id   = Context::getCurrent()->getSite();
        $this->user      = $this->getUser();
        $this->fuser     = $this->getFuserId();
        $this->currency  = $this->getBaseCurrency();
        $this->basket    = $this->getBasketByFuser();
        $this->post_data = Misc::getPostDataFromJson();
    }

    /**
     * id highload-блока договора
     * @var int
     */
    public static $dogovoraHlId = HIGHLOAD_DOGOVORA_ID;

    /**
     * id highload-блока график
     * @var int
     */
    public static $scheduleHlId = HIGHLOAD_SCHEDULE_ID;

    /**
     * id highload-блока торговые точки
     * @var int
     */
    public static $tradePointsHlId = HIGHLOAD_TRADE_POINTS_ID;

    /**
     * Получить основную информацию пользователя (id, name)
     */
    protected function getUser()
    {
        return [
            'id' => CurrentUser::get()->getId(),
            'name' => CurrentUser::get()->getFormattedName(),
        ];
    }

    /**
     * Получить id текущего покупателя
     * @return int
     */
    protected function getFuserId()
    {
        return Sale\Fuser::getId();
    }

    /**
     * Получить базовую валюту
     */
    public function getBaseCurrency()
    {
        return CurrencyManager::getBaseCurrency();
    }

    /**
     * Получить корзину текущего пользователя
     */
    protected function getBasketByFuser()
    {
        return Sale\Basket::loadItemsForFUser(
            $this->fuser,
            $this->site_id
        );
    }

    public function getDistributionCenter() {
        // получить id выбранного договора
        $dealXmlId = UserTable::getList([
            'filter' => [ 'ID' => $this->user['id'] ],
            'select' => [ 'ID', 'UF_ID_DOGOVOR' ]
        ])->Fetch()['UF_ID_DOGOVOR'];

        // получить распределительные центр
        Loader::includeModule('highloadblock');

        $hlblock = HL\HighloadBlockTable::getById(self::$dogovoraHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        return $distributionCenterId = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_XML_ID' => $dealXmlId
            ]
        ])->Fetch()['UF_RASPREDELITELNYECENTRY'];
    }



    // пользователь выбирает торговую точку
    // найти идтипацены по договорам, найти доступные товары
    public function update() {
        $headers = apache_request_headers();
        // доступные товары
        $availableProductsXmlId = \Godra\Api\Catalog\Element::getAvailableProductsId($headers);

        $basketItems = $this->basket->getBasketItems();

        foreach ($basketItems as $item) {
            $xmlId = \CIBlockElement::GetList(
                false,
                [ 'ID' => $item->getProductId() ],
                false,
                false,
                [ 'ID', 'XML_ID' ]
            )->Fetch()['XML_ID'];

            if (!in_array($xmlId, $availableProductsXmlId)) {
                $item->setField('DELAY', 'Y');
            }
        }

        // id распределительного центра
        $distributionCenterId = $this->getDistributionCenter();

        // даты доставки для всех товаров
        $datesForDelivery = [];

        foreach ($basketItems as $item) {
            $xmlId = \CIBlockElement::GetList(
                false,
                [ 'ID' => $item->getProductId() ],
                false,
                false,
                [ 'ID', 'XML_ID' ]
            )->Fetch()['XML_ID'];

            $dateForOrder = $this->getDateForDelivery($distributionCenterId, $xmlId);
            $product = [
                'id'           => $item->getProductId(),
                //'id'           => $item->getId(),
                'name'         => $item->getField('NAME'),
                'price'        => $item->getPrice(),
                'props'        => $item->getPropertyCollection()->getPropertyValues(),
                'weight'       => $item->getWeight(),
                'can_buy'      => $item->canBuy(),
                'quantity'     => $item->getQuantity(),
                'final_price'  => $item->getFinalPrice(),
                'xml_id'       => $xmlId ?? '',
                //'measure_code' => $item->getField('MEASURE_CODE'), // временно закомментировал
                //'measure_name' => $item->getField('MEASURE_NAME'), // всегда штуки
                'delay'        => $item->getField('DELAY'),
                // дата доставки для каждого товара
                'date_for_order' => $dateForOrder
            ];

            $result['products'][] = $product;

            if (strpos($dateForOrder, ',')) {
                $dateArr = explode(',', $dateForOrder);

                foreach ($dateArr as $str) {
                    $datesForDelivery[trim($str)][] = $xmlId;
                }
            } else {
                $datesForDelivery[$dateForOrder][] = $xmlId;
            }
        }

        foreach ($datesForDelivery as $key => $date) {
            $result['group_by'][$key] = [
                'date' => $key,
                'date_to_str' => strtotime($key),
            ];
        }

        // объединение товаров с одинаковыми датами для заказа
        foreach ($datesForDelivery as $key => $date) {
            foreach ($date as $item) {
                foreach ($result['products'] as $product) {
                    if ($product['xml_id'] == $item) {
                        $products[] = $product;
                        $result['group_by'][$key]['products'][] =  $product;
                    }
                }
            }
        }

        $result['group_by'] = array_values($result['group_by']);

        return $result;
    }

    public static function getDateForDelivery($raspredCenterId, $productId) {
        Loader::includeModule('highloadblock');

        $hlblock = HL\HighloadBlockTable::getById(self::$scheduleHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        return $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_IDRASPREDELITELNOGOCENTRA' => $raspredCenterId,
                'UF_IDNOMENKLATURY' => $productId
            ]
        ])->Fetch()['UF_DATADLYAZAKAZA'];
    }
}