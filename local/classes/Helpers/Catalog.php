<?

namespace Godra\Api\Helpers;

use Bitrix\Main\Loader,
	CIBlockElement,
	CCatalogGroup,
    CPrice;

class Catalog
{
	function __construct()
	{
		Loader::includeModule('iblock');
		Loader::includeModule('catalog');
		Loader::includeModule('sale');
	}

    public function getProductPriceByPriceTypeXmlId($productId, $priceTypeXmlId)
    {
        $result = $priceTypeId = '';
        
        if ($productId && $priceTypeXmlId)
        {
            $dbPriceType = CCatalogGroup::GetList([], ['XML_ID' => $priceTypeXmlId]);
            
            while ($arPriceType = $dbPriceType->Fetch())
            {
                if ($arPriceType['ID'])
                {
                    $priceTypeId = $arPriceType['ID'];
                }
            }
            
            if ($priceTypeId)
            {
                $dbRes = CPrice::GetList([], ['PRODUCT_ID' => $productId, 'CATALOG_GROUP_ID' => $priceTypeId]);
                
                if ($arRes = $dbRes->Fetch())
                {
                    $result = $arRes['PRICE'];
                }
            }                                     
        }
        
        return $result;
    }
	
	public function getpriceTypeIdByTypeXmlId($priceTypeXmlId)
    {
        $result = '';
        
        if ($priceTypeXmlId)
        {
            $dbPriceType = CCatalogGroup::GetList([], ['XML_ID' => $priceTypeXmlId]);
            
            while ($arPriceType = $dbPriceType->Fetch())
            {
                if ($arPriceType['ID'])
                {
                    $result = $arPriceType['ID'];
                }
            }

        }
        
        return $result;
    }
	
	public function getProductPrice($email, $name, $phone, $code, $comment)
	{
		$result = [];
		
		$status = 0;
		
		$errorText = $product = '';
		
		if ($email && $name && $phone && $code)
		{
			$res = CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_CATALOG, 'CODE' => $code], false, false, ['ID', 'NAME', 'DETAIL_PAGE_URL']);
			
			while ($arProduct = $res->GetNext())
			{
				if ($arProduct['DETAIL_PAGE_URL'])
				{
					// костыль для сайта, там очень странные самодельные url товаров
					$urlArr = explode('/', $arProduct['DETAIL_PAGE_URL']);
					
					if ($urlArr[2])
					{
						$arProduct['DETAIL_PAGE_URL'] = '/'.$urlArr[2].'/'.end($urlArr);
					}
				}
				
				$product = $arProduct['NAME'].'; ID - '.$arProduct['ID'].'; Ссылка - https://'.$_SERVER['SERVER_NAME'].$arProduct['DETAIL_PAGE_URL'];
			}
			
			$resultForm = (new \Godra\Api\Services\Form(REQUEST_PRICE_FORM_SID, ['name' => $name, 'email' => $email, 'phone' => $phone, 'comment' => $comment, 'product' => $product]))->addResult();
        
			if (!$resultForm['errors'])
			{
				$status = 1;
			}
			else
			{
				$errorText = implode(', ', $resultForm['errors']);
			}
		}
		
		$result['status'] = $status;
		
		$result['errorText'] = $errorText;
		
		return $result;
	}
}
