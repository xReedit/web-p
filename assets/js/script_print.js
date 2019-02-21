// function xConsultarPrint() {
	const ruta = window.localStorage.getItem("_ruta");
	const impr = window.localStorage.getItem("_impr");
	const code = window.localStorage.getItem("_code");
	
	const _header = { 'Content-Type': 'application/json' };
	var data = { ip_print: impr };
	
	$.ajax({
		type: 'POST', url: ruta,
		data: {
			ip_print: impr
		}
	}).done(function (a) {
		console.log(a);
	});
// }