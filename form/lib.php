<?php
// Utilidades de contratar.php y reclamo.php (funcionan sin la consola).
// ponytail: archivos en vez de BD; cuando la consola esté en producción la landing puede volver a usar su API.
ini_set('display_errors', '0'); // un aviso impreso rompería el JSON (PHPMailer viejo lanza "Deprecated" en PHP 7.2+)
date_default_timezone_set('America/Lima');

const COPIA_PAPAYA = 'soporte@papaya.com.pe';
const REMITENTE = 'noreply@papaya.com.pe';

function config() {
	static $c = null;
	if ($c === null) {
		$f = __DIR__ . '/config.php';
		$c = array_merge(
			['culqi_publica' => '', 'culqi_secreta' => '', 'culqi_api' => 'https://api.culqi.com/v2', 'datos' => dirname(__DIR__, 2) . '/papaya-datos'],
			is_file($f) ? require $f : []
		);
	}
	return $c;
}

function responder($datos, $estado = 200) {
	http_response_code($estado);
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	exit(json_encode($datos, JSON_UNESCAPED_UNICODE));
}

function entradaJson() {
	$d = json_decode(file_get_contents('php://input'), true);
	return is_array($d) ? $d : [];
}

// Un navegador en otro sitio no puede usar estos formularios (un script sí puede falsear Origin: el freno real son los límites por IP).
function exigirPost() {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(['ok' => false, 'resultado' => 'error', 'mensaje' => 'Método no permitido'], 405);
	$origen = isset($_SERVER['HTTP_ORIGIN']) ? parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) : null;
	$host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
	if ($origen !== null && $origen !== $host) responder(['ok' => false, 'resultado' => 'error', 'mensaje' => 'Origen no permitido'], 403);
}

function texto($d, $k) {
	return isset($d[$k]) && is_scalar($d[$k]) ? trim((string) $d[$k]) : '';
}

function largoEntre($t, $min, $max) {
	$n = mb_strlen($t, 'UTF-8');
	return $n >= $min && $n <= $max;
}

// IPv6 se agrupa por /64 (un cliente suele tener el bloque entero).
// Detrás de Cloudflare u otro proxy, REMOTE_ADDR es el proxy: config 'cabecera_ip' (p. ej. 'HTTP_CF_CONNECTING_IP').
function claveIp() {
	$cab = config()['cabecera_ip'] ?? '';
	$ip = $cab && !empty($_SERVER[$cab]) ? $_SERVER[$cab] : ($_SERVER['REMOTE_ADDR'] ?? '');
	$bin = @inet_pton($ip);
	return $bin !== false && strlen($bin) === 16 ? bin2hex(substr($bin, 0, 8)) : $ip;
}

// Carpeta de datos (fuera de la web): null si no existe ni se puede crear o no se puede escribir.
function dirDatos($sub = '') {
	$raiz = rtrim(config()['datos'], '/\\');
	$d = $sub ? "$raiz/$sub" : $raiz;
	if (!is_dir($d) && !@mkdir($d, 0700, true)) return null;
	if (!$sub && !is_file("$d/.htaccess")) {
		// Por si la carpeta quedó dentro de la web.
		@file_put_contents("$d/.htaccess", "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
	}
	return is_writable($d) ? $d : null;
}

// Lee y reescribe un archivo con candado: dos envíos a la vez no se pisan.
function conCandado($ruta, $cambiar) {
	$f = @fopen($ruta, 'c+');
	if (!$f || !flock($f, LOCK_EX)) return null;
	list($nuevo, $resultado) = $cambiar(stream_get_contents($f));
	ftruncate($f, 0);
	rewind($f);
	fwrite($f, $nuevo);
	fflush($f);
	flock($f, LOCK_UN);
	fclose($f);
	return $resultado;
}

function siguiente($nombre) {
	return conCandado(dirDatos() . "/$nombre.contador", function ($actual) {
		$n = (int) $actual + 1;
		return [(string) $n, $n];
	});
}

function anotar($nombre, $registro) {
	return file_put_contents(dirDatos() . "/$nombre.jsonl", json_encode($registro, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX) !== false;
}

// ¿Hay cupo? Máximo $max eventos por $ventana segundos para la clave. $sumar=false solo consulta.
function dentroDelLimite($clave, $max, $ventana, $sumar = true) {
	$dir = dirDatos('limites');
	if (!$dir) return false;
	if (mt_rand(1, 200) === 1) {
		// Limpieza de vez en cuando: un archivo por IP no debe llenar el disco.
		foreach (glob("$dir/*.json") ?: [] as $f) if (filemtime($f) < time() - 86400) @unlink($f);
	}
	$ok = conCandado("$dir/" . sha1($clave) . '.json', function ($actual) use ($max, $ventana, $sumar) {
		$desde = time() - $ventana;
		$t = array_values(array_filter((array) json_decode($actual, true), function ($x) use ($desde) { return $x > $desde; }));
		$ok = count($t) < $max;
		if ($ok && $sumar) $t[] = time();
		return [json_encode($t), $ok];
	});
	return $ok === true;
}

function escapar($v) {
	return nl2br(htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
}

function tablaHtml($filas) {
	$h = '<table cellpadding="6" style="border-collapse:collapse;border:1px solid #ddd">';
	foreach ($filas as $k => $v) {
		$h .= '<tr><td style="border:1px solid #ddd;background:#f6f6f6"><b>' . escapar($k) . '</b></td><td style="border:1px solid #ddd">' . escapar($v) . '</td></tr>';
	}
	return $h . '</table>';
}

// $para = null: solo a soporte@. Si hay destinatario, soporte@ va en copia oculta.
function enviarCorreo($para, $nombre, $asunto, $html, $deParte) {
	require_once __DIR__ . '/php-mailer/PHPMailerAutoload.php'; // PHPMailer viejo: no carga en PHP 8
	$m = new PHPMailer();
	$m->CharSet = 'UTF-8';
	$m->IsHTML(true);
	$m->From = REMITENTE;
	$m->FromName = $deParte;
	if ($para) {
		$m->AddAddress($para, $nombre);
		$m->AddBCC(COPIA_PAPAYA, 'Papaya Restobar');
		$m->AddReplyTo(COPIA_PAPAYA, 'Papaya Restobar');
	} else {
		$m->AddAddress(COPIA_PAPAYA, 'Papaya Restobar');
	}
	$m->Subject = $asunto;
	$m->MsgHTML($html);
	return (bool) $m->Send();
}
