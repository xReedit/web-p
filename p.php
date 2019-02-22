<?php 
// $data = array(
//         "ip_print" => $_POST['ip_print'],         
// 	);

// $url = $_POST['url'];

// $_data=json_encode($url, $data);
// // sendPost($_data);
// function sendPost($url, $jsonDataEncoded)
// {    
    
    // return;

    require_once("html_print.html"); 

    echo "ok";
    // $res='';
    // $msj='';
    //url contra la que atacamos
    // $ch = curl_init('http://192.168.1.64/restobar/print/pruebas.print_url.php');    
    // $ch = curl_init('html_print.html');    
    //a true, obtendremos una respuesta de la url, en otro caso, 
    //true si es correcto, false si no lo es
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: application/json"));
    //establecemos el verbo http que queremos utilizar para la petición
    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    
    //Attach our encoded JSON string to the POST fields.
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
 
    //Set the content type to application/json
    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
    
    //enviamos el array data
    // curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    //obtenemos la respuesta
    // $response = curl_exec($ch);
    // Se cierra el recurso CURL y se liberan los recursos del sistema
    // curl_close($ch);
    
    // if(!$response) {
    //     $res=false;
    //     $msj='No se encontro el comprobante';
    // }else{
    //     $res=true;        
    // }

    // $msj=json_encode($data); 

    // responder($res, $msj, $response);
// }

?>