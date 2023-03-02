<?
namespace Godra\Api\Basket;

use Godra\Api\Helpers\Utility\Misc;

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
    public function delete_new()
    {
        $params = Misc::getPostDataFromJson();
        $itemID = $params['basket_id'];
        $compl_section_id = 1223;

        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        $basketItem = $basket->getItemByBasketCode($itemID);

        $el_id = $basketItem->getProductId();
        if($el_id){
            $filter =[
                'ID'=>$el_id,
                'IBLOCK_ID'=>5,
                'SECTION_ID'=>$compl_section_id
            ];
            $res = \CIBlockElement::GetList(Array(),$filter, false, Array(), Array('*'));
            while($ob = $res->GetNextElement()){
                $arFields = $ob->GetFields();
                \CIBlockElement::Delete($arFields['ID']);
            }
        }
        if($basketItem){
            $result = $basketItem->delete();
            if ($result->isSuccess())
            {
                $basket->save();
                return [];
            }else{
                return [];
            }
        }
    }
    public function byId()
    {
        $this->deleteProductById($this->post_data['element_id'],  $this->post_data['measure_code']);
    }
}
?>