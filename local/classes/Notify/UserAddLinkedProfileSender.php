<?

namespace Godra\Api\Notify;

// вызывается после добавления пользователя суперпользователем
class UserAddLinkedProfileSender implements ISender 
{
    public function send(array $params): void
    {
        $smsText = [];
        
        if ($params['phone'] && $params['email'] && $params['password'])
        {
			$message = 'Создан аккаунт пользователя на b2b Агрокомплекс: '.$params['email'].' / '.$params['password'];
			
            $smsText = ['phone' => $params['phone'], 'message' => $message];
            
            (new Sender)->sendInternal([], $smsText, []);
        }
    }
}
