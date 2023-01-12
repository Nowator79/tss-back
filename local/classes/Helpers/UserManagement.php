<?

namespace Godra\Api\Helpers;

use CUser;

class UserManagement
{
    private $perPage = 5;
    
    public function getList($userId, $page)
    {
        $result = $outlets = $outletNames = [];
        
        if ($userId)
        {
            $page = (int)$page;
            
            if (!$page)
            {
                $page = 1;
            }
            
            $arNavStartParams = 
            [
                'iNumPage'  => $page,
                'nPageSize' => $this->perPage,
                'bShowAll'  => true,
            ];
            
            $result['items'] = [];
            
            $contract = new Contract();
            
            $outletsInstance = new Outlets();
            
            $contragent = new Contragent();
            
            $contragentCode = $contragent->getContragentCodeByUser($userId, true);
            
            //$rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['=UF_PARENT_USER' => $userId], ['NAV_PARAMS' => $arNavStartParams]);
            $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['=UF_PARENT_USER' => $contragentCode, 'ACTIVE' => 'Y'], ['NAV_PARAMS' => $arNavStartParams]);

            while ($arUser = $rsUsers->Fetch()) 
            {
                $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['USER_ID' => $arUser['ID']]])->fetch()['PHONE_NUMBER'];

                $userOutlets = $outletsInstance->userOutletsToArray($arUser['UF_CONTRACTS']);

                $result['items'][] = 
                [
                    'id'          => $arUser['ID'],
                    'name'        => ($arUser['NAME'] ? $arUser['NAME'] : ''),
                    'login'       => ($arUser['EMAIL'] ? $arUser['EMAIL'] : ''), // login совпадает с Email
                    'phone'       => ($phone ? $phone : ''),
                    'active'      => ($arUser['ACTIVE'] == 'Y' ? true : false),
                    'outlets'     => $userOutlets,
                    'outletNames' => ($userOutlets ? $outletsInstance->getNamesByOutlets(array_keys($userOutlets)) : []),
                ];
                
                $result['total'] = $rsUsers->NavRecordCount;
            
                $result['perPage'] = $this->perPage;
            }

            // все договора контрагента
            if ($contragentCode)
            {
                $allContragentContracts = $contract->getAllContractsByContragentCode($contragentCode);
                
                $result['outlets'] = $contract->getOutletsAndContracts($allContragentContracts);
            }
            
            $result['contragentName'] = $contragent->getContragentByUserId($userId)['name'];
        }

        return $result;
    }
    
    public function userInSuperUserAccount($superUserId, $userId)
    {
        $result = false;
        
        if ($superUserId && $userId)
        {
            $rsUser = CUser::GetByID($userId);
            
            $arUser = $rsUser->Fetch();
            
            $bitrixUserApi = new Auth\Bitrix();
            
            $superUserCode = $bitrixUserApi->getExternalCode($superUserId);
            
            //if ($arUser['UF_PARENT_USER'] == $superUserId)
            if ($arUser['UF_PARENT_USER'] == $superUserCode)
            {
                $result = true;
            }
        }
        
        return $result;
    }
    
    public function switch($userId, $type)
    {
        if ($userId && $type && in_array($type, ['on', 'off']))
        {
            
            (new CUser)->update($userId, ['ACTIVE' => ($type == 'on' ? 'Y' : 'N')]);
        }
    }
    
}
