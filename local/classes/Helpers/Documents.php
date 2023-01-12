<?

namespace Godra\Api\Helpers;

use Cutil,
    Godra\Api\Notify as Notify;

class Documents
{
    private $perPage = 4;
    
    private $path;

    function __construct()
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'].'/upload/documents/';
    }
    
    public function getList($page, $dates, $type, $contragentCode)
    {
        // ИД            // UF_XML_ID        // f065888e-7924-11df-b33a-0011955cba6b
        // ТипДокумента  // UF_TIPDOCUMENTA  // Счёт/Товарная-Накладная/Сфёт-Фактура/УПД/Акт-сверки
        // Название      // UF_NAME          // Акт Сверки №1024
        // Файл          // UF_FILE          // 12c4c9e9-3465-11eb-ab52-00155d0a8009/Товарная_накладная_14.docx // /12c4c9e9-3465-11eb-ab52-00155d0a8009/счет.doc
        // ИдКонтрагента // UF_IDKONTRAGENTA // 12c4c9e9-3465-11eb-ab52-00155d0a8009
        // Дата          // UF_DATE          // 10.10.2022

        // /api/documents/list?page=1&type=Счёт&dates=10.10.2022-12.10.2022

        $result = $filter = [];
        
        $page = (int)$page;
        
        if (!$page)
        {
            $page = 1;
        }
        
        $utils = new Utility\Misc();
        
        if ($contragentCode)
        {
            $filter = ['=UF_IDKONTRAGENTA' => $contragentCode];
        }

        if ($dates)
        {
            // 02.11.2022-04.11.2022
            $datesArr = explode('-', $dates);
            
            if ($datesArr[0] && $datesArr[1])
            {
                $filter['>=UF_DATE'] = date('d.m.Y', strtotime($datesArr[0]));
                $filter['<UF_DATE'] = date('d.m.Y', strtotime('+1 day', strtotime($datesArr[1])));
            }
        }
        
        if ($type)
        {
            $filter = ['=UF_TIPDOCUMENTA' => $type];
        }
        
        $offset = 0;
        
        if ($page > 1)
        {
            $offset = $this->perPage * ($page - 1);
        }
        
        $items = $utils->getHLData(HIGHLOAD_BLOCK_DOCUMENTS, $filter, ['UF_DATE' => 'DESC'], false, $offset, $this->perPage, true);
        
        $result['items'] = [];
        
        $result['types'] = $this->getDocumentTypes($contragentCode);
        
        if ($items['records'])
        {
            foreach ($items['records'] as $item)
            {
                $result['items'][] = 
                [
                    'id'    => (int)$item['ID'],
                    'name'  => $item['UF_NAME'],
                    //'date'  => $item['UF_DATE']->toString(),
                ];
            }
        }
        
        $result['total'] = (int)$items['total'];
        
        $result['perPage'] = $this->perPage;

        return $result;
    }
    
    public function download($id, $contragentCode)
    {
        $status = 0;
        
        if ($id && $contragentCode)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_DOCUMENTS, ['=UF_IDKONTRAGENTA' => $contragentCode, '=ID' => $id]);
            
            if ($items['records'])
            {
                 foreach ($items['records'] as $item)
                 {
                     if ($item['UF_FILE'] && file_exists($this->path.$item['UF_FILE']))
                     {
                        $this->outputFile($this->path.$item['UF_FILE']);
                        
                        $status = 1;
                     }
                 }
            }
        }
        
        return $status;
    }
    
    protected function outputFile($path)
    {
        if (file_exists($path))
        {
            $fileName = array_pop(explode(DIRECTORY_SEPARATOR, $path));
            
            /*
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') > 0)
            {
                header('Content-Disposition: attachment; filename="'.rawurlencode($fileName ).'"');
            }
            else
            {
                header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode($fileName));
            }
            */

            header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode($fileName));
            
            header('Content-Description: File Transfer');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: '.mime_content_type($path));
            header('Content-Length: '.filesize($path));
            header('Pragma: public');
            header('HTTP/2 200 OK');
          
            flush();
            readfile($path);
            exit;
        }
    }
    
    protected function getDocumentTypes($contragentCode)
    {
        $result = [];
        
        if ($contragentCode)
        {
            $utils = new Utility\Misc();
            
            $items = $utils->getHLData(HIGHLOAD_BLOCK_DOCUMENTS, ['=UF_IDKONTRAGENTA' => $contragentCode]);
            
            if ($items['records'])
            {
                 foreach ($items['records'] as $item)
                 {
                     if ($item['UF_TIPDOCUMENTA'])
                     {
                        $result[] = $item['UF_TIPDOCUMENTA'];
                     }
                 }
            }
            
            $result = array_unique($result);
        }
        
        return $result;
    }
    
    public function DocumentsOnAfterAddHandler(\Bitrix\Main\Entity\Event $event)
    {
		$entityFields = $event->getParameter('fields');

		// справочники поменяются, поэтому все имена полей оставим только в этом файле, "один файл - один класс - один справочник - изменения в одном месте"
		$fields = 
		[
			'XML_ID'     => $entityFields['UF_XML_ID'],
			'NAME'       => $entityFields['UF_NAME'],
			'TYPE'       => $entityFields['UF_TIPDOCUMENTA'],
			'CONTRAGENT' => $entityFields['UF_IDKONTRAGENTA'],
			'FILE'       => $entityFields['UF_FILE'],
			'DATE'       => $entityFields['UF_DATE']->toString(),
		];
		
        (new Notify\Sender)->send(['id' => $event->getParameter('id'), 'fields' => $fields], new Notify\DocumentReceiveSender());
    }
}
