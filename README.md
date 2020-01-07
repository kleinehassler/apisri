# apisri-master
Librería desarrollada en PHP para firmar los comprobantes electrónicos (Factura, Nota de Crédito, Nota de Débito) para Facturación Electrónica SRI Ecuador.

# Autor
Edwin Juarez C. edwinjuarez24x@gmail.com

# Instalación
composer require edwinjuarez/apisri

# Uso
<?php  

use EdwinJuarez\APISri\SignXml\SignXadesSRI;

require 'vendor/autoload.php';

$signXadesSRI = new SignXadesSRI();

$pathcertificado = "pathcertificado.p12";
$clavecertificado = "clavecertificado";
$xmlsinfirma = "xmlsinfirma.xml";
$xmlfirmado = "xmlfirmado.xml";

$signXadesSRI->sign($pathcertificado, $clavecertificado, $xmlsinfirma, $xmlfirmado);

file_put_contents($xmlfirmado, $signXadesSRI->getXml());

?>

