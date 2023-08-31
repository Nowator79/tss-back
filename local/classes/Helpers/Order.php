<?

namespace Godra\Api\Helpers;

use CFile,
    CModule,
    CSaleOrder,
    CSaleStatus,
    CIBlockElement,
    CCatalogProduct,
    Godra\Api\Notify as Notify;

class Order
{
    private $perPage = 8;
    
    private $excludeStatuses = ['N'];
    
    function __construct()
    {
        CModule::IncludeModule('sale');
    }
    
    // только те, которые отображаются на странице списка заказов в личном кабинете в качестве фильтра
    public function getStatusesForOrdersPage()
    {
        $result = [];
        
        // пока выведем все
        $result = $this->getStatuses();
        
        if ($result)
        {
            foreach ($result as $key => $resultItem)
            {
                if (in_array($key, $this->excludeStatuses))
                {
                    unset($result[$key]);
                }
            }
        }
        
        return $result;
    }
    
    public function getOrders($userId, $page, $status, $dates)
    {
        // https://sibir.fishlab.su/api/order/list?page=1&status=VP&dates=25.11.2022-27.11.2022
        // https://sibir.fishlab.su/api/order/list?status=VP,OB
        
        $result = $filter = [];
        
        $filter['USER_ID'] = $userId;
        
        if ($status)
        {
            $filter['STATUS_ID'] = explode(',', $status); // в ТЗ есть странное требование фильтрации по нескольким статусам заказа, поэтому сделаем так
        }
        
        if ($dates)
        {
            // 02.11.2022-04.11.2022
            $datesArr = explode('-', $dates);
            
            if ($datesArr[0] && $datesArr[1])
            {
                $filter['>=DATE_INSERT'] = date('d.m.Y', strtotime($datesArr[0]));
                $filter['<DATE_INSERT'] = date('d.m.Y', strtotime('+1 day', strtotime($datesArr[1])));
            }
        }
        
        $statuses = $this->getStatuses();
        
        $page = (int)$page;
        
        if (!$page)
        {
            $page = 1;
        }
        
        $arNavStartParams = 
        [
            'iNumPage'  => $page,
            'nPageSize' => $this->perPage,
            'bShowAll'  => true,
        ];
        
        $result['items'] = [];

        $rsOrder = CSaleOrder::GetList(['ID' => 'DESC'], $filter, false, $arNavStartParams);
        
        while ($arOrder = $rsOrder->Fetch())
        {
            $result['items'][] = 
            [
                'id'     => $arOrder['ID'],
                'number' => '№'.$arOrder['ACCOUNT_NUMBER'],
                'date'   => FormatDate('d M Y / H:i', strtotime($arOrder['DATE_INSERT'])),
                'total'  => Utility\Misc::numberFormat($arOrder['PRICE']).' ₽',
                'status' => $statuses[$arOrder['STATUS_ID']],
                'statusId' => $arOrder['STATUS_ID'],
            ];
        }
        
        $result['total'] = $rsOrder->NavRecordCount;
        
        $result['perPage'] = $this->perPage;

        return $result;
    }
    
    public function getOrder($userId, $orderID)
    {
        $result = $filter = $productsList = [];
        
        $status = 0;
        
        $isError = false;
        
        if ($orderID)
        {
            $order = \Bitrix\Sale\Order::load($orderID);

            if ($order)
			{
                $statuses = $this->getStatuses();
                
                $orderUserId = $order->getUserId();
                
                // если передан id пользователя и он не совпадает с пользователем в заказе, значит пользователь пытается открыть не свой заказ
                if ($userId && $orderUserId != $userId)
                {
                    $isError = true;
                    
                    $result['errorText'] = 'Доступ запрещён';
                    
                    $result['errorCode'] = 403;
                }
            }
            else
            {
                $isError = true;
                
                $result['errorText'] = 'Заказ не найден';
                
                $result['errorCode'] = 404;
            }
            
            if (!$isError)
            {
                $basket = $order->getBasket();
                
                $productCount = count($basket->getQuantityList());
                
                $productCountAll = array_sum($basket->getQuantityList());
                
                $basketItems = $basket->getBasketItems();

				foreach ($basket as $basketItem)
				{
					$products[] = $basketItem->getProductId();
				}

                if ($products)
				{
                    $res = CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_CATALOG, 'ID' => $products], false, false, ['ID', 'NAME', 'DETAIL_PICTURE', 'PROPERTY_EXPERATION_DATE', 'PROPERTY_VID_UPAKOVKI', 'PROPERTY_CML2_ARTICLE']);

                    while ($arFields = $res->GetNext())
                    {
                        $productsList[$arFields['ID']] = 
                        [
                            'name'       => $arFields['NAME'],
                            'pic'        => ($arFields['DETAIL_PICTURE'] ? CFile::ResizeImageGet($arFields['DETAIL_PICTURE'], ['width' => 604, 'height' => 604], BX_RESIZE_IMAGE_PROPORTIONAL, false)['src'] : ''),
                            'expiration' => $arFields['PROPERTY_EXPERATION_DATE_VALUE'],
                            'packing'    => $arFields['PROPERTY_VID_UPAKOVKI_VALUE'],
                            'article'    => $arFields['PROPERTY_CML2_ARTICLE_VALUE'],
                        ];
                    }

                    $productsInCart = [];
                    
                    foreach ($basket as $basketItem)
                    {
                        $productId = $basketItem->getProductId();
                        
                        $weight = (float)CCatalogProduct::GetByID($productId)['WEIGHT'];
                        
                        if ($weight > 0)
                        {
                            $weight = Utility\Misc::numberFormat($weight / 1000);
                        }

                        $productsInCart[] = 
                        [
                            'name'       => ($productsList[$productId]['name'] ? $productsList[$productId]['name'] : ''),
                            'pic'        => ($productsList[$productId]['pic'] ? $productsList[$productId]['pic'] : ''),
                            'expiration' => ($productsList[$productId]['expiration'] ? $productsList[$productId]['expiration'] : ''),
                            'weight'     => $weight,
                            'packing'    => ($productsList[$productId]['packing'] ? $productsList[$productId]['packing'] : ''),
                            'qty'        => Utility\Misc::numberFormat($basketItem->getQuantity()),
                            'article'    => ($productsList[$productId]['article'] ? $productsList[$productId]['article'] : ''),
                            'price'      => Utility\Misc::numberFormat($basketItem->getPrice() * $basketItem->getQuantity()).' ₽',
                        ];
                    }
                }
                
                $propertyCollection = $order->getPropertyCollection();
                
                $props = $propertyCollection->getArray()['properties'];
                
                $outletId = $address = '';
                
                if ($props)
                {
                    foreach ($props as $prop)
                    {
                        if ($prop['CODE'] == 'TRADE_POINT_ID')
                        {
                            $outletId = $prop['VALUE'][0];
                            
                            break;
                        }
                    }
                }
                
                if ($outletId)
                {
                    $address = (new Outlets)->getAddressByOutletId($outletId);
                }
                
                $arShipments = [];
                
                $shipmentCollection = $order->getShipmentCollection();
                
                foreach ($shipmentCollection as $shipment)
                {
                    if ($shipment->isSystem())
                    {
                        continue;
                    }
                    
                    $comment = $date = '';
                    
                    $comment = $shipment->getField('COMMENTS');
                    
                    if (mb_stripos($shipment->getField('COMMENTS'), '|') !== false)
                    {
                        $shipmentArr = explode('|', $shipment->getField('COMMENTS'));
                        
                        $date = $shipmentArr[0];
                        
                        $comment = $shipmentArr[1];
                    }
                    
                    
                    $arShipments[] = 
                    [
                        'date'    => ($date ? $date : ''),
                        'comment' => ($comment ? $comment : ''),
                    ];
                }

                $result['order'] = 
                [
                    'id'              => $order->getId(),
                    'number'          => '№'.$order->getField('ACCOUNT_NUMBER'),
                    'total'           => Utility\Misc::numberFormat($order->getPrice()).' ₽',
                    'status'          => $statuses[$order->getField('STATUS_ID')],
                    'statusId'        => $order->getField('STATUS_ID'),
                    'productsCount'   => $productCount,    // количество разных товаров
                    'productCountAll' => $productCountAll, // общее количество товаров
                    //'comment'       => ($order->getField('USER_DESCRIPTION') ? $order->getField('USER_DESCRIPTION') : ''), // комментарий пользователя
                    'comment'         => ($order->getField('COMMENTS') ? $order->getField('COMMENTS') : ''), // комментарий менеджера
                    'products'        => $productsInCart,
                    'delivery'        => 
                    [
                        'address' => ($address ? $address : ''),
                        'items'   => $arShipments,
                    ]
                ];
                
                $status = 1;
            }
        }
        
        $result['status'] = $status;
        
        return $result;
    }
    
    public function copy($userId, $orderId)
    {
        $result= [];
        
        $checkOrderRights = $this->isUserOrder($userId, $orderId);
        
        if ($checkOrderRights)
        {
            $resultCopy = $this->copyOrder($orderId);
            
            $result['status'] = $resultCopy['status'];
            
            $result['errorText'] = $resultCopy['errorText'];
            
            if ($result['errorText'])
            {
                $result['errorCode'] = 422;
            }
        }
        else
        {
            $result['status'] = 0;
            
            $result['errorText'] = 'Доступ запрещён';
            
            $result['errorCode'] = 403;
        }
        
        return $result;
    }
    
    public function delete($userId, $orderId)
    {
        $result = [];
        
        $checkOrderRights = $this->isAllowRemove($userId, $orderId);
        
        if ($checkOrderRights)
        {
            $resultDelete = CSaleOrder::Delete($orderId);
            
            if ($resultDelete)
            {
                $result['status'] = 1;
            }
            else
            {
                $result['status'] = 0;

                $result['errorText'] = 'Ошибка удаления заказа';
                
                $result['errorCode'] = 422;
            }
        }
        else
        {
            $result['status'] = 0;
            
            $result['errorText'] = 'Доступ запрещён';
            
            $result['errorCode'] = 403;
        }
        
        return $result;
    }
    
    public function OnSaleStatusOrderChangeHandler(\Bitrix\Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');

        (new Notify\Sender)->send(['orderId' => $order->getId(), 'orderNum' => $order->getField('ACCOUNT_NUMBER'), 'orderDate' => $order->getDateInsert()->toString(), 'userId' => $order->getUserId(), 'statusId' => $order->getField('STATUS_ID')], new Notify\OrderStatusChangeSender());
	}
    
    public function getStatuses()
    {
        $result = [];
        
        $res = CSaleStatus::GetList(['SORT' => 'ASC'], ['LID' => 'ru', 'TYPE' => 'O']);
        
        while ($arStatus = $res->Fetch())
        {
            $result[$arStatus['ID']] = $arStatus['NAME'];
        }
        
        return $result;
    }
    
    public function OnBeforeOrderUpdateHandler($orderId, &$arFields)
    {
        $newCart = $oldCart = [];
        
        if ($orderId)
        {
            if ($arFields['BASKET_ITEMS'])
            {
                foreach ($arFields['BASKET_ITEMS'] as $item)
                {
                    $newCart[] = $item['PRODUCT_ID'].':'.(float)$item['QUANTITY']; // PRODUCT_ID в корзине может повторяться
                }
            }
            
            $basket = \Bitrix\Sale\Order::load($orderId)->getBasket()->getBasketItems();
            
            foreach ($basket as $basketItem) 
            {
                $oldCart[] = $basketItem->getProductId().':'.(float)$basketItem->getQuantity();
            }
            
            sort($newCart);
            
            sort($oldCart);
            
            if ($newCart != $oldCart)
            {
                (new \Godra\Api\Notify\Sender)->send(['userId' => $arFields['USER_ID'], 'orderId' => $arFields['ID'], 'orderNum' => $arFields['ACCOUNT_NUMBER'], 'oldCart' => $oldCart, 'newCart' => $newCart], new \Godra\Api\Notify\OrderChangeCartSender());
            }
        }
    }
    
	public function getArrByXml($xmlId) {
		$parameters = [
			'filter' => [
				"XML_ID" => $xmlId,
			],
			'order' => ["DATE_INSERT" => "ASC"]
		];
		$dbRes = \Bitrix\Sale\Order::getList($parameters);
		while ($order = $dbRes->fetch())
		{
			return $order;
		}
		return null;
	}

	public function getArrById($id) {
		$parameters = [
			'filter' => [
				"ID" => $id,
			],
			'order' => ["DATE_INSERT" => "ASC"]
		];
		$dbRes = \Bitrix\Sale\Order::getList($parameters);
		while ($order = $dbRes->fetch())
		{
			return $order;
		}
		return null;
	}
	
	public function getDocByOrderId($id) {
		$order_xmlid = (new \Godra\Api\Helpers\Order())->getArrById($id)["XML_ID"];

		$utils = new \Godra\Api\Helpers\Utility\Misc();
		$doc = reset($utils->getHLData(HIGHLOAD_BLOCK_DOCUMENTS, ['=UF_ZAKAZ' => $order_xmlid])["records"]);

		return $doc;
	}

	public function clone(int $id) {
		$order_id = $id;
		$siteId = "n1";
		$order = \Bitrix\Sale\Order::loadByAccountNumber($order_id);
		$currencyCode = \Bitrix\Main\Config\Option::get('sale', 'default_currency', 'RUB');
		$basket = $order->getBasket();
		$fields = $order->getFields();
		$propertyCollection = $order->getPropertyCollection();
		$paymentCollection = $order->getPaymentCollection();
		foreach ($paymentCollection as $payment) {
			$psID = $payment->getPaymentSystemId();
			$psName = $payment->getPaymentSystemName();
		}
		$shipmentCollection = $order->getShipmentCollection();
		foreach ($shipmentCollection as $shipment) {
			if($shipment->isSystem()) continue;
			$shName = $shipment->getField('DELIVERY_NAME');
			$shId = $shipment->getField('DELIVERY_ID');
		}

		global $USER;
		$orderNew = \Bitrix\Sale\Order::create($siteId, $USER->GetID());
		$orderNew->setPersonTypeId(2);
		$basketNew = \Bitrix\Sale\Basket::create($siteId);

		foreach ($basket as $key => $basketItem){
			$item = $basketNew->createItem('catalog', $basketItem->getProductId());
			$item->setFields([
				'QUANTITY'=>$basketItem->getQuantity(),
				'CURRENCY'=>$currencyCode,
				'LID'=>$siteId,
				'PRODUCT_PROVIDER_CLASS'=>'\CCatalogProductProvider',
			]);
		}
		$orderNew->setBasket($basketNew);
		$shipmentCollectionNew = $orderNew->getShipmentCollection();
		$shipmentNew = $shipmentCollectionNew->createItem();
		$shipmentNew->setFields([
			'DELIVERY_ID' => $shId,
			'DELIVERY_NAME' => $shName,
			'CURRENCY' => $order->getCurrency()
		]);
		$shipmentCollectionNew->calculateDelivery();
		$paymentCollectionNew = $orderNew->getPaymentCollection();
		$PaymentNew = $paymentCollectionNew->createItem();
		$PaymentNew->setFields([
			'PAY_SYSTEM_ID' => $psID,
			'PAY_SYSTEM_NAME' => $psName
		]);
		$orderNew->setField('CURRENCY', $currencyCode);

		$orderNew->doFinalAction(true);
		$r = $orderNew->save();
		if (!$r->isSuccess())
		{
			return print_r($r->getErrorMessages(), 1);
		} else {
			return true;
		}
	}

    protected function isAllowRemove($userId, $orderId)
    {
        $result = false;
        
        if ($userId && $orderId)
        {
            $rsOrder = CSaleOrder::GetList([], ['USER_ID' => $userId, 'ID' => $orderId]);
            
            while ($arOrder = $rsOrder->Fetch())
            {
                if ($arOrder['STATUS_ID'] == 'DR')
                {
                    $result = true;
                }
                
                break;
            }
        }
        
        return $result;
    }
    
    protected function copyOrder($id)
    {
        $result = $errors = [];
        
        $status = 0;

        if ($id)
        {
            $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

            $filterFields = 
            [
                'SET_PARENT_ID', 'TYPE',
                'PRODUCT_ID', 'PRODUCT_PRICE_ID', 'PRICE', 'CURRENCY', 'WEIGHT', 'QUANTITY', 'LID',
                'NAME', 'CALLBACK_FUNC', 'NOTES', 'PRODUCT_PROVIDER_CLASS', 'CANCEL_CALLBACK_FUNC',
                'ORDER_CALLBACK_FUNC', 'PAY_CALLBACK_FUNC', 'DETAIL_PAGE_URL', 'CATALOG_XML_ID', 'PRODUCT_XML_ID',
                'VAT_RATE', 'MEASURE_NAME', 'MEASURE_CODE', 'BASE_PRICE', 'VAT_INCLUDED'
            ];
            
            $filterFields = array_flip($filterFields);
            
            $oldOrder = \Bitrix\Sale\Order::load($id);

            $oldBasket = $oldOrder->getBasket();
            
            $refreshStrategy = \Bitrix\Sale\Basket\RefreshFactory::create(\Bitrix\Sale\Basket\RefreshFactory::TYPE_FULL);
            
            $oldBasket->refresh($refreshStrategy);
            
            $oldBasketItems = $oldBasket->getOrderableItems();

            foreach ($oldBasketItems as $oldBasketItem)
            {
                $propertyList = [];
                
                if ($oldPropertyCollection = $oldBasketItem->getPropertyCollection())
                {
                    $propertyList = $oldPropertyCollection->getPropertyValues();
                }

                $item = $basket->getExistsItem($oldBasketItem->getField('MODULE'), $oldBasketItem->getField('PRODUCT_ID'), $propertyList);

                if ($item)
                {
                    $item->setField('QUANTITY', $item->getQuantity() + $oldBasketItem->getQuantity());
                }
                else
                {
                    $item = $basket->createItem($oldBasketItem->getField('MODULE'), $oldBasketItem->getField('PRODUCT_ID'));
                    
                    $oldBasketValues = array_intersect_key($oldBasketItem->getFieldValues(), $filterFields);
                    
                    $item->setField('NAME', $oldBasketValues['NAME']);
                    
                    $resultItem = $item->setFields($oldBasketValues);
                    
                    if (!$resultItem->isSuccess())
                    {
                        continue;
                    }

                    $newPropertyCollection = $item->getPropertyCollection();

                    foreach ($propertyList as $oldPropertyFields)
                    {
                        $propertyItem = $newPropertyCollection->createItem($oldPropertyFields);
                        
                        unset($oldPropertyFields['ID'], $oldPropertyFields['BASKET_ID']);

                        $propertyItem->setFields($oldPropertyFields);
                    }
                }
            }

            $resultAction = $basket->save();
            
            if ($resultAction->isSuccess())
            {
                $status = 1;
            }
            else
            {
                $errorList = $resultAction->getErrors();
                
                foreach ($errorList as $error)
                {
                    $errors[] = $error->getMessage();
                }
                
                $result['errorText'] = implode('; ', $errors);
            }
        }
        
        $result['status'] = $status;
        
        return $result;
    }
    
    protected function isUserOrder($userId, $orderId)
    {
        $result = false;
        
        if ($userId && $orderId)
        {
            $rsOrder = CSaleOrder::GetList([], ['USER_ID' => $userId, 'ID' => $orderId]);
            
            while ($arOrder = $rsOrder->Fetch())
            {
                $result = true;
                
                break;
            }
        }
        
        return $result;
    }
}
