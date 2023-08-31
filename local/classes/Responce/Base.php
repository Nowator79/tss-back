<?
namespace Godra\Api\Responce;

abstract class Base
{
	private $responce = [
		"type" => "OK",
		"body" => "",
	];
	const ResponceSuccess = "OK";
	const ResponceError = "ERROR";

	public function __construct() {
	}
	public function setResponce(bool $isSuccess, $text){
		if($isSuccess){
			$this->responce["type"] = self::ResponceSuccess;
		}else{
			$this->responce["type"] = self::ResponceError;
		}
		$this->responce["body"] = $text;
	}

	public function end() {
		return $this->responce;
	}
}