<?

namespace Godra\Api\Helpers;

use CUser;

class Contract
{
    public function getContractsByContragentCode($contragentCode)
    {
        $result = [];
        
        if ($contragentCode)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_IDKONTRAGENTA' => $contragentCode, '=UF_IDGLAVNOGODOGOVORA' => false]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    if ($item['UF_NOMERDOGOVORA'])
                    {
                        // по номеру договора нам нужно получить все приложения к нему
                        $isExistsChild = false;
                        
                        //$childItems = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_IDGLAVNOGODOGOVORA' => $item['UF_NOMERDOGOVORA']]);
                        $childItems = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_IDGLAVNOGODOGOVORA' => $item['UF_XML_ID']]);
                        
                        if ($childItems['records'])
                        {
                            foreach ($childItems['records'] as $childItem)
                            {
                                if ($childItem['UF_NOMERDOGOVORA'])
                                {
                                    $result[] = 
                                    [
                                        'id'   => $childItem['ID'],
                                        'name' => $item['UF_NOMERDOGOVORA'].'-'.$childItem['UF_NOMERDOGOVORA'],
                                    ];
                                    
                                    $isExistsChild = true;
                                }
                            }
                        }
                        
                        // нужно ли выводить договор, если у него нет приложений?
                        if (!$isExistsChild)
                        {
                            $result[] = 
                            [
                                'name' => $item['UF_NOMERDOGOVORA'],
                            ];
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    // $allContragentContracts - приложения к договорам (с нетронутым id)
    public function getOutletsAndContracts($allContragentContracts)
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $userId = $USER->GetID();
        
        $result = $outletsItems = [];
        
        $outlets = new Outlets();
        
        if ($allContragentContracts)
        {
            // выбранный пользователем договор и торговая точка
            $userSelectedContract = $userSelectedOutltet = '';
 
            if ($userId)
            {
                $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['ID' => $userId], ['SELECT' => ['UF_*']]);

                while ($arUser = $rsUsers->Fetch()) 
                {
                    $userSelectedContract = $arUser['UF_ID_DOGOVOR'];
                    
                    $userSelectedOutltet = $arUser['UF_SALE_POINT'];
                }
            }

            // получаем все торговые точки, которые есть у этих приложений к договорам
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_NOMERDOGOVORA' => $allContragentContracts]);
            
            foreach ($items['records'] as $item)
            {
                if ($item['UF_IDTORGOVYHTOCHEK'])
                {
                    $outletsList = explode(',', $item['UF_IDTORGOVYHTOCHEK']);
                    
                    foreach ($outletsList as $outletsItem)
                    {
                        // для каждой торговой точки закрепляем список договоров в ней
                        if ($outletsItem)
                        {
                            if (!is_array($outletsItems[$outletsItem]))
                            {
                                $outletsItems[$outletsItem] = [];
                                
                                $outletsItems[$outletsItem]['id'] = $outletsItem;
                                
                                $outletsItems[$outletsItem]['address'] = $outlets->getAddressByOutletId($outletsItem); // добавилось позже, для api торговых точек
                                
                                $outletsItems[$outletsItem]['name'] = $outlets->getNameById($outletsItem);
                                
                                $outletsItems[$outletsItem]['contracts'] = [];
                            }
                            
                            $name = $item['UF_NOMERDOGOVORA'];
                            
                            $date = '';
                            
                            // получим номер договора и прикрепим его спереди к номеру приложения
                            //$parentItem = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['UF_NOMERDOGOVORA' => $item['UF_IDGLAVNOGODOGOVORA']]);
                            $parentItem = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['UF_XML_ID' => $item['UF_IDGLAVNOGODOGOVORA']]);
                            
                            if ($parentItem['records'])
                            {
                                foreach ($parentItem['records'] as $parent)
                                {
                                    if ($parent['UF_NOMERDOGOVORA'])
                                    {
                                        $name = $parent['UF_NOMERDOGOVORA'].'-'.$item['UF_NOMERDOGOVORA'];
                                    }
                                    
                                    if ($parent['UF_DATANACHALA'])
                                    {
                                        $date = $parent['UF_DATANACHALA'];
                                    }
                                }
                            }
                            
                            if (!in_array($name, array_column($outletsItems[$outletsItem]['contracts'], 'name')))
                            {
                                $outletsItems[$outletsItem]['contracts'][] = 
                                [
                                    //'id' => $item['UF_NOMERDOGOVORA'],
                                    'id'   => $item['UF_XML_ID'], 
                                    'name' => $name,
                                    'date' => $date,
                                    'selected' => ($userSelectedContract == $item['UF_XML_ID'] && $outletsItem == $userSelectedOutltet ? true : false),
                                ];
                            }
                        }
                    }
                }
            }
            
            if ($outletsItems)
            {
                foreach ($outletsItems as $element)
                {
                    $result[] = $element;
                }
            }
        }
        
        return $result;
    }
    
    // все приложения к договорам у контрагента
    public function getAllContractsByContragentCode($contragentCode)
    {
        $result = [];
        
        if ($contragentCode)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_IDKONTRAGENTA' => $contragentCode, '=UF_IDGLAVNOGODOGOVORA' => false]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    // по номеру договора нам нужно получить все приложения к нему
                    //$childItems = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_IDGLAVNOGODOGOVORA' => $item['UF_NOMERDOGOVORA']]);
                    $childItems = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_IDGLAVNOGODOGOVORA' => $item['UF_XML_ID']]);
                    
                    if ($childItems['records'])
                    {
                        foreach ($childItems['records'] as $childItem)
                        {
                            if ($childItem['UF_NOMERDOGOVORA'])
                            {
                                $result[] = $childItem['UF_NOMERDOGOVORA'];
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    // пока не используется
    // здесь непонятно, толи дату договора надо выводить, толи дату приложения к договору // пока выведем дату самого договора
    public function getDateById($id)
    {
        $result = '';
        
        // $id приложения к договору
        if ($id)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_NOMERDOGOVORA' => $id]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    
                    $result = $item['UF_DATANACHALA'];
                    
                    break;
                }
            }
        }
        
        return $result;
    }
    
    public function getNomenclatureId($id)
    {
        $result = '';
        
        if ($id)
        {
            $utils = new Utility\Misc();
            
            //$items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_NOMERDOGOVORA' => $id]);
            $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_XML_ID' => $id]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    $result = $item['UF_IDASSORTIMENTA'];
                    
                    break;
                }
            }
        }
        
        return $result;
    }
    
    public function getPriceTypeByUserId($userId)
    {
        if ($userId)
        {
            $bitrixUserApi = new Auth\Bitrix();
            
            $userSelectedContract = $bitrixUserApi->getUserSelectedContract($userId);
            
            if ($userSelectedContract)
            {
                $utils = new Utility\Misc();
                
                $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRACT, ['=UF_XML_ID' => $userSelectedContract]);
                
                if ($items['records'])
                {
                    foreach ($items['records'] as $item)
                    {
                        $result = $item['UF_IDTIPACEN'];
                        
                        break;
                    }
                }
            }
        }
        
        return $result;
    }
}
