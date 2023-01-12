<?
namespace Godra\Api\Basket;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use \Bitrix\Sale,
    \Bitrix\Sale\Order,
    \Bitrix\Main\Context,
    \Godra\Api\Catalog\Element,
    \Bitrix\Catalog\MeasureTable,
    \Bitrix\Main\Engine\CurrentUser,
    \Godra\Api\Helpers\Utility\Misc,
    \Bitrix\Currency\CurrencyManager,
    \Bitrix\Sale\Delivery\Services\Manager,
    \Bitrix\Sale\Delivery\Services\EmptyDeliveryService;

use Bitrix\Highloadblock as HL;
use Godra\Api\Helpers\Utility\Errors;

use \Godra\Api\Helpers,
    CSaleBasket,
    CUser;

abstract class Base
{
    protected $user;
    protected $fuser;
    protected $basket;
    protected $site_id;
    protected $post_data;

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

    function __construct()
    {
        Misc::checkRowsV2($this->post_data, $this->row_data);
        Misc::includeModules(['iblock', 'catalog', 'sale', 'currency']);

        $this->site_id   = Context::getCurrent()->getSite();
        $this->user      = $this->getUser();
        $this->fuser     = $this->getFuserId();
        $this->currency  = $this->getBaseCurrency();
        $this->basket    = $this->getBasketByFuser();
        $this->post_data = Misc::getPostDataFromJson();
    }

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
     * Получить единицу измерения по коду
     *
     * @param string $code Код единицы измерения
     * @return array|
     */
    protected function getMeasureByCode($code)
    {
        return MeasureTable::getList([
            'filter' => [ 'CODE' => $code ],
            'limit'  => 1,
        ])->fetch();
    }

    /**
     * Проверить наличие товара в корзине
     *
     * @param int $id Ид товара
     * @param string $measure_code название единицы измерения
     * @return boolean
     */
    protected function GetExistsBasketItem($id, $measure_code = false)
    {
        $result = false;

        if(empty($id) OR intval($id) <= 0 OR intval($id) != $id)
            return $result;

        foreach ($this->basket as $item)
        {
            if((int) $id == $item->getProductId())
            {
                if($measure_code)
                {
                    if(($item->getField('MEASURE_CODE') == $measure_code))
                    {
                        $result = $item;
                        break;
                    }
                }
                else
                {
                    $result = $item;
                    break;
                }
            }
        }

        return $result;
     }

    /**
     * Добавить товар в текущую корзину по id товара
     * @param int $id
     * @param array $measure Единица измерения , {name: 'название из админки', value: 'коофициент относительно базовой единицы' }
     * @param int $quantity
     */
    protected function addProductById($id, $measure_code = false, $quantity = false, $price_type = false)
    {
        $quantity = $quantity ?? 1;

        $priceTypeId = \Godra\Api\Catalog\Element::getPriceTypeId($price_type);
        $price = (int) \Godra\Api\Catalog\Element::getPriceValue($id, $priceTypeId) ?? '';

        // $measure_code - шт/кг, уп - коробка, плт- паллет
        switch ($measure_code) {
            case '':
            case 'шт':
            case 'кг':
                //
                break;
            case 'уп':
                $quantity =
                    $quantity * (int) \CIBlockElement::GetProperty(5, $id, ['sort' => 'asc'], ['CODE' => 'CML2_BASE_UNIT'])->Fetch()['DESCRIPTION'];
                break;
            case 'плт':
                $quantity =
                    $quantity * (int) \CIBlockElement::GetProperty(5, $id, ['sort' => 'asc'], ['CODE' => 'QUANTITY_PER_PALLET'])->Fetch()['DESCRIPTION'];
                break;
        }

        // временно закомментарил
//        if($measure_code)
//        {
//            $measure = $this->getMeasureByCode($measure_code);
//            $cooficient = Misc::getMeasureCooficientByProductId($id);
//        }

        // $quantity = $this->post_data['quantity'] ?: 1;

        // if ($item = $this->GetExistsBasketItem( $id, $measure['CODE'] ? : false ))
        if ($item = $this->GetExistsBasketItem( $id, 796 ? : false ))
        {
            $item->setFields([
                'QUANTITY' => $item->getQuantity() + (int) $quantity,
                'PRICE' => $price,
            ]);
        }
        else
        {
            $item = $this->basket->createItem('catalog', $id);

            $item->setFields([
                'QUANTITY' => (int) $quantity,
                'LID'      => 's1',
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                'PRICE' => $price,
            ]);

            // Если передана единица измерения
//            if($measure_code AND $measure)
//            {
//                $item->setField('MEASURE_CODE', (int) $measure['CODE']);
//                $item->setField('MEASURE_NAME', $measure['MEASURE_TITLE']);
//                $item->setField('CUSTOM_PRICE', 'Y');
//                $item->setField('PRICE', $item->getPrice()*$cooficient);
//            }

        }

        return $this->basket->save();
    }

    /**
     * Уменьшить кол-во товара в текущуей корзину по id товара
     * @param int $id
     * @param int $quantity
     */
    protected function removeProductById($id, $measure_code = false)
    {
        if($measure_code)
        {
            $measure = $this->getMeasureByCode($measure_code);
        }

        $quantity = $this->post_data['quantity'] ?: 1;

        //if ($item = $this->GetExistsBasketItem( $id, $measure['CODE'] ? : false ))
        if ($item = $this->GetExistsBasketItem( $id, 796 ? : false ))
            $item->getQuantity() > $quantity ?
                $item->setField('QUANTITY', $item->getQuantity() - $quantity):
                $item->delete();

        $this->basket->save();
    }

    /**
     * Удалить товар из корзины по id товара
     * @param int $id
     */
    protected function deleteProductById($id, $measure_code = false)
    {
        //if($item = $this->GetExistsBasketItem( $id, $measure['CODE'] ? : false ))
        if($item = $this->GetExistsBasketItem( $id, 796 ? : false ))
            $item->delete();

        $this->basket->save();
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
     * Получить корзину текущего пользователя
     */
    protected function getBasketByFuser()
    {
        return Sale\Basket::loadItemsForFUser(
            $this->fuser,
            $this->site_id
        );
    }

    /**
     * Получить кол-во товаров в корзине
     * @return int
     */
    public function getQuantityList()
    {
        return [ 'count' => count($this->basket->getQuantityList()) ];
    }

    // получить id распределительного центра
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

    /**
     * Метод для получения дат для заказа в зависимости от распределительного центра и товара
     *
     * @param $productId
     * @param $raspredCenterId
     * @return false|mixed|string[]
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
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

    /**
     * Получить товары корзины
     * @return array
     */
    public function getBasketItems()
    {
        $basketItems = $this->basket->getBasketItems();

		/*
		// доступные товары
		$headers = apache_request_headers();
        $availableProductsXmlId = \Godra\Api\Catalog\Element::getAvailableProductsId($headers);
		
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
		*/
		
        // id распределительного центра
        $distributionCenterId = $this->getDistributionCenter();

        // даты доставки для всех товаров
        $datesForDelivery = [];

        $result['group_by'] = [];
        
        //////////////////////////////////////////////////////////
        // товары, которые стали недоступны данному покупателю, нужно перевести в отложенные, а доступные убрать из отложенных
        $isCartChange = false;
        
        if ($basketItems)
        {
            $bitrixUserApi = new Helpers\Auth\Bitrix();
            
            if ($bitrixUserApi->isAuth())
            {
                $userId = $bitrixUserApi->getUserId();
                
                if ($userId)
                {
                    $nomenclature = (new Helpers\Nomenclature)->getByUserId($userId);
                    
                    foreach ($basketItems as $item) 
                    {
                        if (!$nomenclature[$item->getProductId()])
                        {
                            if (!$item->isDelay())
                            {
                                $item->setField('DELAY', 'Y');
                                
                                $isCartChange = true;
                            }
                        }
                        else
                        {
                            if ($item->isDelay())
                            {
                                $item->setField('DELAY', 'N');
                                
                                $isCartChange = true;
                            }
                        }
                    }
                }
            }
        }
        else
        {
            // не авторизованный пользователь не может работать с корзиной, поэтому здесь ничего не делаем
        }
        
        // если корзина изменилась, сохраним
        if ($isCartChange)
        {
            $this->basket->save();
            
            // обновим данные по корзине
            $this->basket = $this->getBasketByFuser();
            
            $basketItems = $this->basket->getBasketItems();
        }
        //////////////////////////////////////////////////////////
        
        $allProds = [];
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
                'date_for_order' => $dateForOrder,
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

        // сумма заказа = общая сумма - сумма скидки
        $result['order_price'] = $this->basket->getPrice();

        // общая стоимость
        $result['total_price'] = $this->basket->getBasePrice();

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

        foreach ($result['group_by'] as $k => $group) {
            if ($group['date_to_str'] == '' || !$group['date_to_str']) {
                unset($result['group_by'][$k]);
            }
        }

        $result['group_by'] = array_values($result['group_by']);
        if (empty($result['group_by'])) unset($result['group_by']);

        return !empty($result) ? $result : null;
    }

    public function getDogovorById($dogovorId) {
        Loader::includeModule('highloadblock');

        $hlblock = HL\HighloadBlockTable::getById(self::$dogovoraHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

         return $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'ID' => $dogovorId
            ]
        ])->Fetch();
    }

    /**
     * Метод для получения торговых точек по договору
     *
     * @param $dogovorsIds
     * @return array
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function getTradePointsByDogovorId($dogovorsIds) {
        Loader::includeModule('highloadblock');

        $tradePoints = [];

        $hlblock = HL\HighloadBlockTable::getById(self::$dogovoraHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $tradePointObjs = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'ID' => $dogovorsIds,
            ]
        ]);

        while ($row = $tradePointObjs->Fetch()) {
            if (strpos($row['UF_IDTORGOVYHTOCHEK'], ',')) {
                $arr = explode(',', $row['UF_IDTORGOVYHTOCHEK']);
                foreach ($arr as $item) {
                    $tradePoints[] = (int) $item;
                }
            } else {
                $tradePoints[] = (int) $row['UF_IDTORGOVYHTOCHEK'];
            }
        }
        return  array_unique( $tradePoints );
    }

    /**
     * Метод для получения id договоров текущего пользователя
     *
     * @return mixed
     */
    public function getUserDogovorsId() {
        $dogovors = [];

        $dogovorsRaw = UserTable::getList([
            'select' => ['ID', 'UF_ID_DOGOVOR'],
            'filter' => [
                'ID' => $this->getUser()['id'],
                'ACTIVE' => 'Y'
            ]
        ])->Fetch()['UF_ID_DOGOVOR'];

        return strpos($dogovorsRaw, ',') ? explode(',', $dogovorsRaw) : $dogovorsRaw;
    }

    /**
     * Получить базовую валюту
     */
    public function getBaseCurrency()
    {
        return CurrencyManager::getBaseCurrency();
    }

    /**
     * метод для получения данных торговой точки
     *
     * @param $tradePointId
     * @return mixed
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function getTradePointById($tradePointId) {
        Loader::includeModule('highloadblock');

        $hlblock = HL\HighloadBlockTable::getById(self::$tradePointsHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        return $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'ID' => $tradePointId,
            ]
        ])->Fetch();
    }

    /**
     * Метод для получения информации о договорах по id контрагента
     *
     * @param $contragentId
     * @return array
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDogovorsByContragentId($contragentId) {
        Loader::includeModule('highloadblock');
        $dogovors = [];

        $hlblock = HL\HighloadBlockTable::getById(self::$dogovoraHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $dogovorsObj = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_IDKONTRAGENTA' => $contragentId,
            ]
        ]);

        while ($row = $dogovorsObj->Fetch()) {
            $dogovors[] = $row;
        }

        return $dogovors;
    }

    /**
     * Метод для получения id торговых точек
     *
     * @param $contragentId
     * @return false|string[]
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getTradePoints($contragentId) {
        Loader::includeModule('highloadblock');

        $hlblock = HL\HighloadBlockTable::getById(self::$dogovoraHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $tradePointsStr = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_IDKONTRAGENTA' => $contragentId,
            ]
        ])->Fetch()['UF_IDTORGOVYHTOCHEK'];

        return array_values(explode(',', $tradePointsStr));
    }

    /**
     * Метод для получения данных торговых точек
     *
     * @param $tradePointsIds
     * @return array
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function getTradePointsData($tradePointsIds) {
        Loader::includeModule('highloadblock');

        $tradePoints = [];

        $hlblock = HL\HighloadBlockTable::getById(self::$tradePointsHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $tradePointsObj = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_IDTORGOVOJTOCHKI' => $tradePointsIds,
            ]
        ]);

        while ($row = $tradePointsObj->Fetch()) {
            $tradePoints[] = $row;
        }

        return $tradePoints;
    }

    /**
     * Метод для получения id контрагента
     *
     * @return mixed
     */
    public function getContragentId() {
        return UserTable::getList([
            'select' => ['ID', 'UF_CONTRAGENT_ID'],
            'filter' => ['ID' => $this->user['id']]
        ])->Fetch()['UF_CONTRAGENT_ID'];
    }

    /**
     * Метод для получения распределительного центра
     *
     * @param $contragentId
     * @return mixed|string
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDistributionCenters($contragentId) {
        Loader::includeModule('highloadblock');

        $hlblock = HL\HighloadBlockTable::getById(self::$dogovoraHlId)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $distributionCentersStr = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_IDKONTRAGENTA' => $contragentId,
            ]
        ])->Fetch()['UF_RASPREDELITELNYECENTRY'];

        $distributionCenters = explode(',', $distributionCentersStr);

        return $distributionCenters;
    }

    /**
     * Создать заказ
     *
     *
     * надо будет переделать в зависимости от свойства "Дата и время доставки"
     */
    public function addOrder()
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        if (count($this->basket)<=0)
        {
           Errors::add("Ваша корзина пуста");
        }
        
        $userSelectedContract = $userSelectedOutltet = '';
        
        $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['ID' => $USER->GetID()], ['SELECT' => ['UF_*']]);

        while ($arUser = $rsUsers->Fetch()) 
        {
            $userSelectedContract = $arUser['UF_ID_DOGOVOR'];
            
            $userSelectedOutltet = $arUser['UF_SALE_POINT'];
        }
		
        $userContragent = (new \Godra\Api\Helpers\Contragent)->getContragentCodeByUser($USER->GetID(), (new \Godra\Api\Helpers\Auth\Bitrix)->isSuperUser());

       //
       $basketItems = $this->basket->getBasketItems();

        if ($basketItems)
        {
            foreach ($basketItems as $item) 
            {
                if ($item->isDelay())
                {
                    CSaleBasket::Delete($item->getId['ID']);
                }
            }
        }
        //
        
        $delyvery_id = $this->post_data['delivery_id'] ?:
            EmptyDeliveryService::getEmptyDeliveryServiceId();

        if($order = Order::create(SITE_ID, $this->user['id'], $this->currency))
        {
            $order->setPersonTypeId(1);
            $order->setBasket($this->basket);

            // статус - черновик
            if ($this->post_data['status'] == 'draft') {
                $order->setField('STATUS_ID', 'DR');
            }

            /* Доставка */

            // несколько доставок в зависимости от датыДляЗаказа
            $shipmentCollection = $order->getShipmentCollection();

            if (isset($this->post_data['group_by']) && !empty(isset($this->post_data['group_by']))) {
                foreach ($this->post_data['group_by'] as $group) {
                    if (isset($group['datetime'])
                        && !empty($group['datetime'])
                        && isset($group['productsId'])
                        && !empty($group['productsId'])) {

                        $shipment = $shipmentCollection->createItem();
                        $service  = Manager::getById($delyvery_id);

                        //$deliveryTime = \Bitrix\Main\Type\DateTime::createFromTimestamp($group['date_str']);

                        // установка свойств отгрузки
                        $shipment->setFields([
                            'DELIVERY_ID' => $service['ID'],
                            'DELIVERY_NAME' => $service['NAME'],
                            'DELIVERY_DOC_DATE' => $deliveryTime ?? '',
                            'COMMENTS' => $group['datetime'] . ' | ' . $group['comment'] ?? ''
                        ]);

                        $shipment_item_collection = $shipment->getShipmentItemCollection();

                        foreach ($this->basket as $item)
                        {
                            if (in_array($item->getProductId(), $group['productsId'])) {
                                $shipment_item = $shipment_item_collection->createItem($item);
                                $shipment_item->setQuantity($item->getQuantity());
                            }
                        }
                    }
                }
            } else {

                $shipment = $shipmentCollection->createItem();
                $service  = Manager::getById($delyvery_id);

                $shipment->setFields([
                    'DELIVERY_ID' => $service['ID'],
                    'DELIVERY_NAME' => $service['NAME'],
                ]);

                $shipment_item_collection = $shipment->getShipmentItemCollection();

                foreach ($this->basket as $item)
                {
                    $shipment_item = $shipment_item_collection->createItem($item);
                    $shipment_item->setQuantity($item->getQuantity());
                }
            }

            /* /Конец доставки */

            /* Свойства заказа */
            $select_props = ['FIO', 'PHONE', 'EMAIL', 'TRADE_POINT_ID', 'id_dogovor', 'id_kontragent'];
            $property_collection = $order->getPropertyCollection();
            $property_code_to_id   = [];

            foreach($property_collection as $prop_value)
            {
                $property_code_to_id[$prop_value->getField('CODE')] = $prop_value->getField('ORDER_PROPS_ID');
            }

            foreach ($select_props as $prop_code)
            {
                if ($this->post_data[$prop_code])
                {
                    $prop_value = $property_collection->getItemByOrderPropertyId(\strtoupper($prop_code));
                    $prop_value->setValue($this->post_data[$prop_code]);
                }
                
                if ($prop_code == 'TRADE_POINT_ID')
                {
                    $prop_value = $property_collection->getItemByOrderPropertyId($property_code_to_id[$prop_code]);
                    $prop_value->setValue($userSelectedOutltet);
                }
                
                if ($prop_code == 'id_dogovor')
                {
                    $prop_value = $property_collection->getItemByOrderPropertyId($property_code_to_id[$prop_code]);
                    $prop_value->setValue($userSelectedContract);
                }
				
				if ($prop_code == 'id_kontragent')
                {
                    $prop_value = $property_collection->getItemByOrderPropertyId($property_code_to_id[$prop_code]);
                    $prop_value->setValue($userContragent);
                }
            }

            /* /Конец свойств заказа */
            $order->doFinalAction(true);

            $result = $order->save();

            if(!$result->isSuccess())
                Errors::add("Ошибка создания заказа: ".implode(", ",$result->getErrorMessages()));
            else return [ 'id' => $result->getId() ];

            //return $order->getField('ACCOUNT_NUMBER');
        }
    }
    
    public function clear()
    {
        CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID());
        
        return ['status' => 1];
    }
}
?>