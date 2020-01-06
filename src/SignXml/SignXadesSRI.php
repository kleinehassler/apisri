<?php 

namespace EdwinJuarez\APISri\SignXml;

use DOMDocument;
/**
 * @package EdwinJuarez\APISri\SignXml
 * @version 1.0.0
 * @license http://www.opensource.org/licenses/mit-license.php  MIT License
 * @author  Edwin Juarez 
 */
class SignXadesSRI extends AbstractSign
{
	const XMLDSIG = 'http://www.w3.org/2000/09/xmldsig#';
	const XADES_NAMESPACE_URI = 'http://uri.etsi.org/01903/v1.3.2#';
	const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
	const XMLDSIG_RSA_SHA1 = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
	const SIGNED_PROPERTIES_TYPE = 'http://uri.etsi.org/01903#SignedProperties';
	const DIGEST_METHOD_XMLDSIG_SHA1 = 'http://www.w3.org/2000/09/xmldsig#sha1';
	const ENVELOPED_SIGNATURE = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

	private $ns = [
		"xmlns:ds" => self::XMLDSIG, 
		"xmlns:etsi" => self::XADES_NAMESPACE_URI
	];

	/**
	 * Description
	 * @return type
	 */
	public function getXml(){
		return $this->xmlSigned;
	}

	public function sign($pathcertificado, $clavecertificado, $xmlsinfirma)
	{
		$this->xmlSigned = "";

		$this->pathcertificado = $pathcertificado;
		$this->clavecertificado = $clavecertificado;

		$this->loadCert();

		$this->xmlString = file_get_contents($xmlsinfirma);
		$domDocument = new DOMDocument($this->version, $this->encoding);
		$domDocument->loadXML($this->xmlString);
		$domDocumentC14N = $domDocument->C14N();

		//Números involucrados en los hash
		$SignatureID = $this->getNumeroAleatorio();
		$CertificateID = $this->getNumeroAleatorio();
        $SignedPropertiesID = $this->getNumeroAleatorio();
        
        //Números fuera de los hash:
        $SignedInfoID = $this->getNumeroAleatorio();
        $SignedPropertiesID = $this->getNumeroAleatorio();
        $ReferenceID = $this->getNumeroAleatorio();
        $SignatureValueID = $this->getNumeroAleatorio();
        $ObjectID = $this->getNumeroAleatorio();

        $signTime = is_null($this->signTime) ? time() : $this->signTime;
        $SigningTime1 =  date('c', $signTime);
        
    	$CertDigest = base64_encode(openssl_x509_fingerprint($this->publicKey, "sha1", true));
	    
        $SignedProperties = '';
        $SignedProperties .= '<etsi:SignedProperties Id="Signature' . $SignatureID . '-SignedProperties' . $SignedPropertiesID . '">';
        	$SignedProperties .= '<etsi:SignedSignatureProperties>';
        		$SignedProperties .= '<etsi:SigningTime>';
        			$SignedProperties .= $SigningTime1;
				$SignedProperties .= '</etsi:SigningTime>';
            	$SignedProperties .= '<etsi:SigningCertificate>';
                	$SignedProperties .= '<etsi:Cert>';
                    	$SignedProperties .= '<etsi:CertDigest>';
                        	//$SignedProperties .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
                    		$SignedProperties .= '<ds:DigestMethod Algorithm="' . self::DIGEST_METHOD_XMLDSIG_SHA1 . '">';

                        	$SignedProperties .= '</ds:DigestMethod>';
                        	$SignedProperties .= '<ds:DigestValue>';
                        		$SignedProperties .= $CertDigest;
                        	$SignedProperties .= '</ds:DigestValue>';
                    	$SignedProperties .= '</etsi:CertDigest>';
                    	$SignedProperties .= '<etsi:IssuerSerial>';
                        	$SignedProperties .= '<ds:X509IssuerName>';
                        		$SignedProperties .=  $this->getIssuer(); 
                        	$SignedProperties .= '</ds:X509IssuerName>';
                    		$SignedProperties .= '<ds:X509SerialNumber>'; 
                    			$SignedProperties .= $this->getSerialNumber();
                    		$SignedProperties .= '</ds:X509SerialNumber>';
                    	$SignedProperties .= '</etsi:IssuerSerial>';
                	$SignedProperties .= '</etsi:Cert>';
            	$SignedProperties .= '</etsi:SigningCertificate>';
        	$SignedProperties .= '</etsi:SignedSignatureProperties>';
        	$SignedProperties .= '<etsi:SignedDataObjectProperties>';
        		$SignedProperties .= '<etsi:DataObjectFormat ObjectReference="#Reference-ID-' . $ReferenceID . '">';
                	$SignedProperties .= '<etsi:Description>';
                		$SignedProperties .= 'contenido comprobante';                        
                	$SignedProperties .= '</etsi:Description>';
                	$SignedProperties .= '<etsi:MimeType>';
                    	$SignedProperties .= 'text/xml';
                	$SignedProperties .= '</etsi:MimeType>';
            	$SignedProperties .= '</etsi:DataObjectFormat>';
        	$SignedProperties .= '</etsi:SignedDataObjectProperties>';
    	$SignedProperties .= '</etsi:SignedProperties>';
        
		$KeyInfo = '';
	    $KeyInfo .= '<ds:KeyInfo Id="Certificate' . $CertificateID . '">' . "\n";
	        $KeyInfo .= '<ds:X509Data>' . "\n";
	            $KeyInfo .= '<ds:X509Certificate>' . "\n";
	            	$KeyInfo .= $this->getCert();
	                $KeyInfo .= '</ds:X509Certificate>' . "\n";
	        $KeyInfo .= '</ds:X509Data>' . "\n";
	        $KeyInfo .= '<ds:KeyValue>' . "\n";
	            $KeyInfo .= '<ds:RSAKeyValue>' . "\n";
	                $KeyInfo .= '<ds:Modulus>' . "\n";
	                	$KeyInfo .= $this->getModulus();
	                $KeyInfo .= '</ds:Modulus>';
	                $KeyInfo .= '<ds:Exponent>';
	                    $KeyInfo .= $this->getExponent();
	                $KeyInfo .= '</ds:Exponent>' . "\n";
	            $KeyInfo .= '</ds:RSAKeyValue>' . "\n";
	        $KeyInfo .= '</ds:KeyValue>' . "\n";
	    $KeyInfo .= '</ds:KeyInfo>';

	    $SignedProperties_para_hash = str_replace('<etsi:SignedProperties', "<etsi:SignedProperties {$this->joinArray($this->ns)}", $SignedProperties);
	    $PropDigest = $this->getDigest("sha1", $this->canonicalizeData($SignedProperties_para_hash));

	    $KeyInfo_para_hash = str_replace('<ds:KeyInfo', "<ds:KeyInfo {$this->joinArray($this->ns)}", $KeyInfo);
	    $KeyInfoDigest = $this->getDigest("sha1", $this->canonicalizeData($KeyInfo_para_hash));

	    $documentDigest = $this->getDigest("sha1", $this->canonicalizeData($this->xmlString));

	    $SignedInfo = '';
	    $SignedInfo .= '<ds:SignedInfo Id="Signature-SignedInfo' . $SignedInfoID . '">'. "\n";
	        //$SignedInfo .= '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">'. "\n";
	    	$SignedInfo .= '<ds:CanonicalizationMethod Algorithm="' . self::C14N .'">'. "\n";

	        $SignedInfo .= '</ds:CanonicalizationMethod>'. "\n";
	        //$SignedInfo .= '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1">';
	        $SignedInfo .= '<ds:SignatureMethod Algorithm="' . self::XMLDSIG_RSA_SHA1 . '">';

	        $SignedInfo .= '</ds:SignatureMethod>'. "\n";
	        //$SignedInfo .= '<ds:Reference Id="SignedPropertiesID' . $SignedPropertiesID . '" Type="http://uri.etsi.org/01903#SignedProperties" URI="#Signature' . $SignatureID . '-SignedProperties' . $SignedPropertiesID . '">'. "\n";

	        $SignedInfo .= '<ds:Reference Id="SignedPropertiesID' . $SignedPropertiesID . '" Type="' . self::SIGNED_PROPERTIES_TYPE . '" URI="#Signature' . $SignatureID . '-SignedProperties' . $SignedPropertiesID . '">'. "\n";

	            //$SignedInfo .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
	        	$SignedInfo .= '<ds:DigestMethod Algorithm="' . self::DIGEST_METHOD_XMLDSIG_SHA1 . '">';

	            $SignedInfo .= '</ds:DigestMethod>'. "\n";
	            $SignedInfo .= '<ds:DigestValue>';
	                $SignedInfo .= $PropDigest;
	            $SignedInfo .= '</ds:DigestValue>'. "\n";
	        $SignedInfo .= '</ds:Reference>'. "\n";
	        $SignedInfo .= '<ds:Reference URI="#Certificate' . $CertificateID . '">'. "\n";
	            //$SignedInfo .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
	        	$SignedInfo .= '<ds:DigestMethod Algorithm="' . self::DIGEST_METHOD_XMLDSIG_SHA1 . '">';

	            $SignedInfo .= '</ds:DigestMethod>'. "\n";
	            $SignedInfo .= '<ds:DigestValue>';
	                $SignedInfo .= $KeyInfoDigest;
	            $SignedInfo .= '</ds:DigestValue>'. "\n";
	        $SignedInfo .= '</ds:Reference>'. "\n";
	        $SignedInfo .= '<ds:Reference Id="Reference-ID-' . $ReferenceID . '" URI="#comprobante">'. "\n";
	            $SignedInfo .= '<ds:Transforms>'. "\n";
	                //$SignedInfo .= '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature">';
	            	$SignedInfo .= '<ds:Transform Algorithm="' . self::ENVELOPED_SIGNATURE . '">';

	                $SignedInfo .= '</ds:Transform>'. "\n";
	            $SignedInfo .= '</ds:Transforms>'. "\n";
	            //$SignedInfo .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
	            $SignedInfo .= '<ds:DigestMethod Algorithm="' . self::DIGEST_METHOD_XMLDSIG_SHA1 . '">';

	            $SignedInfo .= '</ds:DigestMethod>'. "\n";
	            $SignedInfo .= '<ds:DigestValue>';
	                $SignedInfo .= $documentDigest;
	            $SignedInfo .= '</ds:DigestValue>'. "\n";
	        $SignedInfo .= '</ds:Reference>'. "\n";
	    $SignedInfo .= '</ds:SignedInfo>';

	    //CALCULAR FIRMA
	   	$SignedInfo_para_firma = str_replace('<ds:SignedInfo', "<ds:SignedInfo {$this->joinArray($this->ns)}", $SignedInfo);
	    $signatureResult = $this->getSignature($this->canonicalizeData($SignedInfo_para_firma), "SHA1");

	    //GENERAR LA FIRMA
	    $Sig = '';
	    $Sig .= "<ds:Signature {$this->joinArray($this->ns)}" . ' Id="Signature' . $SignatureID . '">'. "\n";
	        $Sig .= $SignedInfo . "\n";
	        $Sig .= '<ds:SignatureValue Id="SignatureValue' . $SignatureValueID . '">'. "\n";
	            $Sig .= $signatureResult . "\n";
	        $Sig .= '</ds:SignatureValue>' . "\n";
	        $Sig .= $KeyInfo . "\n";
	        $Sig .= '<ds:Object Id="Signature' . $SignatureID . '-Object' . $ObjectID . '">';
	            $Sig .= '<etsi:QualifyingProperties Target="#Signature' . $SignatureID . '">';
	                $Sig .= $SignedProperties;
	            $Sig .= '</etsi:QualifyingProperties>';
	        $Sig .= '</ds:Object>';
	    $Sig .= '</ds:Signature>';

	    $TipoComprobantes = ["factura", "notaCredito", "notaDebito", "guiaRemision", "comprobanteRetencion"];
	    $TipoComprobante = "";
	    $count = count($TipoComprobantes);
	    for ($i=0; $i < $count; $i++) { 
	    	if (strstr($this->xmlString, $TipoComprobantes[$i]))
			{
				$TipoComprobante = $TipoComprobantes[$i];
			}
	    }

		$this->xmlSigned = str_replace('</' . $TipoComprobante, $Sig . '</' . $TipoComprobante, $this->xmlString);

	}
}

?>