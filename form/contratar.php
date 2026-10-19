<?php
// Contratación en línea sin la consola: planes fijos y cobro con la llave SECRETA de Culqi (el navegador solo manda
// el token del checkout). Cada intento se anota en datos/solicitudes.jsonl y los pagos llegan por correo a soporte@.
// Mismo contrato JSON que /api/contratar de la consola: contratar.html funciona igual con cualquiera de los dos.
require __DIR__ . '/lib.php';

// Céntimos. Deben coincidir con la tabla de planes de index.html.
const PLANES = [
	['idplan' => 1, 'nombre' => 'Plan Básico', 'monto' => 8990],
	['idplan' => 2, 'nombre' => 'Plan Mype', 'monto' => 11990],
	['idplan' => 3, 'nombre' => 'Plan Full', 'monto' => 14990],
];
const MAX_INTENTOS = 3; // llamadas a /charges por solicitud (1.ª + reenvío 3DS, con margen)
const MENSAJE_INCIERTO = 'No pudimos confirmar el pago. No vuelvas a pagar: te escribiremos al correo que dejaste.';

$c = config();
$disponible = $c['culqi_publica'] && $c['culqi_secreta'] && dirDatos();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$motivo = !($c['culqi_publica'] && $c['culqi_secreta']) ? 'faltan las llaves de Culqi en form/config.php' : (dirDatos() ? null : 'no se puede escribir en la carpeta de datos');
	responder(['disponible' => (bool) $disponible, 'llavePublica' => $disponible ? $c['culqi_publica'] : '', 'planes' => $disponible ? PLANES : [], 'motivo' => $motivo]);
}
exigirPost();
if (!$disponible) responder(['resultado' => 'error', 'mensaje' => 'El pago en línea no está disponible en este momento.'], 503);

$p = entradaJson();
$token = texto($p, 'tokenId');
$deviceId = texto($p, 'deviceId');
if (!preg_match('/^(tkn|ype)_[A-Za-z0-9_]{6,80}$/', $token) || !preg_match('/^[A-Za-z0-9_-]{0,100}$/', $deviceId)) {
	responder(['resultado' => 'error', 'mensaje' => 'Datos del pago inválidos. Recarga la página.'], 400);
}
$ip = claveIp();

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax'); // PHP 7.3+; en versiones anteriores se ignora
session_set_cookie_params(0, '/', '', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);
session_start();

if (!empty($p['auth3ds'])) {
	// Reenvío tras autenticar con el banco (3DS): solo la misma solicitud, desde el mismo navegador y con el mismo token.
	$s = $_SESSION['contratar'] ?? null;
	$vigente = $s && is_array($p['auth3ds']) && hash_equals($s['token'], $token) && (string) $s['id'] === texto($p, 'idsolicitud')
		&& $s['intentos'] < MAX_INTENTOS && time() - $s['t'] < 3600;
	if (!$vigente) responder(['resultado' => 'error', 'mensaje' => 'La solicitud ya no está vigente. Recarga la página y vuelve a intentarlo.'], 404);
	$auth = $p['auth3ds'];
} else {
	$s = validar($p);
	if (is_string($s)) responder(['resultado' => 'error', 'mensaje' => $s], 400);
	// Doble clic, dos pestañas: un RUC con un pago (o uno sin confirmar) en los últimos 15 min no paga de nuevo.
	if (!dentroDelLimite('pagado|' . $s['ruc'], 1, 900, false)) {
		responder(['resultado' => 'error', 'mensaje' => 'Ya recibimos un pago para este RUC hace unos minutos. Revisa tu correo o escríbenos por WhatsApp antes de volver a pagar.'], 409);
	}
	if (!dentroDelLimite("altas|$ip", 10, 3600)) responder(['resultado' => 'error', 'mensaje' => 'Demasiadas solicitudes. Prueba más tarde o escríbenos por WhatsApp.'], 429);
	$id = siguiente('solicitudes');
	if (!$id) responder(['resultado' => 'error', 'mensaje' => 'No pudimos registrar tu solicitud. Intenta de nuevo.'], 500);
	$s = $s + ['id' => $id, 'token' => $token, 'intentos' => 0, 't' => time()];
	$auth = null;
}

// Freno a carding: cada llamada a Culqi (aprobada, rechazada o 3DS) gasta un cupo, reservado antes de cobrar
// (atómico: una ráfaga en paralelo no pasa). Un cliente normal usa 1 o 2; quien prueba tarjetas se queda sin cupo.
if (!dentroDelLimite("cobros|$ip", 6, 3600)) {
	responder(['resultado' => 'rechazado', 'mensaje' => 'Demasiados intentos de pago. Prueba más tarde o escríbenos por WhatsApp.'], 429);
}
$s['intentos']++;
$_SESSION['contratar'] = $s;
$r = cobrar($s, $token, $deviceId, $auth);

if ($r === null) {
	// Sin respuesta el cargo PUDO entrar: sin reenvíos (no hay doble cargo); soporte lo revisa en CulqiPanel.
	cerrar($s, 'incierto', null);
	responder(['resultado' => 'error', 'mensaje' => MENSAJE_INCIERTO, 'idsolicitud' => $s['id']], 502);
}
if (($r['action_code'] ?? '') === 'REVIEW') {
	responder(['resultado' => 'requiere_accion', 'idsolicitud' => $s['id']]);
}
if (($r['object'] ?? '') === 'error') {
	cerrar($s, 'rechazada', $r);
	responder(['resultado' => 'rechazado', 'mensaje' => (string) ($r['user_message'] ?? 'El banco rechazó el pago. Prueba con otra tarjeta.')]);
}
$ok = ($r['object'] ?? '') === 'charge' && ($r['outcome']['type'] ?? 'venta_exitosa') === 'venta_exitosa'
	&& (int) ($r['amount'] ?? 0) === $s['monto'] && ($r['currency_code'] ?? '') === 'PEN';
if (!$ok) {
	cerrar($s, 'incierto', $r);
	responder(['resultado' => 'error', 'mensaje' => MENSAJE_INCIERTO, 'idsolicitud' => $s['id']], 502);
}
cerrar($s, 'pagada', $r);
responder(['resultado' => 'pagado', 'idsolicitud' => $s['id']]);

/** Datos del formulario → solicitud, o el mensaje de error. */
function validar($p) {
	$plan = null;
	foreach (PLANES as $x) if ($x['idplan'] === (int) ($p['idplan'] ?? 0)) $plan = $x;
	if (!$plan) return 'Elige un plan.';
	if ((int) ($p['montoVisto'] ?? 0) !== $plan['monto']) return 'El precio del plan cambió. Recarga la página.';
	$d = [
		'idplan' => $plan['idplan'], 'plan' => $plan['nombre'], 'monto' => $plan['monto'],
		'ruc' => texto($p, 'ruc'), 'razonSocial' => texto($p, 'razonSocial'), 'contacto' => texto($p, 'contacto'),
		'email' => texto($p, 'email'), 'telefono' => texto($p, 'telefono'),
	];
	if (!preg_match('/^(10|15|17|20)\d{9}$/', $d['ruc'])) return 'Revisa el RUC (11 dígitos).';
	if (!largoEntre($d['razonSocial'], 2, 150)) return 'Escribe la razón social.';
	if (!largoEntre($d['contacto'], 3, 100)) return 'Escribe tu nombre.';
	if (strlen($d['email']) > 120 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) return 'Revisa tu correo.';
	$digitos = preg_replace('/\D/', '', $d['telefono']);
	if (strlen($d['telefono']) > 20 || strlen($digitos) < 7 || strlen($digitos) > 15) return 'Revisa tu celular.';
	if (($p['acepta'] ?? null) !== true) return 'Debes aceptar los Términos y Condiciones.';
	return $d;
}

/** POST /charges a Culqi. null = resultado desconocido (red, tiempo agotado, error 5xx o api_error): el cargo pudo entrar. */
function cobrar($s, $token, $deviceId, $auth) {
	$nombre = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L} ]/u', '', $s['contacto'])));
	$partes = explode(' ', $nombre ?: 'Cliente Papaya', 2);
	$cuerpo = [
		'amount' => $s['monto'],
		'currency_code' => 'PEN',
		'email' => $s['email'],
		'source_id' => $token,
		'description' => 'Papaya alta SOL' . $s['id'],
		'metadata' => ['solicitud' => (string) $s['id']],
		'antifraud_details' => array_filter([
			'first_name' => mb_substr($partes[0], 0, 50, 'UTF-8'),
			'last_name' => mb_substr($partes[1] ?? 'Papaya', 0, 50, 'UTF-8'),
			'phone_number' => substr(preg_replace('/\D/', '', $s['telefono']), -15),
			'country_code' => 'PE',
			'device_finger_print_id' => $deviceId,
		]),
	];
	if ($auth) $cuerpo['authentication_3DS'] = $auth;
	$ch = curl_init(config()['culqi_api'] . '/charges');
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode($cuerpo),
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . config()['culqi_secreta'], 'Content-Type: application/json'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 30,
	]);
	$resp = curl_exec($ch);
	$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$r = is_string($resp) ? json_decode($resp, true) : null;
	if (!is_array($r) || $http >= 500 || ($r['type'] ?? '') === 'api_error') return null;
	return $r;
}

/** Fin de la solicitud: se anota, se avisa a soporte@ si hay que actuar y se cierra el reenvío 3DS. */
function cerrar($s, $estado, $r) {
	unset($_SESSION['contratar']);
	$fuente = (string) ($r['source']['id'] ?? '');
	$registro = [
		'id' => $s['id'], 'fecha' => date('Y-m-d H:i:s'), 'estado' => $estado, 'plan' => $s['plan'], 'monto' => $s['monto'] / 100,
		'ruc' => $s['ruc'], 'razonSocial' => $s['razonSocial'], 'contacto' => $s['contacto'], 'email' => $s['email'], 'telefono' => $s['telefono'],
		'referencia' => $r['id'] ?? null, 'metodo' => $r ? (strpos($fuente, 'ype_') === 0 ? 'YAPE' : 'TARJETA') : null,
		'mensaje' => $r['merchant_message'] ?? ($r['outcome']['merchant_message'] ?? null), 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
	];
	anotar('solicitudes', $registro);
	if ($estado === 'rechazada') return;
	dentroDelLimite('pagado|' . $s['ruc'], 1, 900);
	$titulo = $estado === 'pagada' ? 'pagada' : 'REVISAR EN CULQIPANEL (sin confirmar)';
	$intro = $estado === 'pagada'
		? '<p>Nueva contratación en línea pagada. Coordina la instalación con el cliente.</p>'
		: '<p><b>No se pudo confirmar este pago.</b> Búscalo en CulqiPanel (descripción "Papaya alta SOL' . $s['id'] . '") y avisa al cliente.</p>';
	$filas = [
		'Solicitud' => 'SOL' . $s['id'], 'Fecha' => $registro['fecha'], 'Plan' => $s['plan'], 'Monto' => 'S/ ' . number_format($s['monto'] / 100, 2),
		'RUC' => $s['ruc'], 'Razón social' => $s['razonSocial'], 'Contacto' => $s['contacto'], 'Correo' => $s['email'], 'Celular' => $s['telefono'],
		'Referencia Culqi' => $registro['referencia'] ?: '—', 'Medio' => $registro['metodo'] ?: '—',
	];
	enviarCorreo(null, '', 'Contratación SOL' . $s['id'] . ' - ' . $s['plan'] . ' - ' . $titulo, $intro . tablaHtml($filas), 'Contratación papaya.com.pe');
}
