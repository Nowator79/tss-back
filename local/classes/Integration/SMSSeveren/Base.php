<?
namespace Godra\Api\Integration\SMSSeveren;

abstract class Base
{
    protected $login = SEVEREN_AUTHORIZE_DATA['login'];
    protected $password = SEVEREN_AUTHORIZE_DATA['password'];

    protected function getOrganisationName()
    {
        return \json_decode(\file_get_contents('https://gateway.api.sc/rest/Statistic/originator.php?login='.$this->login.'&pass='.$this->password))[0];
    }

    protected function sendMessage()
    {
        $url = 'https://gateway.api.sc/get/?user='.$this->login.
                    '&pwd='.$this->password.
                    '&sadr='.$this->getOrganisationName().
                    '&dadr='.$this->data['phone'].
                    '&text='.$this->data['text'];

        return \json_decode(\file_get_contents($url));
    }
}
?>