<?

namespace Godra\Api\Helpers;

use CIBlockElement;

class Nomenclature
{
	// array [productId] => xml_id
    public function getByUserId($userId)
    {
        $result = [];
        
        if ($userId)
        {
            $bitrixUserApi = new Auth\Bitrix();
            
            $userSelectedContract = $bitrixUserApi->getUserSelectedContract($userId);
            
            if ($userSelectedContract)
            {
                $nomenclatureId = (new Contract)->getNomenclatureId($userSelectedContract);
                
				// id ассортимента
                if ($nomenclatureId)
                {
                    $map = [];
                    
                    $utils = new Utility\Misc();
                    
					// старый вариант, по xml_id
					
					/*
					// все товары каталога
                    $res = CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_CATALOG], false, false, ['ID', 'XML_ID']);
		
                    while ($arFields = $res->GetNext())
                    {
                        if ($arFields['XML_ID'] && $arFields['ID'])
                        {
                            $map[$arFields['XML_ID']] = $arFields['ID'];
                        }
                    }

					// 123 => xml_id[be707f6b-e077-11ea-ab76-00155d0a8003, 1d41bc38-3bdd-11e8-ab34-00155d07b505]
                    $items = $utils->getHLData(HIGHLOAD_BLOCK_NOMENCLATURE, ['=UF_IDASSORTIMENTA' => $nomenclatureId]);
                    
                    if ($items['records'])
                    {
                        foreach ($items['records'] as $item)
                        {
                            if ($map[$item['UF_IDNOMENKLATURY']])
                            {
                                $result[$map[$item['UF_IDNOMENKLATURY']]] = $item['UF_IDNOMENKLATURY']; // productId => xml_id
                            }
                        }
                    }
					*/
					
					// новый вариант, по артикулам
					
					// все товары каталога
                    $res = CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_CATALOG], false, false, ['ID', 'XML_ID', 'PROPERTY_CML2_ARTICLE']);
		
                    while ($arFields = $res->GetNext())
                    {
                        if ($arFields['PROPERTY_CML2_ARTICLE_VALUE'] && $arFields['ID'])
                        {
                            $map[$arFields['PROPERTY_CML2_ARTICLE_VALUE']] = ['ID' => $arFields['ID'], 'XML_ID' => $arFields['XML_ID']];
                        }
                    }
					
					// 123 => articles[456,789]
					$items = $utils->getHLData(HIGHLOAD_BLOCK_NOMENCLATURE, ['=UF_IDASSORTIMENTA' => $nomenclatureId]);
					
					if ($items['records'])
                    {
                        foreach ($items['records'] as $item)
                        {
							$articles = explode(',', $item['UF_IDNOMENKLATURY']);
							
							if ($articles)
							{
								foreach ($articles as $article)
								{
									if ($map[$article]['ID'])
									{
										$result[$map[$article]['ID']] = $map[$article]['XML_ID'];
									}
								}
							}
                        }
                    }
                }
            }
        }
        
        return $result;
    }
}