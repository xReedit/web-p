<?php	
require __DIR__ . '/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\ImagickEscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;


header('Access-Control-Allow-Origin: *');  

$fecha_actual=date('d').'/'.date('m').'/'.date('y');
$hora_actual=date('H').':'.date('i').':'.date('s');

$ip_printer=isset ($_POST['ip_print']) ? $_POST['ip_print'] : "smb://pc:182182@192.168.1.56/ticketera1";


try {
    // Enter the share name for your printer here, as a smb:// url format
    // $connector = new WindowsPrintConnector("smb://desa1/ticketera");
    //$connector = new WindowsPrintConnector("smb://Guest@computername/Receipt Printer");
    //$connector = new WindowsPrintConnector("smb://FooUser:secret@computername/workgroup/Receipt Printer");
    //$connector = new WindowsPrintConnector("smb://pc:182182@192.168.1.56/ticketera1");
	
	$impresora_print=$ip_printer;	
	$pos_print=strrpos($ip_printer,'//');		
	if($pos_print===false){
		$connector = new NetworkPrintConnector($impresora_print);
	}else{						
		$impresora_print=$impresora_print;		
		$connector = new WindowsPrintConnector($impresora_print);
	}
    
    /* Print a "Hello world" receipt" */
    $printer = new Printer($connector);


    // $logo = EscposImage::load('./logo/11logo_prueba4.jpg', false);
	$printer -> setJustification(Printer::JUSTIFY_CENTER);
    // $printer -> graphics($logo);


    $uri = "data:image/png;base64,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";
    // Convert data URI to binary data
    $imageBlob = base64_decode(explode(",", $uri)[1]);
    // // Give Imagick a filename with the correct extension to stop it from attempting
    // // to identify the format itself (this avoids CVE-2016–3714)
    // $imagick = new Imagick();
    // $imagick->setResourceLimit(6, 1);
    // // Prevent libgomp1 segfaults, grumble grumble.
    // $imagick->readImageBlob($imageBlob, "input.png");
    // // Load Imagick straight into an EscposImage object
    // $im = new ImagickEscposImage();
    // $im->readImageFromImagick($imagick);
    // // Do a test print to make sure that this EscposImage object has the right data
    // // (should see a tiny bullet point)
    // // $connector = new FilePrintConnector("php://output");
    // // $printer = new Printer($connector);
    // $printer->bitImage($im);


    $imagick = new Imagick();
    $imagick->setResourceLimit(6, 1);
    $imagick->readImageBlob($imageBlob, "input.png");
    $imLogo = new ImagickEscposImage();
    $imLogo->readImageFromImagick($imagick);

    $size = Printer::IMG_DEFAULT;
    $printer->bitImage($imLogo, $size);
    $printer -> feed();
    
    $printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
	$printer -> text('MESA 01'."\n");
	$printer -> selectPrintMode();
	$printer -> text("------------------------------------------------\n");
	$printer -> text('Referencia del pedido ej: Sr. Perez'."\n");
    $printer -> feed();
    
    $printer -> setJustification(Printer::JUSTIFY_CENTER);								
	$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
	$printer -> selectPrintMode(Printer::MODE_UNDERLINE);
	$printer -> setEmphasis(true);				
	$printer -> text("*** CONSUMIR EN EL LOCAL ***\n");	
	$printer -> setEmphasis(false);
    $printer -> selectPrintMode();
    
    //SECCION 1
	$printer -> setJustification(Printer::JUSTIFY_LEFT);
	$printer -> setEmphasis(true);				
	$printer -> text('ENTRADAS'."\n");
	$printer -> text("------------------------------------------------\n");	
    $printer -> setEmphasis(false);
    
    //destalles
	$printer -> setEmphasis(false);
	$printer -> text(new item('01 ARROZ CON LECHE', '2.00'));
	$printer -> text(new item('01 ENSALADA FRESCA', '2.00'));
	$printer -> text(new item('01 CAUSA RELLENA', '5.00'));
	$printer -> feed();

    /* TOTALES */
	$printer -> feed();
	$printer -> text("------------------------------------------------\n");
	$printer -> setEmphasis(true);
	
	$printer -> selectPrintMode(Printer::MODE_DOUBLE_WIDTH);	
	$printer -> text(new item('TOTAL', '39.00', true));
	$printer -> selectPrintMode();

	/* PIE DE PAGINA */	
	$printer -> feed(2);
	$printer -> setJustification(Printer::JUSTIFY_CENTER);
	$printer -> text($ArrayEnca['pie_pagina']."\n");
	$printer -> feed(2);
	$printer -> text("Atendido por: PEDRO \n");
	$printer -> text($fecha_actual.' | '.$hora_actual. "\n");

	$printer -> text("www.papaya.com.pe\n");

	$printer -> cut();
    $printer -> close();
    
    // $printer -> text("Hello World!\n");
    // $printer -> cut();
    
    /* Close printer */
    $printer -> close();
} catch (Exception $e) {
    echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}


class item
{
    private $name;
    private $price;
    private $dollarSign;

    public function __construct($name = '', $price = '', $dollarSign = false)
    {
        $this -> name = $name;
        $this -> price = $price;
        $this -> dollarSign = $dollarSign;
    }

    public function __toString()
    {
        $rightCols = 10;
        $leftCols = 38;
        if ($this -> dollarSign) {
            $leftCols = $leftCols / 2 - $rightCols / 2;
        }
        $left = str_pad($this -> name, $leftCols) ;

        $sign = ($this -> dollarSign ? 'S/. ' : '');
        $right = str_pad($sign . $this -> price, $rightCols, ' ', STR_PAD_LEFT);
        return "$left$right\n";
    }
}

?>