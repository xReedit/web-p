<?php

header('Access-Control-Allow-Origin: https://papaya.com.pe');
header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
header('Content-type: application/json');

define('SITE_KEY', '6LeRWZAUAAAAAHZOZ-Ezg7OUEbPjDa3jJncjU1o5');
define('SECRET_KEY', '6LeRWZAUAAAAAL5lS3GZ1eB_SQ9Dg0Qbly_mgpkZ');

$msj = '';
$res;

if($_POST){
    function getCaptcha($SecretKey){
        $Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".SECRET_KEY."&response={$SecretKey}");
        $Return = json_decode($Response);
        return $Return;
    }
    $Return = getCaptcha($_POST['g-recaptcha-response']);        
    // if($Return->success == true && $Return->score > 0.5){  
    if($Return->success == true){
        

        $numComprobante = explode("-", $_POST['num_comprobante']);        
        $data = array(
            "number_ruc_company" => $_POST['ruc_establecimiento'],
            "series" => $numComprobante[0],
            "number_document" => (int)$numComprobante[1],
            "total" => $_POST['importe']
        );

        $_data=json_encode($data);

        sendPost($_data);
        
    }else{
        $msj="No se encontro el comprobante...(r)";
        $res=false;
        responder($res,$msj,'');        
    }	
}

function sendPost($jsonDataEncoded)
{    
    
    // return;

    $res='';
    $msj='api1';
    //url contra la que atacamos
    // $ch = curl_init("http://3.16.166.249/api/documents/consult_id");
    $ch = curl_init("https://apifac.papaya.com.pe/api/documents/getLinks");    
    //a true, obtendremos una respuesta de la url, en otro caso, 
    //true si es correcto, false si no lo es
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: application/json"));
    //establecemos el verbo http que queremos utilizar para la petición
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    
    //Attach our encoded JSON string to the POST fields.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
 
    //Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
    
    //enviamos el array data
    // curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    //obtenemos la respuesta
    $response = curl_exec($ch);
    // Se cierra el recurso CURL y se liberan los recursos del sistema
    curl_close($ch);
    
    if(!$response) {
        sendPostApi2($jsonDataEncoded);
        // $res=false;
        // $msj='No se encontro el comprobante';
    }else{
        $dt = json_decode($response, true);        
        if ( $dt['success'] ) {
            $res=true;    
            responder($res, $msj, $response);    
        } else {
            sendPostApi2($jsonDataEncoded);
        }
    }

    // $msj=json_encode($data); 

    // responder($res, $msj, $response);
}

function sendPostApi2($jsonDataEncoded)
{    
    
    // return;

    $res='';
    $msj='api2';
    //url contra la que atacamos
    // $ch = curl_init("http://3.16.166.249/api/documents/consult_id");
    $chApi = curl_init("https://apifac.papaya.com.pe/api/documents/getLinks");    
    //a true, obtendremos una respuesta de la url, en otro caso, 
    //true si es correcto, false si no lo es
    curl_setopt($chApi, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: application/json"));
    //establecemos el verbo http que queremos utilizar para la petición
    curl_setopt($chApi, CURLOPT_CUSTOMREQUEST, "POST");
    
    //Attach our encoded JSON string to the POST fields.
    curl_setopt($chApi, CURLOPT_POSTFIELDS, $jsonDataEncoded);
 
    //Set the content type to application/json
    curl_setopt($chApi, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
    
    //enviamos el array data
    // curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    //obtenemos la respuesta
    $response = curl_exec($chApi);
    // Se cierra el recurso CURL y se liberan los recursos del sistema
    curl_close($chApi);
    
    if(!$response) {
        $res=false;
        $msj='No se encontro el comprobante';
    }else{
        $res=true;        
    }

    // $msj=json_encode($data); 

    responder($res, $msj, $response);
}




function responder($res, $msj, $data){
    $response = array(
        'res' => $res,
        'msj'=> $msj,
        'data'=> $data
    );

    echo json_encode($response); 
}

?>