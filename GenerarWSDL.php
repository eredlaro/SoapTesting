<?php
require_once('WSDLDocument.php');
try {
    $wsdl = new WSDLDocument("Facturandote", "http://localhost/facturandote-wsdemo/timfac.php", "http://localhost/facturandote-wsdemo/");
    echo $wsdl->SaveXml();
} catch (Exception $e) {
    echo $e->getMessage();
}
class Facturandote
{

    /**
     * Devuelve un saludo   
     * @param string $nombre
     * @return string 
     */
    public function  Saludar($nombre)
    {

        return "hola " . $nombre . "!";
    }
}
