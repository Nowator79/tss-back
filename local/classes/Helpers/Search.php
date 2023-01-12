<?

namespace Godra\Api\Helpers;

use \Bitrix\Main\Loader,
	CSearchLanguage,
	CCatalogProduct,
	CIBlockElement,
	CSearch,
	CFile;

class Search
{
    private $perPage = 8;
    
    function __construct()
    {
        Loader::includeModule('search');
        
        Loader::includeModule('catalog');
    }
    
    // в поиске в каталоге участвуют - имя товара, описание и свойства - бренд [48], состав [47], производитель [46]
    // в акциях - наименование и описание
    public function searchProcess($query)
	{
		$result = $products = $sales = [];
		
		if ($query)
		{
            //$query = $this->prepareQuery($query); // битрикс косячит и переводит нормальные руссские слова
            
            // товары каталога
            $searchResultCatalog = $this->search($query, IBLOCK_CATALOG);
            
            // если пользователь авторизован, то он привязан к договору, нужно удалить товары, которые недоступны пользователю
            if ($searchResultCatalog)
            {
                $bitrixUserApi = new Auth\Bitrix();
                
                if ($bitrixUserApi->isAuth())
                {
                    $userId = $bitrixUserApi->getUserId();
                    
                    if ($userId)
                    {
                        $nomenclature = (new Nomenclature)->getByUserId($userId);
                        
                        foreach ($searchResultCatalog as $key => $productItem)
                        {
                            if (!$productItem['ITEM_ID'] || !$nomenclature[$productItem['ITEM_ID']])
                            {
                                unset($searchResultCatalog[$key]);
                            }
                        }
                    }
                }
            }
            
            if ($searchResultCatalog)
            {
                $products = $this->processCatalogItems($searchResultCatalog);
            }

            // акции
            $searchResultSales = $this->search($query, IBLOCK_SALES);
            
            if ($searchResultSales)
            {
                $sales = $this->processSalesItems($searchResultSales);
            }
            
            // объединим товары и акции
            $itemsAll = array_merge($products, $sales);
            
            // обрежем до максимального количества
            $itemsAll = array_chunk($itemsAll, $this->perPage)[0];
            
            $result = 
            [
                'items' => $itemsAll,
                'total' => 
                [
                    //'catalog' => count($searchResultCatalog),
                    //'sales'   => count($searchResultSales),
                    //'all'     => count($searchResultCatalog) + count($searchResultSales),
                    
                    'catalog' => count($products),
                    'sales'   => count($sales),
                    'all'     => count($products) + count($sales),
                ]
            ];
		}
		
		return $result;
	}
    
    protected function processSalesItems($items)
    {
        $result = $offers = $sales = [];
        
        $i = 0;
        
        if ($items)
        {
            foreach ($items as $item)
            {
                if ($item['ITEM_ID'] && !in_array($item['ITEM_ID'], $offers))
                {
                    $offers[] = $item['ITEM_ID'];
                    
                    $names[$item['ITEM_ID']] = $item['TITLE'];
                }
                
                if ($offers)
                {
                    $offers = array_chunk($offers, $this->perPage)[0];
                
                    foreach ($offers as $offer)
                    {
                        $sales[$offer] = $offer;
                    }

                    if ($sales)
                    {
                        $sales = array_unique($sales);

                        $filter = ['IBLOCK_ID' => IBLOCK_SALES, 'ID' => $sales];
                        
                        $salesData = $this->getSearchSalesData($filter);
                        
                        if ($salesData)
                        {
                            foreach ($salesData as $key => $saleItem)
                            {
                                if ($saleItem)
                                {
                                    $saleItem['URL'] = str_replace('/promotions/', '/', $saleItem['URL']);

                                    $result[] = 
                                    [
                                        'name'    => $names[array_search($key, $sales)],
                                        'url'     => $saleItem['URL'],
                                        'price'   => '',
                                        'pic'     => $saleItem['PIC'],
                                        'measure' => '',
                                        'type'    => 'sales',
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    protected function getSearchSalesData($filter)
	{
		$result = [];

		if ($filter)
		{
			$res = CIBlockElement::GetList([], $filter, false, false, ['ID', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE']);

			while ($ob = $res->GetNextElement())
			{
				$arFields = $ob->GetFields();

                if (!$arFields['DETAIL_PICTURE'])
                {
                    $arFields['DETAIL_PICTURE'] = $arFields['PREVIEW_PICTURE'];
                }
                
				$result[$arFields['ID']] = 
				[
					'URL'     => $arFields['DETAIL_PAGE_URL'],
					'PIC'     => ($arFields['DETAIL_PICTURE'] ? CFile::ResizeImageGet($arFields['DETAIL_PICTURE'], ['width' => 604, 'height' => 604], BX_RESIZE_IMAGE_PROPORTIONAL, false)['src'] : ''),
				];
			}
		}
		
		return $result;
	}
    
    protected function processCatalogItems($items)
    {
        $result = $names = $products = $offers = [];
        
        if ($items)
        {
            foreach ($items as $item)
            {
                if ($item['ITEM_ID'] && !in_array($item['ITEM_ID'], $offers))
                {
                    $offers[] = $item['ITEM_ID'];
                    
                    $names[$item['ITEM_ID']] = $item['TITLE'];
                }
            }
            
            if ($offers)
            {
                $offers = array_chunk($offers, $this->perPage)[0];
                
                foreach ($offers as $offer)
                {
                    $products[$offer] = $offer;
                }

                if ($products)
                {
                    $products = array_unique($products);

                    // получим ссылки на товары и прочую информацию
                    $filter = ['IBLOCK_ID' => IBLOCK_CATALOG, 'ID' => $products];
                    
                    $productsData = $this->getSearchProductsData($filter);
                    
                    if ($productsData)
                    {
                        foreach ($productsData as $key => $productItem)
                        {
                            if ($productItem)
                            {
                                if ($productItem['URL'])
                                {
                                    // костыль для сайта, там очень странные самодельные url товаров
                                    $urlArr = explode('/', $productItem['URL']);
                                    
                                    if ($urlArr[2])
                                    {
                                        //$productItem['URL'] = '/catalog/'.$urlArr[2].'/'.end($urlArr);
                                        $productItem['URL'] = '/'.$urlArr[2].'/'.end($urlArr);
                                    }
                                }
                                
                                $price = '';
                                
                                $bitrixUserApi = new Auth\Bitrix();
                                
                                if ($bitrixUserApi->isAuth())
                                {
                                    $userId = $bitrixUserApi->getUserId();
                                    
                                    if ($userId)
                                    {
                                        // вытаскиваем тип цен UF_IDTIPACEN из таблицы договоров
                                        $priceType = (new Contract)->getPriceTypeByUserId($userId);
                                        
                                        $price = (new Catalog)->getProductPriceByPriceTypeXmlId($productItem['ID'], $priceType);

                                        if ($price)
                                        {
                                            $price = Utility\Misc::numberFormat($price).' ₽';
                                        }
                                    }
                                }
                                
                                $result[] = 
                                [
                                    'name'    => $names[array_search($key, $products)],
                                    'url'     => $productItem['URL'],
                                    'price'   => $price,
                                    'pic'     => $productItem['PIC'],
                                    'measure' => $productItem['MEASURE'],
                                    'type'    => 'catalog',
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    protected function search($query, $iblock)
    {
        $result = [];
        
        $obSearch = new CSearch();

        // отключаем морфологию, так поиск более вменяем
        $obSearch->Search(
            [
                'SITE_ID'    => 's1',
                'QUERY'      => $query,
                '=MODULE_ID' => 'iblock',
                '=PARAM2'    => $iblock,
            ],
            [],
            ['STEMMING' => false]
        );
        
        // если ничего не нашли, то ищем с морфологией, например "гайки" без морфологии не ищет, а "гайка" находит
        if (!$obSearch->errorno)
        {
            if (!$obSearch->result->num_rows)
            {
                $obSearch = new CSearch();
                
                $obSearch->Search(
                    [
                        'SITE_ID'    => 's1',
                        'QUERY'      => $query,
                        '=MODULE_ID' => 'iblock',
                        '=PARAM2'    => $iblock,
                    ]
                );
            }
        }
        
        if (!$obSearch->errorno)
        {
            while ($arSearchResult = $obSearch->GetNext())
            {
                $result[] = $arSearchResult;
            }
        }

        return $result;
    }
    
    protected function prepareQuery($query)
    {
        // корректировка раскладки клавиатуры
        // в битриксе работает глючно, переделывает слово круг в rheu, нужно ставить исключения
        if ($query)
        {
            if (!$this->excludeTranslate($query))
            {
                $arLang = CSearchLanguage::GuessLanguage($query);
                
                if (is_array($arLang) && $arLang['from'] != $arLang['to'])
                {
                    $query = CSearchLanguage::ConvertKeyboardLayout($query, $arLang['from'], $arLang['to']);
                }
            }
        }
        
        return $query;
    }
	
	protected function getSearchProductsData($filter)
	{
		$result = [];

		if ($filter)
		{
			$res = CIBlockElement::GetList([], $filter, false, false, ['ID', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE']);

			while ($ob = $res->GetNextElement())
			{
				$arFields = $ob->GetFields();
                
                if (!$arFields['DETAIL_PICTURE'])
                {
                    $arFields['DETAIL_PICTURE'] = $arFields['PREVIEW_PICTURE'];
                }

				$result[$arFields['ID']] = 
				[
                    'ID'      => $arFields['ID'],
					'URL'     => $arFields['DETAIL_PAGE_URL'],
					'PRICE'   => CCatalogProduct::GetOptimalPrice($arFields['ID'])['RESULT_PRICE']['DISCOUNT_PRICE'],
					'PIC'     => ($arFields['DETAIL_PICTURE'] ? CFile::ResizeImageGet($arFields['DETAIL_PICTURE'], ['width' => 604, 'height' => 604], BX_RESIZE_IMAGE_PROPORTIONAL, false)['src'] : ''),
                    'MEASURE' => \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure($arFields['ID'])[$arFields['ID']]['MEASURE']['SYMBOL_RUS'],
				];
			}
		}
		
		return $result;
	}
	
	protected function excludeTranslate($query)
	{
		$result = false;
		
		$words = ['круг', 'круги'];
		
		if (in_array($query, $words))
		{
			$result = true;
		}
		
		return $result;
	}

}