<?php  

namespace EdwinJuarez\APISri;

use EdwinJuarez\APISri\Traits\WSClient;

class Client
{	
	use WSClient;

	public function send($function, $arguments){
		$this->call($function, $arguments);
	}

}

?>