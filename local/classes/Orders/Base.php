<?
namespace Godra\Api\Orders;
use \Bitrix\Main,
    \Bitrix\Sale,
    \Bitrix\Currency,
    \Bitrix\Sale\Order,
    \Bitrix\Sale\Basket,
    \Bitrix\Main\Loader,
    \Bitrix\Main\Context,
    \Bitrix\Sale\Delivery,
    \Bitrix\Sale\Affiliate,
    \Bitrix\Sale\PaySystem,
    \Bitrix\Main\Application,
    \Godra\Api\Helpers\Utility\Misc,
    \Bitrix\Main\Engine\CurrentUser,
    \Bitrix\Currency\CurrencyManager,
    \Godra\Api\Helpers\Utility\Errors,
    \Bitrix\Sale\DiscountCouponsManager,
    \Bitrix\Main\Localization\Loc as Loc,
    \Bitrix\Sale\Delivery\Services\Manager,
    \Bitrix\Sale\Delivery\Services\EmptyDeliveryService;

abstract class Base
{
    protected $user;
    protected $fuser;
    protected $site_id;
    protected $post_data;

    function __construct()
    {
        Misc::checkRowsV2($this->post_data, $this->row_data);
        Misc::includeModules(['iblock', 'catalog', 'sale', 'currency']);

        // инициализация купонов
        DiscountCouponsManager::init();

        $this->site_id   = Context::getCurrent()->getSite();
        $this->user      = $this->getUser();
        $this->fuser     = $this->getFuserId();
        $this->currency  = $this->getBaseCurrency();
        $this->post_data = Misc::getPostDataFromJson();
    }

    /**
     * Получить заказы.
     * # надо допилить получение состава заказов + допил оплаты и доставки
     */
    protected function getOrders()
    {
        $filter[] = [];
        $filter['USER_ID'] = $this->user['id'];

        if (!empty($this->post_data['id'])) $filter['ID'] = (int) $this->post_data['id'];

        $db_orders = \Bitrix\Sale\Order::getList([
            'select'   => ['*'],
            'order'    => ['ID' => 'DESC'],
            'filter'   => $filter
        ])->fetchAll();

        foreach ($db_orders as $order)
        {
            $basketItems = (Sale\Order::load($order['ID'])->getBasket())->getBasketItems();

            foreach ($basketItems as $item)
                $products[] = [
                    'id'           => $item->getId(),
                    'name'         => $item->getField('NAME'),
                    'price'        => $item->getPrice(),
                    'props'        => $item->getPropertyCollection()->getPropertyValues(),
                    'weight'       => $item->getWeight(),
                    'can_buy'      => $item->canBuy(),
                    'quantity'     => $item->getQuantity(),
                    'product_id'   => $item->getProductId(),
                    'final_price'  => $item->getFinalPrice(),
                    'measure_code' => $item->getField('MEASURE_CODE'),
                    'measure_name' => $item->getField('MEASURE_NAME'),
                ];

            // отгрузки

            $rsShipment = \Bitrix\Sale\Internals\ShipmentTable::getList(array(
                'filter'=>array('ORDER_ID' => $order['ID']),
            ));

            $shipments = [];

            while ($shipment = $rsShipment->Fetch()) {
                $shipments[] = $shipment;
            }

//            foreach ($collection as $shipment) {
//                //$shipment = $collection->getItemById($id);
//
//                $shipments[] = [
//                    'id' => $shipment->getField('ID'),
//                    'doc_date' => $shipment->getField('DELIVERY_DOC_DATE'),
//                    'comment' => $shipment->getField('COMMENTS'),
//                ];
//            }



            $result[] = [
                'products'       => $products,
                'id'             => $order['ID'],
                'currency'       => $order['CURRENCY'],
                'delivery_id'    => $order['DELIVERY_ID'],
                'account_number' => $order['ACCOUNT_NUMBER'],
                'payed'          => $order['PAYED'] != 'N',
                'canceled'       => $order['CANCELED'] != 'N',
                'updated_1c'     => $order['UPDATED_1C'] != 'N',
                'date_payed'     => FormatDate("d F Y", MakeTimeStamp($order['DATE_PAYED'])),
                'date_insert'    => FormatDate("d F Y", MakeTimeStamp($order['DATE_INSERT'])),
                'date_update'    => FormatDate("d F Y", MakeTimeStamp($order['DATE_UPDATE'])),
                'price'          => \CCurrencyLang::CurrencyFormat($order['PRICE'], $order['CURRENCY']),
                'sum_paid'       => \CCurrencyLang::CurrencyFormat($order['SUM_PAID'], $order['CURRENCY']),
                'price_delivery' => \CCurrencyLang::CurrencyFormat($order['PRICE_DELIVERY'], $order['CURRENCY']),
                'discount_value' => \CCurrencyLang::CurrencyFormat($order['DISCOUNT_VALUE'], $order['CURRENCY']),
                'shipmentCollection' => $shipments ?? []
            ];
        }


        return $order;

        if (!empty($this->post_data['id'])) $result = array_values($result);

        return $result ?: null;
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
}
?>