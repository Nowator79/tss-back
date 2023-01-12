<?
namespace Godra\Api;

class Search
{
    public function header()
    {
        return ['status' => 1, 'data' => (new Helpers\Search)->searchProcess($_GET['query'])];
    }
}
