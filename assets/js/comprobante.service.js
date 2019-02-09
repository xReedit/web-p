grecaptcha.ready(function () {
	grecaptcha.execute('6LeRWZAUAAAAAHZOZ-Ezg7OUEbPjDa3jJncjU1o5', { action: 'homepage' })
	.then(function (token) {						
		document.getElementById('g-recaptcha-response').value = token;
	});
});

function consultar() {
	const formElement = document.getElementById("frm_doc");
	const data = new URLSearchParams();
	for (const pair of new FormData(formElement)) {
		data.append(pair[0], pair[1]);
	}

	fetch('comprobante.php', {
		method: 'post',
		body: data,
	})
	.then((res)=>{
		return res.json();
	})
	.then((res)=>{
		console.log(res);
	})
		
}