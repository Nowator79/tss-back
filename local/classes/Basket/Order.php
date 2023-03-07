<?
namespace Godra\Api\Basket;

use Godra\Api\Helpers\Utility\Misc;
use Bitrix\Sale,
    Bitrix\Currency;
use Bitrix\Main\Context;

class Order
{
    protected static $orderFields = [
        'FIO',
        'PHONE',
        'EMAIL',
        'DELIVERY_ADRESS'
    ];

    /**
     *  метод для создания заказа
     *  {
     * "FIO": "Иванов Иван Иванович",
     * "PHONE": "+799953333111",
     * "EMAIL": "ya1331@ya.ru",
     * "USER_DESCRIPTION":"Это комментарий",
     * "DELIVERY_ID": "4",
     * "PAYMENT_ID": "2",
     * "DELIVERY_ADRESS":"Сан-Франциско ул. Слава Коммунизму 1",
     * "STATUS":"draft"
     * }
     */
    public static function add()
    {
        $params = Misc::getPostDataFromJson();
        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");
        global $USER;
        $useId = ($USER->GetID() == 0) ? 1 : $USER->GetID();

        $siteId = Context::getCurrent()->getSite();
        $currencyCode = Currency\CurrencyManager::getBaseCurrency();
        $order = \Bitrix\Sale\Order::create($siteId, $USER->isAuthorized() ? $useId : 1);
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

        if (count($basket->getQuantityList())) {
            $order->setBasket($basket);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', $currencyCode);
            $order->setField('USER_DESCRIPTION', $params['USER_DESCRIPTION']);

            // заполняем поля
            $propertyCollection = $order->getPropertyCollection();

            foreach ($propertyCollection as $propertyItem) {
                $code = $propertyItem->getField('CODE');
                if (in_array($code, self::$orderFields)) {
                    $propertyItem->setValue($params[$code]);
                }
            }

            //доставка
            $shipmentCollection = $order->getShipmentCollection();
            $shipment = $shipmentCollection->createItem();
            $service = \Bitrix\Sale\Delivery\Services\Manager::getById($params['DELIVERY_ID']);

            $shipment->setFields([
                'DELIVERY_ID' => $service['ID'],
                'DELIVERY_NAME' => $service['NAME'],
            ]);

            $shipmentItemCollection = $shipment->getShipmentItemCollection();

            foreach ($order->getBasket() as $item) {
                $shipmentItem = $shipmentItemCollection->createItem($item);
                $shipmentItem->setQuantity($item->getQuantity());
            }

            // оплата
            $paymentCollection = $order->getPaymentCollection();
            $payment = $paymentCollection->createItem(\Bitrix\Sale\PaySystem\Manager::getObjectById($params['PAYMENT_ID'])); // $params['PAYMENT_ID'] - ИД платежной системы
            $payment->setField('SUM', $order->getPrice());

            // статус - черновик
            if ($params['STATUS'] == 'draft') {
                $order->setField('STATUS_ID', 'DR');
            }

            // Сохраняем
            $order->doFinalAction(true);
            $result = $order->save();

            if ($result->isSuccess()) {
                $orderId = $order->getId();
            } else {
                $orderId = ["error" => $result->getError()];
            }
        } else {
            $orderId = ["error" => "Корзина пуста!"];
        }

        return $orderId;
    }

    /**
     * Получить все заказы пользователя
     * возможна фильтрация по дате и статусу (F,DR,DF)
     *
     * @return void
     */
    public static function get()
    {
        $params = Misc::getPostDataFromJson();
        \Bitrix\Main\Loader::includeModule("sale");
        $ORDERS_PER_PAGE = 10;
        $PAGEN = 1;
        global $USER;
        $userId = ($USER->GetID() == 0) ? 1 : $USER->GetID();


        if (!empty($params['PAGEN'])) {
            $PAGEN = $params['PAGEN'];
        }

        if (!empty($params['orderPerPage'])) {
            $ORDERS_PER_PAGE = $params['orderPerPage'];
        }

        $filter = [];
        if (!empty($params['status'])) {
            $filter['STATUS_ID'] = $params['status'];
        }

        if (!empty($params['id'])) {
            $filter['ID'] = $params['id'];
        }

        $filter['USER_ID'] = $userId;
        if (!empty($params['date_begin']) && !empty($params['date_end'])) {
            $BITRIX_DATETIME_FORMAT = 'd.m.Y H:i:s';
            $dateBegin = new \DateTime(sprintf($params['date_begin'], date('Y'), date('m'), date('d')), new \DateTimeZone('UTC'));
            $dateEnd = new \DateTime(sprintf($params['date_end'], date('Y'), date('m'), date('d')), new \DateTimeZone('UTC'));
            $dateEnd->modify('+1 day -1 second');
            $filter['>=DATE_INSERT'] = $dateBegin->format($BITRIX_DATETIME_FORMAT);
            $filter['<=DATE_INSERT'] = $dateEnd->format($BITRIX_DATETIME_FORMAT);

        }

        $listOrders = array();
        $orderIdList = array();
        $listOrderBasket = array();
        $listOrderShipment = array();
        $listOrderPayment = array();
        $registry = \Bitrix\Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

        $select = array(
            'ID',
            'LID',
            'PERSON_TYPE_ID',

            'PAYED',
            'DATE_PAYED',
            'EMP_PAYED_ID',

            'CANCELED',
            'DATE_CANCELED',
            'EMP_CANCELED_ID',
            'REASON_CANCELED',

            'MARKED',
            'DATE_MARKED',
            'EMP_MARKED_ID',
            'REASON_MARKED',

            'STATUS_ID',
            'DATE_STATUS',

            'PAY_VOUCHER_NUM',
            'PAY_VOUCHER_DATE',
            'EMP_STATUS_ID',

            'PRICE_DELIVERY',
            'ALLOW_DELIVERY',
            'DATE_ALLOW_DELIVERY',
            'EMP_ALLOW_DELIVERY_ID',

            'DEDUCTED',
            'DATE_DEDUCTED',
            'EMP_DEDUCTED_ID',

            'REASON_UNDO_DEDUCTED',

            'RESERVED',
            'PRICE',
            'CURRENCY',
            'DISCOUNT_VALUE',

            'SUM_PAID',
            'USER_ID',
            'PAY_SYSTEM_ID',
            'DELIVERY_ID',

            'DATE_INSERT',
            'DATE_UPDATE',

            'USER_DESCRIPTION',
            'ADDITIONAL_INFO',

            'TAX_VALUE',
            'STAT_GID',
            'RECURRING_ID',
            'RECOUNT_FLAG',

            'DELIVERY_DOC_NUM',
            'DELIVERY_DOC_DATE',
            'STORE_ID',
            'ORDER_TOPIC',

            'RESPONSIBLE_ID',
            'DATE_PAY_BEFORE',
            'DATE_BILL',
            'ACCOUNT_NUMBER',
            'TRACKING_NUMBER',
            'XML_ID'
        );

        $getListParams = array(
            'filter' => $filter,
            'select' => $select
        );

        if ($sortBy == 'STATUS') {
            $getListParams['runtime'] = array(
                new \Bitrix\Main\Entity\ReferenceField(
                    'STATUS',
                    '\Bitrix\Sale\Internals\StatusTable',
                    array(
                        '=this.STATUS_ID' => 'ref.ID',
                    ),
                    array(
                        "join_type" => 'inner'
                    )
                )
            );
            $getListParams['order'] = array("STATUS.SORT" => 'ASC', 'ID' => $sortOrder);
        } else {
            $getListParams['order'] = ['ID' => 'DESC'];
        }

        $code = \Bitrix\Sale\TradingPlatform\Landing\Landing::getCodeBySiteId('s1');
        $platformId = \Bitrix\Sale\TradingPlatform\Landing\Landing::getInstanceByCode($code)->getId();
        if ((int)$platformId > 0) {
            $getListParams['runtime'][] = new Main\ORM\Fields\Relations\Reference(
                'TRADING_BINDING',
                '\Bitrix\Sale\TradingPlatform\OrderTable',
                array(
                    '=this.ID' => 'ref.ORDER_ID',
                    '=ref.TRADING_PLATFORM_ID' => new Main\DB\SqlExpression('?i', $platformId)
                ),
                array(
                    "join_type" => 'inner'
                )
            );
            $getListParams['runtime'][] = new Main\ORM\Fields\Relations\Reference(
                'TRADING',
                '\Bitrix\Sale\TradingPlatformTable',
                array(
                    '=this.TRADING_BINDING.TRADING_PLATFORM_ID' => 'ref.ID',
                    '=ref.CLASS' => new Main\DB\SqlExpression('?', "\\" . Sale\TradingPlatform\Landing\Landing::class)
                ),
                array(
                    "join_type" => 'inner'
                )
            );
        }


        $usePageNavigation = true;

        $totalPages = 0;
        $totalCount = 0;

        $orderClassName = $registry->getOrderClassName();

        \CPageOption::SetOptionString("main", "nav_page_in_session", "N");

        $navyParams = [
            'PAGEN' => $PAGEN,
            'SIZEN' => 10,
            'SHOW_ALL' => false
        ];

        if ($navyParams['SHOW_ALL']) {
            $usePageNavigation = false;
        } else {
            $navyParams['PAGEN'] = (int)$navyParams['PAGEN'];
            $navyParams['SIZEN'] = (int)$navyParams['SIZEN'];

            $navyParams['SIZEN'] = $ORDERS_PER_PAGE;//$this->arParams["ORDERS_PER_PAGE"];


            $getListParams['limit'] = $navyParams['SIZEN'];
            $getListParams['offset'] = $navyParams['SIZEN'] * ($navyParams['PAGEN'] - 1);

            $countParams = [
                "filter" => $getListParams['filter'],
                "select" => [new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(1)')]
            ];

            if (!empty($getListParams['runtime'])) {
                $countParams["runtime"] = $getListParams['runtime'];
            }

            /** @var Main\DB\Result $countQuery */
            $countQuery = $orderClassName::getList($countParams);

            $totalCount = $countQuery->fetch();
            $totalCount = (int)$totalCount['CNT'];
            unset($countQuery);

            if ($totalCount > 0) {

                $totalPages = ceil($totalCount / $navyParams['SIZEN']);

                if ($navyParams['PAGEN'] > $totalPages)
                    $navyParams['PAGEN'] = $totalPages;

                $getListParams['limit'] = $navyParams['SIZEN'];
                $getListParams['offset'] = $navyParams['SIZEN'] * ($navyParams['PAGEN'] - 1);
            } else {
                $navyParams['PAGEN'] = 1;
                $getListParams['limit'] = $navyParams['SIZEN'];
                $getListParams['offset'] = 0;
            }
        }

        $dbQueryResult['ORDERS'] = new \CDBResult($orderClassName::getList($getListParams));

        if ($usePageNavigation) {
            $dbQueryResult['ORDERS']->NavStart($getListParams['limit'], $navyParams['SHOW_ALL'], $navyParams['PAGEN']);
            $dbQueryResult['ORDERS']->NavRecordCount = $totalCount;
            $dbQueryResult['ORDERS']->NavPageCount = $totalPages;
            $dbQueryResult['ORDERS']->NavPageNomer = $navyParams['PAGEN'];

            $dbResult['TOTAL_PAGES'] = $totalPages;
            $dbResult['TOTAL_COUNT'] = $totalCount;
            $dbResult['PAGEN'] = $navyParams['PAGEN'];
        } else {
            if ((int)($ORDERS_PER_PAGE)) {
                $dbQueryResult['ORDERS']->NavStart($ORDERS_PER_PAGE, false);
            }
        }

        if (empty($dbQueryResult['ORDERS'])) {
            return;
        }

        while ($arOrder = $dbQueryResult['ORDERS']->GetNext()) {
            if (
                is_array($arParams['RESTRICT_CHANGE_PAYSYSTEM'])
                && in_array($arOrder['STATUS_ID'], $arParams['RESTRICT_CHANGE_PAYSYSTEM'])
            ) {
                $arOrder['LOCK_CHANGE_PAYSYSTEM'] = 'Y';
            }

            $arOrder['DATE_INSERT'] = $arOrder['DATE_INSERT']->toString();
            $arOrder['DATE_UPDATE'] = $arOrder['DATE_UPDATE']->toString();

            $listOrders[$arOrder["ID"]] = $arOrder;
            $orderIdList[] = $arOrder["ID"];
        }

        $basketClassName = $registry->getBasketClassName();
        /** @var Main\DB\Result $listBaskets */
        $listBaskets = $basketClassName::getList(array(
            'select' => array("*"),
            'filter' => array("ORDER_ID" => $orderIdList),
            'order' => array('NAME' => 'asc')
        ));

        while ($basket = $listBaskets->fetch()) {
            if (\CSaleBasketHelper::isSetItem($basket))
                continue;
            $basket['DATE_INSERT'] = $basket['DATE_INSERT']->toString();
            $basket['DATE_UPDATE'] = $basket['DATE_UPDATE']->toString();

            $resEl = \CIBlockElement::GetByID($basket['PRODUCT_ID']);
            if($ar_res = $resEl->GetNext())
                $basket['PREVIEW_PICTURE'] = \CFile::GetPath($ar_res['DETAIL_PICTURE']);

            $listOrderBasket[$basket['ORDER_ID']][$basket['ID']] = $basket;
            //
            $dbProp = \CSaleBasket::GetPropsList(
                ["ID" => "DESC"],
                ["BASKET_ID" => $basket['ID']]
            );

           while($arProp = $dbProp -> GetNext())
               $listOrderBasket[$basket['ORDER_ID']][$basket['ID']]["PROPS"][] = $arProp;
            //
        }

        $trackingManager = \Bitrix\Sale\Delivery\Tracking\Manager::getInstance();
        $deliveryStatusClassName = $registry->getDeliveryStatusClassName();
        $deliveryStatuses = $deliveryStatusClassName::getAllStatusesNames(LANGUAGE_ID);

        $shipmentClassName = $registry->getShipmentClassName();
        /** @var Main\DB\Result $listShipments */
        $listShipments = $shipmentClassName::getList(array(
            'select' => array(
                'STATUS_ID',
                'DELIVERY_NAME',
                'SYSTEM',
                'DELIVERY_ID',
                'ACCOUNT_NUMBER',
                'PRICE_DELIVERY',
                'DATE_DEDUCTED',
                'CURRENCY',
                'DEDUCTED',
                'TRACKING_NUMBER',
                'ORDER_ID'
            ),
            'filter' => array('ORDER_ID' => $orderIdList)
        ));

        while ($shipment = $listShipments->fetch()) {
            if ($shipment['SYSTEM'] == 'Y')
                continue;

            $shipment['DELIVERY_NAME'] = htmlspecialcharsbx($shipment['DELIVERY_NAME']);
            $shipment["FORMATED_DELIVERY_PRICE"] = SaleFormatCurrency(floatval($shipment["PRICE_DELIVERY"]), $shipment["CURRENCY"]);
            $shipment["DELIVERY_STATUS_NAME"] = $deliveryStatuses[$shipment["STATUS_ID"]];
            if ($shipment["DELIVERY_ID"] > 0 && mb_strlen($shipment["TRACKING_NUMBER"])) {
                $shipment["TRACKING_URL"] = $trackingManager->getTrackingUrl($shipment["DELIVERY_ID"], $shipment["TRACKING_NUMBER"]);
            }
            $listOrderShipment[$shipment['ORDER_ID']][] = $shipment;
        }

        $paymentClassName = $registry->getPaymentClassName();
        /** @var Main\DB\Result $listPayments */
        $listPayments = $paymentClassName::getList(array(
            'select' => array('ID', 'PAY_SYSTEM_NAME', 'PAY_SYSTEM_ID', 'ACCOUNT_NUMBER', 'ORDER_ID', 'PAID', 'SUM', 'CURRENCY', 'DATE_BILL'),
            'filter' => array('ORDER_ID' => $orderIdList)
        ));

        $paymentIdList = array();
        $paymentList = array();

        while ($payment = $listPayments->fetch()) {
            $paySystemFields = $dbResult['PAYSYS'][$payment['PAY_SYSTEM_ID']];
            $payment['PAY_SYSTEM_NAME'] = htmlspecialcharsbx($payment['PAY_SYSTEM_NAME']);
            $payment["FORMATED_SUM"] = SaleFormatCurrency($payment["SUM"], $payment["CURRENCY"]);
            $payment['IS_CASH'] = $paySystemFields['IS_CASH'];
            $payment['NEW_WINDOW'] = $paySystemFields['NEW_WINDOW'];
            $payment['ACTION_FILE'] = $paySystemFields['ACTION_FILE'];
            $payment["PSA_ACTION_FILE"] = htmlspecialcharsbx($arParams["PATH_TO_PAYMENT"]) . '?ORDER_ID=' . urlencode(urlencode($listOrders[$payment["ORDER_ID"]]['ACCOUNT_NUMBER'])) . '&PAYMENT_ID=' . $payment['ACCOUNT_NUMBER'];
            $paymentList[$payment['ID']] = $payment;
            $paymentIdList[] = $payment['ID'];
        }

        $checkList = \Bitrix\Sale\Cashbox\CheckManager::collectInfo(
            array(
                "PAYMENT_ID" => $paymentIdList,
                "ENTITY_REGISTRY_TYPE" => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER
            )
        );

        if (!empty($checkList)) {
            foreach ($checkList as $check) {
                $paymentList[$check['PAYMENT_ID']]['CHECK_DATA'][] = $check;
            }
        }

        foreach ($paymentList as $payment) {
            $listOrderPayment[$payment['ORDER_ID']][] = $payment;
        }

        $orderStatusClassName = $registry->getOrderStatusClassName();
        $allowStatusList = $orderStatusClassName::getAllowPayStatusList();

        foreach ($orderIdList as $orderId) {
            if (!$listOrderShipment[$orderId]) {
                $listOrderShipment[$orderId] = array();
            }
            if (!$listOrderPayment[$orderId]) {
                $listOrderPayment[$orderId] = array();
            }

            if (in_array($listOrders[$orderId]['STATUS_ID'], $allowStatusList)) {
                $listOrders[$orderId]['IS_ALLOW_PAY'] = 'Y';
            } else {
                $listOrders[$orderId]['IS_ALLOW_PAY'] = 'N';
            }

            $dbResult['ORDERS'][] = array(
                "ORDER" => $listOrders[$orderId],
                "BASKET_ITEMS" => $listOrderBasket[$orderId],
                "SHIPMENT" => $listOrderShipment[$orderId],
                "PAYMENT" => $listOrderPayment[$orderId],
            );
        }


        return $dbResult;
    }
}