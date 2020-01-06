<?php  

use EdwinJuarez\APISri\SignXml\SignXadesSRI;

require 'vendor/autoload.php';

$signXadesSRI = new SignXadesSRI();

$pathcertificado = "certificado.p12";
$clavecertificado = "clavecertificado";
$xmlsinfirma = "xxxxxxxxxxxxxxxxxx.xml";
$xmlfirmado = "xxxxxxxxxxxxxxxxxx_SIGNED.xml";

$signXadesSRI->sign($pathcertificado, $clavecertificado, $xmlsinfirma);

file_put_contents($xmlfirmado, $signXadesSRI->getXml());


?>