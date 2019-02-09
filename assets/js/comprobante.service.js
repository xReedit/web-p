grecaptcha.ready(function () {
	grecaptcha.execute('6LeRWZAUAAAAAHZOZ-Ezg7OUEbPjDa3jJncjU1o5', { action: 'homepage' })
	.then(function (token) {						
		document.getElementById('g-recaptcha-response').value = token;
	});
});

function consultar() {
	var formData = new FormData(document.getElementById("contact-form"));

	fetch("../../../form/comprobante.service.php", {
		method: "post",
		body: formData
	})
    .then(res => {
      return res.json();
    })
    .then(res => {
      console.log(res);
    });
		
}