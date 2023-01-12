<?

namespace Godra\Api\Helpers;

use CUser;

class Outlets
{
    private $perPage = 8;
    
    public function getAddressByOutletId($id)
    {
        $result = '';
        
        if ($id)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_OUTLETS, ['=UF_IDTORGOVOJTOCHKI' => $id]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    $result = $item['UF_ADRESTORGOVOJTOCHKI'];
                    
                    break;
                }
            }
        }
        
        return $result;
    }
    
    public function getNameById($id)
    {
        $result = '';
        
        if ($id)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_OUTLETS, ['=UF_IDTORGOVOJTOCHKI' => $id]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    $result = $item['UF_NAZVANIETORGOVOJTOCHKI'];
                    
                    break;
                }
            }
        }
        
        return $result;
    }

    public function getNamesByOutlets($outlets)
    {
        $result = [];
        
        if ($outlets)
        {
            $utils = new Utility\Misc();

            $items = $utils->getHLData(HIGHLOAD_BLOCK_OUTLETS, ['=UF_IDTORGOVOJTOCHKI' => $outlets]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    $result[] = $item['UF_NAZVANIETORGOVOJTOCHKI'];
                }
            }
        }
        
        return $result;
    }
    
    // суперпользователь видит все точки, обычный пользователь видит только привязанные к нему
    public function getList($userId, $page = 1, $showAll = false)
    {
        $result = $resultOutlets = [];

        if ($userId)
        {
            $contract = new Contract();
            
            $contragent = new Contragent();
            
            $bitrixUserApi = new Auth\Bitrix();
            
            $isSuperUser = $bitrixUserApi->isSuperUser();
            
            $contragentCode = $contragent->getContragentCodeByUser($userId, $isSuperUser);
            
            if ($contragentCode)
            {
                $allContragentContracts = $contract->getAllContractsByContragentCode($contragentCode); // вытаскиваются все приложения к договорам
                
                $outletsAndContracts = $contract->getOutletsAndContracts($allContragentContracts);
                
                if ($outletsAndContracts)
                {
                    if ($isSuperUser)
                    {
                        // суперпользователю возвращаем полный набор
                        $resultOutlets = $outletsAndContracts;
                    }
                    else
                    {
                        $rsUser = CUser::GetByID($userId);
        
                        $arUser = $rsUser->Fetch();
                        
                        $userContracts = $arUser['UF_CONTRACTS'];
                        
                        if ($userContracts)
                        {
                            $userOutletsAndContracts = $this->userOutletsToArray($userContracts);
                            
                            $userOutlets = array_keys($userOutletsAndContracts);

                            // у обычного пользователя отображаем только те договора-торговые точки, которые привязаны к нему
                            foreach ($outletsAndContracts as $key => $allContract)
                            {
                                // если такой торговой точки нет у пользователя, то удаляем её
                                if (!in_array($allContract['id'], $userOutlets))
                                {
                                    unset($outletsAndContracts[$key]);
                                }
                                else
                                {
                                    // если торговая точка есть, то проверяем все договора в ней
                                    foreach ($allContract['contracts'] as $keyContract => $allContractItem)
                                    {
                                        if (!in_array($allContractItem['id'], $userOutletsAndContracts[$allContract['id']]))
                                        {
                                            unset($outletsAndContracts[$key]['contracts'][$keyContract]);
                                        }
                                    }
                                }
                            }
                            
                            // удалим точки без договоров
                            foreach ($outletsAndContracts as $key => $allContract)
                            {
                                if (!$allContract['contracts'])
                                {
                                    unset($outletsAndContracts[$key]);
                                }
                            }
                            
                            $resultOutlets = $outletsAndContracts;
                        }
                    }
                    
                    $result['total'] = count($resultOutlets);
                    
                    $result['perPage'] = $this->perPage;
                    
                    // пагинация
                    if ($resultOutlets)
                    {
                        // выводит торговые точки с договорами в публичной части, в будущем нужно будет добавить проверку на дату нчала и дату окончания договора, чтобы не попадали в выдачу, в личном кабинете при этом выводятся все
                        if (!$showAll)
                        {
                            $page = (int)$page;
                            
                            if (!$page)
                            {
                                $page = 0;
                            }
                            else
                            {
                                $page = $page - 1;
                            }
                            
                            $result['items'] = array_chunk($resultOutlets, $this->perPage)[$page];
                        }
                        else
                        {
                            $result['items'] = $resultOutlets;
                            
                            unset($result['total']);
                            
                            unset($result['perPage']);
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function userOutletsToArray($outlets)
    {
        $result = [];
                
        if ($outlets)
        {
            $outletList = explode(',', $outlets);

            foreach ($outletList as $outletItem)
            {
                $outletArr = explode(':', $outletItem);

                if (is_array($outletArr) && count($outletArr) == 2 && $outletArr[0] && $outletArr[1])
                {
                    if (!in_array($outletArr[1], $result))
                    {
                        $result[$outletArr[0]][] = $outletArr[1];
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function set($userId, $outlet, $contract)
    {
        if ($userId && $outlet && $contract)
        {
            (new CUser)->Update($userId, ['UF_SALE_POINT' => $outlet, 'UF_ID_DOGOVOR' => $contract]);
        }
    }
}
