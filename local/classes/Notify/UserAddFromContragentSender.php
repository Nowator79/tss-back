<?

namespace Godra\Api\Notify;

// вызывается после добавления пользователя из справочника контрагентов
class UserAddFromContragentSender implements ISender 
{
    public function send(array $params): void
    {
        $smsText = [];
        
        if ($params['phone'] && $params['email'])
        {
			$message = 'Создан аккаунт суперпользователя на b2b Агрокомплекс: '.$params['email'];
			
            $smsText = ['phone' => $params['phone'], 'message' => $message];
            
            (new Sender)->sendInternal([], $smsText, []);
        }
    }
}
