<?php
// Copia este archivo como config.php (no se sube a git) y pon las llaves de Culqi:
// CulqiPanel → Desarrollo → API Keys. Mientras Culqi no apruebe el comercio, usa las de prueba (pk_test / sk_test).
return [
	'culqi_publica' => 'pk_test_xxxxxxxxxxxxxxxx',
	'culqi_secreta' => 'sk_test_xxxxxxxxxxxxxxxx',
	// Carpeta donde se guardan solicitudes, hojas de reclamación y límites. Debe estar FUERA de la carpeta pública
	// y el PHP debe poder escribir en ella. Por defecto: una carpeta "papaya-datos" al lado de la carpeta de la web.
	// 'datos' => '/home/usuario/papaya-datos',
	// Solo si el sitio pasa por Cloudflare: así los límites por IP ven la IP del cliente y no la del proxy.
	// 'cabecera_ip' => 'HTTP_CF_CONNECTING_IP',
];
