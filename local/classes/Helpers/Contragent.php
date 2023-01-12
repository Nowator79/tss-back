<?

/*
Контрагенты - просто список
Контрагент обязательно должен быть связан с Договором
С контрагентом могут быть связаны несколько Договоров

Договор обязательно должен быть связан с только с одним Контрагентом;
Договор является связующим звеном между Контрагентом и Приложением к Договору;
Приложение у договора есть всегда;

Приложение обязательно должно быть связано только с одним Договором;
Приложение является связующим звеном между Договором и складом;
Приложение обуславливает доступные для торговой точки номенклатуру и цены;
ИдСкладов ИдТипаЦен ИдТорговыхТочек ТорговыйПредставитель

Торговая точка обязательно должна быть связана с Приложением к Договору. Через приложение происходит определение доступной для торговой точки номенклатуры и цен;
ПриложенияКДоговорам (множество)

Информация о торговых представителях необходима для участия в форме “Вызвать торгового представителя”;
В частности поле - Электронная почта
*/

namespace Godra\Api\Helpers;

class Contragent
{
    public function getContragentByUserId($userId)
    {
        $result = [];
        
        if ($userId)
        {
            $utils = new Utility\Misc();
            
            $bitrixUserApi = new Auth\Bitrix();
            
            $isSuperUser = $bitrixUserApi->isSuperUser();
            
            $contragentCode = $this->getContragentCodeByUser($userId, $isSuperUser);

            if ($contragentCode)
            {
                $contracts = (new Contract)->getContractsByContragentCode($contragentCode);
                
                $items = $utils->getHLData(HIGHLOAD_BLOCK_CONTRAGENT, ['=UF_IDKONTRAGENTA' => $contragentCode]);
                
                if ($items['records'])
                {
                    foreach ($items['records'] as $item)
                    {
                        $result = 
                        [
                            'name'      => $item['UF_NAME'],
                            'inn'       => $item['UF_INN'],
                            'kpp'       => $item['UF_KPP'],
                            'ogrn'      => $item['UF_OGRN'],
                            'okpo'      => $item['UF_OKPO'],
                            'address'   => $item['UF_ADRESREGISTRACII'],
                            'contracts' => $contracts,
                        ];

                        break;
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function getContragentCodeByUser($userId, $isSuperUser)
    {
        $result = '';
        
        $bitrixUserApi = new Auth\Bitrix();
        
        if ($userId)
        {
            if ($isSuperUser)
            {
                // у суперпользователя связь с контрагентом идёт через внешний код
                $result = $bitrixUserApi->getExternalCode($userId);
            }
            else
            {
                // у обычного пользователя сначала нужно достать его связь с суперпользователем, а затем у того вытащить внешний код
                
                /*
                $parentUserId = $bitrixUserApi->getParentUserId($userId);
                
                if ($parentUserId)
                {
                    //$result = $bitrixUserApi->getExternalCode($parentUserId);
                    $result = $bitrixUserApi->getExternalCode($parentUserId);
                }
                */
                
                $result = $bitrixUserApi->getParentUserId($userId);
            }
        }
        
        return $result;
    }
    
    public function ContragentOnAfterAddHandler(\Bitrix\Main\Entity\Event $event)
    {
		$entityFields = $event->getParameter('fields');
        
        (new Auth\Bitrix)->addUserFromContragent($entityFields['UF_XML_ID'], $entityFields['UF_ELEKTRONNAYAPOCHT'], $entityFields['UF_PREDSTAVITELFIO'], $entityFields['UF_TELEFONRABOCHIY'], $entityFields['UF_PREDSTAVITELDOLZH']);
    }
}
