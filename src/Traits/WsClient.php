<?php  

namespace EdwinJuarez\APISri\Traits;

trait WSClient
{
	private $client;

	public function __construct($wsdl = "", $parameters = []){
		if (empty($wsdl)) 
		{
			$wsdl = "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl";
		}
		$this->client = new SoapClient($wsdl, $parameters);
	}
	
	public function call($function, $arguments)
    {
        return $this->client->__soapCall($function, $arguments);
    }

}

?>