<?

namespace Godra\Api;

use Bitrix\Main\EventManager;

class Events 
{
	/**
	 * Регистрация обработчиков событий
	 */
	public static function register() 
	{
		$eventManager = EventManager::getInstance();
		
		$eventManager->addEventHandler('main', 'OnBeforeUserAdd', ['\Godra\Api\Helpers\Auth\Bitrix', 'OnBeforeUserAddHandler']);
		
        $eventManager->addEventHandler('main', 'OnBeforeUserRegister', ['\Godra\Api\Helpers\Auth\Bitrix', 'OnBeforeUserRegisterHandler']);
		
        $eventManager->addEventHandler('main', 'OnBeforeUserUpdate', ['\Godra\Api\Helpers\Auth\Bitrix', 'OnBeforeUserUpdateHandler']);
        
        $eventManager->addEventHandlerCompatible('main', 'OnUserTypeBuildList', ['\Godra\Api\Helpers\WysiwygEditorUserField', 'GetUserTypeDescription']);
        
        $eventManager->AddEventHandler('sale', 'OnSaleStatusOrderChange', ['\Godra\Api\Helpers\Order', 'OnSaleStatusOrderChangeHandler']);
        
        $eventManager->addEventHandler('', HIGHLOAD_BLOCK_DOCUMENTS_ENTITY.'OnAfterAdd', ['\Godra\Api\Helpers\Documents', 'DocumentsOnAfterAddHandler']);
		
		$eventManager->addEventHandler('main', 'OnAfterUserUpdate', ['\Godra\Api\Helpers\Auth\Bitrix', 'OnAfterUserUpdateHandler']);
        
        $eventManager->addEventHandler('sale', 'OnBeforeBasketAdd', ['\Godra\Api\Helpers\Cart', 'OnBeforeBasketAddHandler']);
        
        $eventManager->addEventHandler('sale', 'OnBeforeBasketUpdate', ['\Godra\Api\Helpers\Cart', 'OnBeforeBasketUpdateHandler']);
        
        $eventManager->addEventHandler('sale', 'OnBeforeBasketDelete', ['\Godra\Api\Helpers\Cart', 'OnBeforeBasketDeleteHandler']);
        
        $eventManager->addEventHandler('sale', 'OnBeforeOrderUpdate', ['\Godra\Api\Helpers\Order', 'OnBeforeOrderUpdateHandler']);
        
        $eventManager->addEventHandler('', HIGHLOAD_BLOCK_CONTRAGENT_ENTITY_PRODUCTION.'OnAfterAdd', ['\Godra\Api\Helpers\Contragent', 'ContragentOnAfterAddHandler']);
	}
}
