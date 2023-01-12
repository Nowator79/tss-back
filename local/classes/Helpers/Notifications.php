<?

namespace Godra\Api\Helpers;

use CUserFieldEnum;

class Notifications
{
    private $perPage = 14;

    public function getList($page = 1, $userId = 0)
    {
        $result = $filter = [];
        
        $page = (int)$page;
        
        if (!$page)
        {
            $page = 1;
        }
        
        $utils = new Utility\Misc();
        
        if ($userId)
        {
            $filter = ['=UF_USER_ID' => $userId];
        }
        
        $offset = 0;
        
        if ($page > 1)
        {
            $offset = $this->perPage * ($page - 1);
        }
        
        $items = $utils->getHLData(HIGHLOAD_BLOCK_NOTIFICATION, $filter, ['UF_DATE' => 'DESC'], false, $offset, $this->perPage, true);
        
        $result['items'] = [];
        
        //$types = $utils->getValuesByUserFieldId(TYPES_FIELD_ID);

        if ($items['records'])
        {
            foreach ($items['records'] as $item)
            {
                $result['items'][] = 
                [
                    'id'    => (int)$item['ID'],
                    'date'  => FormatDate('d M Y / H:i', $item['UF_DATE']),
                    //'type'  => $types[$item['UF_TYPE']],
                    'type'  => $item['UF_TYPE'],
                    'text'  => $item['UF_TEXT'],
                    'isNew' => (int)$item['UF_IS_NEW'],
                ];
            }
        }
        //Новое сообщение по вашей заявке 	UF_TYPE Тип уведомления 
        $result['total'] = (int)$items['total'];
        
        $result['perPage'] = $this->perPage;
        
        return $result;
    }
    
    public function markAsRead($userId, $notificationId)
    {
        if ($userId)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_NOTIFICATION, ['=UF_USER_ID' => $userId]);
            
            if ($items['records'])
            {
                foreach ($items['records'] as $item)
                {
                    if ($item['ID'])
                    {
                        if ($notificationId)
                        {
                            if ($item['ID'] == $notificationId)
                            {
                                $this->setAsRead($item['ID']);
                                
                                break;
                            }
                        }
                        else
                        {
                            $this->setAsRead($item['ID']);
                        }
                    }
                }
            }
        }
    }
    
    protected function setAsRead($id)
    {
        $result = false;
        
        if ($id)
        {
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(HIGHLOAD_BLOCK_NOTIFICATION_ID)->fetch(); 

			$entityDataClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();
            
            $resultUpdate = $entityDataClass::update($id, ['UF_IS_NEW' => false]);
					
            if ($resultUpdate->isSuccess()) 
            {
                $result = true;
            }
        }
        
        return $result;
    }
}
