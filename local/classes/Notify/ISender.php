<?

namespace Godra\Api\Notify;

interface ISender
{
    public function send(array $params): void;
}
