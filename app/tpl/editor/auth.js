
$(document).ready(function() {
    
    var loginForm = $('#loginForm');
	var auth = {
		post: function(el) {
            console.log(el);
			var obj = {
				'login': $(el).find('#inputLogin').val(),
				'password': $(el).find('#inputPassword').val()
			};

			$.ajax({
				type: "POST",
				url: '/?editor',
				dataType: 'json',
				data: obj,
				success: function(data) {
                    $('div.status-login').html('<div class="alert alert-success" role="alert" id="error-alert">' + data.message + '</div>');

                    setTimeout(() => {
                        window.location.pathname = window.location.pathname;
                    }, 1000);
				},
				error: function(xhr, textStatus, errorThrown) {
                    if (typeof xhr.responseText !== "undefined") {
                        var data = JSON.parse(xhr.responseText);
                        
                        $('div.status-login').html('<div class="alert alert-danger" role="alert" id="error-alert">' + data.message + '</div>');
					}
				}
			});
		}
	};


    loginForm.on('submit', (event) => {
        event.preventDefault();
        auth.post(loginForm);
	});
});
