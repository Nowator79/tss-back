<?php

namespace Godra\Api\Test;

use Godra\Api\Helpers\Utility\Misc;
use Bitrix\Sale\Order,
    Bitrix\Sale,
    Bitrix\Currency;
use Bitrix\Main\Context;

class Base
{
    protected static $orderFields = [
        'FIO',
        'PHONE',
        'EMAIL',
        'DELIVERY_ADRESS'
    ];

    public static function Test()
    {
        $params = Misc::getPostDataFromJson();
        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");
        global $USER;
        $useId = ($USER->GetID() == 0) ? 1 : $USER->GetID();

        $siteId = Context::getCurrent()->getSite();
        $currencyCode = Currency\CurrencyManager::getBaseCurrency();
        $order = Order::create($siteId, $USER->isAuthorized() ? $useId : 1);
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
}