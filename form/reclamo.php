<?php
// Libro de Reclamaciones Virtual sin la consola: numera la hoja, la guarda en datos/reclamos.jsonl (conservar
// mínimo 2 años) y manda la copia al correo del consumidor con copia oculta a soporte@ (ahí se responde).
require __DIR__ . '/lib.php';

const PLAZO_DIAS_HABILES = 15;

exigirPost();
if (!dirDatos()) responder(['ok' => false, 'mensaje' => 'No pudimos registrar tu hoja. Escríbenos a ' . COPIA_PAPAYA . ' o por WhatsApp al 924 997 492.'], 503);

$h = validar(entradaJson());
if (is_string($h)) responder(['ok' => false, 'mensaje' => $h], 400);
if (!dentroDelLimite('reclamo|' . claveIp(), 5, 3600)) responder(['ok' => false, 'mensaje' => 'Demasiados envíos. Prueba más tarde.'], 429);

$n = siguiente('reclamos');
if (!$n) responder(['ok' => false, 'mensaje' => 'No pudimos registrar tu hoja. Intenta de nuevo.'], 500);
$r = ['idreclamo' => $n, 'numero' => sprintf('%06d-%s', $n, date('Y')), 'fecha' => date('d/m/Y H:i'), 'vence' => sumarDiasHabiles(date('Y-m-d'), PLAZO_DIAS_HABILES)] + $h;
if (!anotar('reclamos', $r + ['ip' => $_SERVER['REMOTE_ADDR'] ?? ''])) {
	responder(['ok' => false, 'mensaje' => 'No pudimos registrar tu hoja. Intenta de nuevo.'], 500);
}

$filas = [
	'Fecha' => $r['fecha'],
	'Tipo' => $r['tipo'] === 'reclamo' ? 'Reclamo' : 'Queja',
	'Nombre' => $r['nombre'],
	'Documento' => $r['tipoDoc'] . ' ' . $r['numDoc'],
	'Domicilio' => $r['domicilio'],
	'Teléfono' => $r['telefono'],
	'Correo' => $r['email'],
	'Padre, madre o apoderado' => $r['apoderado'] ?: '—',
	($r['bien'] === 'producto' ? 'Producto' : 'Servicio') => $r['descripcion'],
	'Monto reclamado' => $r['monto'] === null ? '—' : 'S/ ' . number_format($r['monto'], 2),
	'Detalle' => $r['detalle'],
	'Pedido' => $r['pedido'],
];
$vence = implode('/', array_reverse(explode('-', $r['vence'])));
$html = '<p>Hemos recibido tu hoja de reclamación <b>N.° ' . escapar($r['numero']) . '</b> en el Libro de Reclamaciones Virtual de papaya.com.pe.</p>'
	. '<p>Proveedor: papaya.com.pe · RUC 10422995566</p>'
	. '<p>Te responderemos a este correo en un plazo máximo de ' . PLAZO_DIAS_HABILES . ' días hábiles (hasta el ' . $vence . ').</p>'
	. tablaHtml($filas)
	. '<p style="color:#666;font-size:13px">La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.</p>';
// Tope diario de correos: el formulario no puede usarse para mandar spam desde papaya.com.pe rotando IPs.
// Pasado el tope la hoja igual queda registrada; el consumidor imprime su constancia.
$copia = dentroDelLimite('reclamo|correos', 40, 86400) && enviarCorreo($r['email'], $r['nombre'], 'Hoja de reclamación N.° ' . $r['numero'] . ' - papaya.com.pe', $html, 'Libro de Reclamaciones papaya.com.pe');

responder(['ok' => true, 'reclamo' => $r, 'copia' => $copia]);

/** Hoja del formulario → datos limpios, o el mensaje de error. */
function validar($p) {
	$h = [
		'tipo' => texto($p, 'tipo'), 'nombre' => texto($p, 'nombre'), 'tipoDoc' => texto($p, 'tipoDoc'), 'numDoc' => texto($p, 'numDoc'),
		'domicilio' => texto($p, 'domicilio'), 'telefono' => texto($p, 'telefono'), 'email' => texto($p, 'email'),
		'apoderado' => texto($p, 'apoderado') ?: null, 'bien' => texto($p, 'bien'), 'descripcion' => texto($p, 'descripcion'),
		'monto' => texto($p, 'monto'), 'detalle' => texto($p, 'detalle'), 'pedido' => texto($p, 'pedido'),
	];
	if (!in_array($h['tipo'], ['reclamo', 'queja'], true)) return 'Elige reclamo o queja.';
	if (!largoEntre($h['nombre'], 3, 150)) return 'Escribe tu nombre completo.';
	if (!in_array($h['tipoDoc'], ['DNI', 'CE', 'PASAPORTE', 'RUC'], true)) return 'Elige el tipo de documento.';
	if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $h['numDoc'])) return 'Número de documento inválido.';
	if ($h['tipoDoc'] === 'DNI' && !preg_match('/^\d{8}$/', $h['numDoc'])) return 'El DNI tiene 8 dígitos.';
	if ($h['tipoDoc'] === 'RUC' && !preg_match('/^\d{11}$/', $h['numDoc'])) return 'El RUC tiene 11 dígitos.';
	if (!largoEntre($h['domicilio'], 5, 200)) return 'Escribe tu domicilio.';
	if (!preg_match('/^\+?[\d\s-]{7,20}$/', $h['telefono'])) return 'Teléfono inválido.';
	if (strlen($h['email']) > 120 || !filter_var($h['email'], FILTER_VALIDATE_EMAIL)) return 'Correo inválido.';
	if ($h['apoderado'] !== null && !largoEntre($h['apoderado'], 1, 150)) return 'Revisa el nombre del apoderado.';
	if (!in_array($h['bien'], ['producto', 'servicio'], true)) return 'Elige producto o servicio.';
	if ($h['monto'] === '') $h['monto'] = null;
	elseif (is_numeric($h['monto']) && $h['monto'] >= 0 && $h['monto'] <= 1000000) $h['monto'] = round((float) $h['monto'], 2);
	else return 'Monto inválido.';
	if (!largoEntre($h['descripcion'], 3, 255)) return 'Describe el producto o servicio.';
	if (!largoEntre($h['detalle'], 10, 3000)) return 'Cuéntanos el detalle (mínimo 10 caracteres).';
	if (!largoEntre($h['pedido'], 5, 2000)) return 'Escribe tu pedido.';
	if (($p['acepta'] ?? null) !== true) return 'Debes confirmar que los datos son verdaderos.';
	return $h;
}

// Solo salta sábados y domingos (no feriados): la fecha mostrada nunca es posterior al plazo legal.
function sumarDiasHabiles($fecha, $dias) {
	$d = new DateTime($fecha);
	while ($dias > 0) {
		$d->modify('+1 day');
		if ((int) $d->format('N') < 6) $dias--;
	}
	return $d->format('Y-m-d');
}
