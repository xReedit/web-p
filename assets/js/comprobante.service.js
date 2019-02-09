grecaptcha.ready(function () {
	grecaptcha.execute('6LeRWZAUAAAAAHZOZ-Ezg7OUEbPjDa3jJncjU1o5', { action: 'homepage' })
	.then(function (token) {						
		document.getElementById('g-recaptcha-response').value = token;
	});
});

function consultar() {
	const e_res = $("#txt_res");
	const formData = new FormData(document.getElementById("contact-form"));	

	e_res.addClass("xInvisible");
	fetch("../../../form/comprobante.service.php", {
		method: "post",
		body: formData
		}).then(function (response) {
			return response.json();
		}).then(function (res) {
			if (res.res) { // si todo va bien
				e_res.text(res.msj);
			} else {				
				e_res.removeClass("xInvisible");
				e_res.text(res.msj);				
			}
    });
		
}