<?php 

namespace EdwinJuarez\APISri\SignXml;

use DOMDocument;

/**
 * @package EdwinJuarez\APISri\SignXml
 * @version 1.0.0
 * @license http://www.opensource.org/licenses/mit-license.php  MIT License
 * @author  Edwin Juarez 
 */
abstract class AbstractSign
{

	protected $autor = "Edwin Juárez C.";
	protected $version = "1.0";
	protected $encoding = "utf-8";
	protected $pathcertificado;
	protected $clavecertificado;
	protected $xmlString;
	protected $xmlSigned = "";
	protected $certs = "";
	protected $publicKey;
	protected $privateKey;
	protected $signTime;

	abstract public function sign($pathcertificado, $clavecertificado, $xmlsinfirma);

	protected function getNumeroAleatorio()
	{
		return rand(100000, 999999);
	}

    protected function loadCert()
    {
    	if (!is_file($this->pathcertificado)) {
    		return false;
    	}

    	if (!$almacen_cert = file_get_contents($this->pathcertificado)) {
		    echo "Error: No se puede leer el fichero del certificado\n";
		    return false;
		}

		if (openssl_pkcs12_read($almacen_cert, $this->certs, $this->clavecertificado)) {
		    $this->publicKey = $this->certs['cert'];
      		$this->privateKey = $this->certs['pkey'];
		}
    }

    protected function getSerialNumber(){
    	$certData = openssl_x509_parse($this->publicKey);
		return $certData['serialNumber'];
	}

	 protected function getIssuer(){
    	$certData = openssl_x509_parse($this->publicKey);
		$certIssuer = array();
	    foreach($certData['issuer'] as $item => $value) {
	    	$certIssuer[] = $item . '=' . $value;
	    }
	    return implode(",", array_reverse($certIssuer));
	}

	protected function getModulus(){
		$details = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));
		return base64_encode($details['rsa']['n']);
	}

	protected function getExponent(){
		$details = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));
		return base64_encode($details['rsa']['e']);
	}

	protected function getDigest($algo = "sha1", $data, $raw_output = true) 
	{
		return $this->toBase64(hash($algo , $data, $raw_output));
  	}

    protected function getCert()
  	{
  		if (is_null($this->publicKey)) {
    		return "";
    	}

    	openssl_x509_export($this->publicKey, $certificateX509_pem); //Exporta un certificado como una cadena
    	$certificateX509_pem = str_replace("-----BEGIN CERTIFICATE-----", "", $certificateX509_pem);
    	$certificateX509_pem = str_replace("-----END CERTIFICATE-----", "", $certificateX509_pem);
		$certificateX509_pem = str_replace("\r", "", str_replace("\n", "", $certificateX509_pem));
		return $certificateX509_pem;
  	}

  	protected function canonicalizeData($data){
  		$domDocument = new DOMDocument($this->version, $this->encoding);
		$domDocument->loadXML($data);
		return $domDocument->C14N();
  	}

	protected function joinArray(array $array, $join = ' ') 
	{
		return implode($join, array_map(function($key, $value) {
            return "{$key}=\"$value\"";
        }, array_keys($array), $array));
    }

  	protected function getSignature($data, $signature_alg = "SHA1") {
    	openssl_sign($data, $signature, $this->privateKey, $signature_alg);
    	return $this->toBase64($signature);
  	}
  	protected function toBase64($data) 
  	{
    	return base64_encode($data);
  	}

	public function getAutor(){
		echo $this->autor;
	}

	public function setAutor($autor) {
        $this->autor = $autor;
    }

}

?>