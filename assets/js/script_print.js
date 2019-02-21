// function xConsultarPrint() {
const ruta = 'http://192.168.1.64/restobar/print/pruebas.print_url.php';//window.localStorage.getItem("_ruta");
const impr = 'smb://pc:182182@192.168.1.56/ticketera1';//window.localStorage.getItem("_impr");
	// const code = window.localStorage.getItem("_code");
	
	const _header = { 'Content-Type': 'application/json' };
	var data = { ip_print: impr };
	
	$.ajax({
		url: ruta,
		data: {
			ip_print: impr
		}
	})
	// .done(function (a) {		
	// 	console.log(a);		
	// });


// }