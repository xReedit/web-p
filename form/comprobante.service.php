<?php
define('SITE_KEY', '6LeRWZAUAAAAAHZOZ-Ezg7OUEbPjDa3jJncjU1o5');
define('SECRET_KEY', '6LeRWZAUAAAAAL5lS3GZ1eB_SQ9Dg0Qbly_mgpkZ');

if($_POST){
    function getCaptcha($SecretKey){
        $Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".SECRET_KEY."&response={$SecretKey}");
        $Return = json_decode($Response);
        return $Return;
    }
    $Return = getCaptcha($_POST['g-recaptcha-response']);
    //var_dump($Return);
    if($Return->success == true && $Return->score > 0.5){
        echo "Succes!";
    }else{
        echo "You are a Robot!!";
    }
}


// $recaptcha = $_POST["g-recaptcha-response"];

//     $url = 'https://www.google.com/recaptcha/api/siteverify';
//     $data = array(
//         'secret' => '6LcjWZAUAAAAADDBkGngrdQmQJeRmwIrU6zZYV9G',
//         'response' => $recaptcha
//     );

//     $options = array(
//         'http' => array (
//             'method' => 'POST',
//             'content' => http_build_query($data)
//         )
//     );

//     $context  = stream_context_create($options);
//     $verify = file_get_contents($url, false, $context);
//     $captcha_success = json_decode($verify);
    
//     if ($captcha_success->success) {
//         echo 'Se envía el formulario';
//     } else {
//         echo 'No se envía el formulario';
//     }


?>