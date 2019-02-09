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
    if($Return->success == true && $Return->score > 0.8){
		echo "Succes!";
		// comprobar comprobantes
    }else{
        echo "Eres un robot";
	}
	
}



?>