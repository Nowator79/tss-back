<?
namespace Godra\Api\Basket;

class Order extends Base
{
    /**
     * Отдаётся при /api/map
     * @var array
     */
    protected static $row_data = [
        'shipment_id' => [
            'mandatory' => false,
            'alias' => 'shipment_id',
            'description' => 'Ид доставки'
        ],
    ];

    /**
     * Удалить товар из корзины по id товара
     * * Отличается от remove тем, что не требует кол-во, а удаляет полностью.
     */
    public function add()
    {
        return $this->addOrder();
    }
}
?>