<?
namespace Godra\Api\Basket;

class DeleteProduct extends Base
{
    /**
     * Отдаётся при /api/map
     * @var array
     */
    protected static $row_data = [
        'element_id' => [
            'mandatory' => true,
            'alias' => 'PRODUCT_ID',
            'description' => 'Ид товара'
        ],
        'measure_code' => [
            'mandatory' => false,
            'alias' => 'measure',
            'description' => 'Единица измерения , Символьный код единицы измерения'
        ],
    ];

    /**
     * Апи код информационного блока каталога
     * @var string
     */
    protected static $api_ib_code = IBLOCK_CATALOG_API;

    /**
     * Удалить товар из корзины по id товара
     * * Отличается от remove тем, что не требует кол-во, а удаляет полностью.
     */
    public function byId()
    {
        $this->deleteProductById($this->post_data['element_id'],  $this->post_data['measure_code']);
    }
}
?>