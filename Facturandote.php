<?php
include('con3xion.php');



/** Clase Facturandote */
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

    private function Foliosres($iduser)
    {
        $sql = Conexion::conectarlocal();
        try {
            $test = $sql->prepare("SELECT folios_rest FROM folios_timbres WHERE id_user=:iduser");
            $test->bindParam(':iduser', $iduser, PDO::PARAM_INT);
            $test->execute();
            $consult = $test->fetchAll();
            foreach ($consult as $consul) :
                $rew = $consul['folios_rest'];
            endforeach;
            if ($rew == "")
                return "error|El Usuario no existe" . $iduser;
            else
                return "success|" . $rew;
        } catch (Exception $e) {
            return "error|" . $e->getMessage() . $iduser;
        }
    }
    private function GuardaEnviado($iduser, $xml)
    {


        $sql = Conexion::conectarlocal();
        try {
            $test = $sql->prepare("INSERT INTO cfdi_sendis (id_user,cfdi) VALUES ({$iduser},'{$xml}')");
            $sql->beginTransaction();
            $test->execute();
            $sql->exec("INSERT INTO bit_log (id_user,log_bita) VALUES ({$iduser},'Envio a Timbrar UN CFDI el usuario:{$iduser}') ");
            $sql->commit();
            return "success";
        } catch (Exception $e) {
            $sql->rollback();

            return 'error' . $e->getMessage();
        }

        $sql->close();
    }
    private function GuardaCancela($iduser, $foliofis, $rfc, $total, $firma)
    {

        try {
            $sql = Conexion::conectarlocal();
            $estatus = "SOLICITUD ENVIADA";
            $test = $sql->prepare("INSERT INTO cancel_cancelaciones (folio_fis,rfc,total,id_user,estatus,cancel_firma) VALUES (:foliofis,:rfc,:total,:id_user,:estatus,:firma)");
            $test->bindParam(':foliofis', $foliofis, PDO::PARAM_STR);
            $test->bindParam(':rfc', $rfc, PDO::PARAM_STR);
            $test->bindParam(':total', $total, PDO::PARAM_STR);
            $test->bindParam(':id_user', $iduser, PDO::PARAM_STR);
            $test->bindParam(':estatus', $estatus, PDO::PARAM_STR);
            $test->bindParam(':firma', $firma, PDO::PARAM_STR);

            $sql->beginTransaction();
            $test->execute();
            $sql->exec("INSERT INTO bit_log (id_user,log_bita) VALUES ({$iduser},'Envio a cancelar UN CFDI el usuario:{$iduser}') ");
            $sql->commit();
            return "success";
        } catch (Exception $e) {
            $sql->rollback();

            return $e->getMessage();
        }

        $sql->close();
        $sql->close();
    }
    private function RestaFolios($iduser)
    {

        $sql = Conexion::conectarlocal();
        try {
            $test = $sql->prepare("UPDATE folios_timbres set folios_rest=folios_rest-1,folios_cons=folios_cons+1,consumo_acumulado=consumo_acumulado+1 WHERE id_user=:iduser");
            $test->bindParam(':iduser', $iduser, PDO::PARAM_INT);
            $sql->beginTransaction();
            $test->execute();
            $sql->commit();
            return "success";
        } catch (Exception $e) {
            $sql->rollback();

            return 'error' . $e->getMessage();
        }

        $sql->close();
    }
    private function SumaFolios($iduser)
    {

        $sql = Conexion::conectarlocal();
        try {
            $test = $sql->prepare("UPDATE folios_timbres set folios_rest=folios_rest+1,folios_cons=folios_cons+1,consumo_acumulado=consumo_acumulado-1 WHERE id_user=:iduser");
            $test->bindParam(':iduser', $iduser, PDO::PARAM_INT);
            $sql->beginTransaction();
            $test->execute();
            $sql->commit();
            return "success";
        } catch (Exception $e) {
            $sql->rollback();

            return 'error' . $e->getMessage;
        }

        $sql->close();
    }
    private function ActualizaSello($iduser, $xml)
    {

        $sql = Conexion::conectarlocal()->prepare("SELECT nombre_cer,csd_pem,key_pem FROM csd_certi WHERE id_user=" . $iduser);
        $sql->execute();
        foreach ($sql->fetchAll() as $row) {
            $rew = $row;
        }


        $xmlDoc = new DOMDocument("1.0", "UTF-8");

        if (!$xmlDoc->loadxml($xml)) {
            $errors = libxml_get_errors();
            var_dump($errors);
        }
        $zonaHoraria = "America/Mexico_City";
        date_default_timezone_set($zonaHoraria);
        $date = date('Y-m-d_H:i:s');
        $date = str_replace("_", "T", $date);
        $xmlDoc->firstChild->setAttribute('Fecha', $date);

        $xmlDoc->firstChild->setAttribute('Certificado', $rew['csd_pem']);
        $xmlString = $xmlDoc->saveXML();

        $xslt = new DOMDocument();
        $xslt->load("xslt/cadenaoriginal_3_3.xslt");
        $xml = new DOMDocument;
        $xml->loadxml($xmlString);
        $proc = new XSLTProcessor;
        @$proc->importStyleSheet($xslt);
        $cadena = $proc->transformToXML($xml);


        openssl_sign($cadena, $digest, $rew['key_pem'], OPENSSL_ALGO_SHA256);
        $sello = base64_encode($digest);


        $xmlDoc->firstChild->setAttribute('Sello', $sello);
        $xmlString = $xmlDoc->saveXML();
        $xmlString = str_replace('<?xml version="1.0"?>', "", $xmlString);

        return $xmlString;
    }
    private function SendTimb($xmlsellado)
    {
        $wsdl_url = "https://www.paxfacturacion.com.mx:453/webservices/wcfRecepcionasmx.asmx?wsdl";
        $wsdl_usuario = "eredlaro29";
        $wsdl_contrasena = "HjjYn7SV3bcttvVNUpctLtIejAF86rGD+tE2zVDVWC2Z857sF3OthPlbIqYTyN59";




        $cliente = new SoapClient($wsdl_url, array(
            'trace' => 1,
            'use' => SOAP_LITERAL,
        ));
        $parametros = array(
            "psComprobante" => $xmlsellado,
            "psTipoDocumento" => "01",
            "pnId_Estructura" => "0",
            "sNombre" => $wsdl_usuario,
            "sContraseña" => $wsdl_contrasena,
            "sVersion" => "3.3"
        );

        try {

            $respuesta = $cliente->fnEnviarXML($parametros);

            $respuesta = $respuesta->fnEnviarXMLResult;
            return "success|" . $respuesta;
        } catch (Exception $exception) {

            return "error| " . $exception->getCode() . "\n" . "Descripción del error: " . $exception->getMessage() . "\n";
        }
    }
    private function SendCancel($foliofis, $rfcemi, $rfcrec, $total, $motivo, $foliosus, $firma)
    {
        $wsdl_url = "https://www.paxfacturacion.com.mx:476/webservices/wcfCancelaASMX.asmx?wsdl";
        $wsdl_usuario = "eredlaro29";
        $wsdl_contrasena = "HjjYn7SV3bcttvVNUpctLtIejAF86rGD+tE2zVDVWC2Z857sF3OthPlbIqYTyN59";


        try {

            $cliente = new SoapClient($wsdl_url, array(
                'trace' => 1,
                'use' => SOAP_LITERAL,
            ));
            $fs = array();
            $rr = array();
            $to = array();
            $mo = array();
            $fosus = array();
            array_push($fs, $foliofis);
            array_push($rr, $rfcrec);
            array_push($to, $total);
            array_push($mo, $motivo);
            array_push($fosus, $foliosus);
            $parametros = array(
                "sListaUUID" => $fs,
                "psRFCEmisor" => $rfcemi,
                "psRFCReceptor" => $rr,
                "sListaTotales" => $to,
                "sMotivosCancelacion" => $mo,
                "sFoliosSustitucion" => $fosus,
                "signature" => $firma,
                "sNombre" => $wsdl_usuario,
                "sContrasena" => $wsdl_contrasena,
            );




            //$respuesta = $cliente->fnCancelarDistribuidoresXML20($fs,$rfcemi,$rr,$to,$mo,$fosus,$firma,$wsdl_usuario,$wsdl_contrasena);  
            $respuesta = $cliente->fnCancelarDistribuidoresXML20($parametros);
            $respuesta = $respuesta->fnCancelarDistribuidoresXML20Result;
            return "success|" . $respuesta . "|" . $cliente->__getLastRequest();
        } catch (Exception $exception) {

            return "Error|code:" . $exception->getCode() . "\n" . "Descripción del error: " . $exception->getMessage() . "\n";
        }
    }
    /**
     * Devuelve el xml de un cfdi sin sello ya timbrado por el SAT
     * @param string $rfc
     * @param string $iduser
     * @param string $xml
     * @return string */
    public function Timbrar($rfc, $iduser, $xml)
    {
        $fol = explode("|", $this->Foliosres($iduser));
        if ($fol[0] == "success" && $fol[1] > 0) {
            $ans = $this->RestaFolios($iduser);
            if ($ans == 'success') {
                $resgurda = $this->GuardaEnviado($iduser, $xml);
                if ($resgurda == "success") {
                    $cfdi = $this->SendTimb($xml);
                    $cfdires = explode('|', $cfdi);
                    if ($cfdires[0] == "error") {
                        $this->SumaFolios($iduser);
                        $cfdi = "Error|" . $cfdires[1] . '|' . $cfdires[2];
                    } else {
                        if ($cfdires[2] != null) {
                            $sum = $this->SumaFolios($iduser);
                            if ($sum == "success")
                                $cfdi = "error|Code:" . $cfdires[1] . "Descripcion:" . $cfdires[2];
                            else
                                $cfdi = "error|No se pudo sumar el folio" . $sum . " Code:" . $cfdires[1] . "Descripcion:" . $cfdires[2];
                        } else
                            $cfdi = $cfdi;
                    }
                } else {
                    $this->SumaFolios($iduser);
                    $cfdi = "Error|No se pudo insertar el xml construido" . $resgurda;
                }
            } else {
                $cfdi = "Error|No pudo restar los folios:" . $ans;
            }
        } else {
            $cfdi = "Error|Folios Insuficientes";
        }
        return $cfdi;
    }

    /**
     * Devuelve la respuesta de cancelacion del SAT despues de enviar un uuid
     * @param string $user
     * @param string $foliofis
     * @param string $rfcemi
     * @param string $rfcrec
     * @param string $total
     * @param string $motivo
     * @param string $uuidsus
     * @param string $firma
     * @return string */
    public function Cancelar($user, $foliofis, $rfcemi, $rfcrec, $total, $motivo, $uuidsus, $firma)
    {
        $fol = explode("|", $this->Foliosres($user));
        if ($fol[0] == "success" && $fol[1] > 0) {
            $resp = $this->GuardaCancela($user, $foliofis, $rfcrec, $total, $firma);
            if ($resp == "success") {
                $folios = explode("|", $this->Foliosres($user));
                if ($folios[0] == "success" && (int)$folios[1] > 0) {
                    $cfdi = $this->SendCancel($foliofis, $rfcemi, $rfcrec, $total, $motivo, $uuidsus, $firma);
                    $cfdires = explode('|', $cfdi);
                    if ($cfdires[0] == "Error")
                        $res = "Error|" . $cfdires[1];
                    else {
                        $ans = $this->RestaFolios($user);
                        if ($ans == "success")
                            $res = "success|" . $cfdires[1];
                        else
                            $res = "Error|No pudo restar los folios:" . $ans;
                    }
                } else {
                    $res = "Error|Folios Insuficientes " . $folios[1];
                }
            } else {
                $res = "Error|no se guardo " . $resp;
            }
        } else
            $res = "Error|Folios Insuficientes= " . $fol[1];

        return $res;
    }
}

try {
    $server = new SoapServer("Facturandote.wsdl", array('cache_wsdl' => WSDL_CACHE_NONE));
    $server->setClass("Facturandote");
    $server->addFunction("Saludar");
    $server->addFunction("Timbrar");
    $server->addFunction("Cancelar");
    $server->handle();
} catch (SOAPFault $f) {
    print $f->faultstring;
}
