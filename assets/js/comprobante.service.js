var dataLinks = {};



grecaptcha.ready(function () {	
	iniCaptcha();
});

$("#contact-form").submit(function(event) {	
	if (!event.target.checkValidity()) return;	
	xRetornaMoneda($("importe"));
	consultar();
	event.preventDefault();
});

function consultar() {
	const e_res = $("#txt_res");
	const formData = new FormData(document.getElementById("contact-form"));	

	btn_consultar.disabled = true;
	$(btn_consultar).text("Cargando ...");	

	e_res.addClass("xInvisible");
	fetch("../../../form/comprobante.service.php", {
		method: "post",
		body: formData
		}).then(function (response) {
			return response.json();
		}).then(function (res) {			
			console.log(res);
			if (res.res) { // si todo va bien
				const _data = JSON.parse(res.data);
				if (_data.success) {
					// muestra los iconos
					const html_btns =
						"<span>Click para descargar</span><br>" +
						'<div class="p-10 btn btn-file" data-type="pdf" onclick="xDownloadFile(this)">' +
						'<img src="images/001-pdf.png" alt="">' +
						"</div>" +
						'<div class="p-10 btn btn-file" data-type="xml" onclick="xDownloadFile(this)">' +
						'<img src="images/002-xml.png" alt="">' +
						"<div>";
								
					
					$("#_btn_send").addClass('xInvisible');
					$("#_btn_res").html(html_btns).trigger('create');

					dataLinks = _data.links;

				} else {
					iniCaptcha();
					e_res.removeClass("xInvisible");
					e_res.text('No se encontro ningun comprobante.');		
				}
			} else {
				iniCaptcha();			
				e_res.removeClass("xInvisible");
				e_res.text(res.msj);				
			}

			$(btn_consultar).text('Consultar');
			btn_consultar.disabled = false;
		}).catch(function (err) {
			console.log(err);
			iniCaptcha();
			e_res.removeClass("xInvisible");
			e_res.text('No se pudo conectar.');	
		});;
		
}

function iniCaptcha() {
	grecaptcha.execute('6LeRWZAUAAAAAHZOZ-Ezg7OUEbPjDa3jJncjU1o5', { action: 'homepage' })
		.then(function (token) {
			document.getElementById('g-recaptcha-response').value = token;
		});
}

function xDownloadFile(obj) {
	const tipo = $(obj).attr('data-type');
	const _url = tipo === 'pdf' ? dataLinks.pdf : dataLinks.xml;
	window.open(_url, "_blank");
}


function xRetornaMoneda(xObj) {
	var xVal = parseFloat($(xObj).val());
	if (isNaN(xVal)) { xVal = 0; }
	xObj.value = parseFloat(xVal).toFixed(2);
}

function conMayusculas(field) { field.value = field.value.toUpperCase(); };