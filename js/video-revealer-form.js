$videoRevealerSubmit = $('.video-revealer-submit');
$spinner = $('.spinner-border');
$form = $('#video-revealer-form');
$formErrorMessage = $('#form-error-message');

const resetSubmitButton = () => {
	$spinner.css('display', 'none');
	$videoRevealerSubmit.append('Submit');
	$videoRevealerSubmit.prop('disabled', false);
};

const activateSpinner = () => {
	$spinner.css('display', 'flex');
	$videoRevealerSubmit
		.contents()
		.filter(function() {
			return this.nodeType == 3;
		})
		.remove();
	$videoRevealerSubmit.prop('disabled', true);
};

function isRequired(value) {
	return value.trim() !== '';
}

function isValidEmail(email) {
	const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return regex.test(email);
}

function isValidPhoneNumber(phoneNumber) {
	const regex = /^(\+1\s?)?(\(?\d{3}\)?[\s.-]?)?\d{3}[\s.-]?\d{4}$/;
	return regex.test(phoneNumber);
}

function isChecked(value) {
	return value === true;
}

function validateField(selector, validationFn, errorMsg) {
	const field = $(selector);
	let isValid = true;
	const errorContainer = field.is(':checkbox')
		? field.closest('#consent-container').find('.error-message')
		: field.next('.error-message');
	if (field.is(':checkbox')) {
		isValid = validationFn(field.prop('checked'));
	} else {
		isValid = validationFn(field.val());
	}

	if (!isValid) {
		errorContainer.text(errorMsg);
	} else {
		errorContainer.text('');
	}

	return isValid;
}

$form.submit(function(e) {
	e.preventDefault();

	let isValid = true;
	isValid &= validateField('#name', isRequired, 'Name is required.');
	isValid &= validateField('#email', isValidEmail, 'Please enter a valid email address.');
	isValid &= validateField('#phone', isValidPhoneNumber, 'A valid phone number is required.');
	isValid &= validateField('#company', isRequired, 'Company name is required.');
	isValid &= validateField('#consent-checkbox', isChecked, 'Please consent to proceed.');

	if (!isValid) {
		return;
	}

	activateSpinner();

	grecaptcha.ready(function() {
		grecaptcha.execute('6Ld4fhUqAAAAAI8Kv9ZybK7LtlPqTNeYMaVy4BpA', { action: 'submit' }).then(function(token) {
			$('#recaptcha-response').val(token);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'video_revealer_form_submit',
					consentCheckbox: $('#consent-checkbox').prop('checked') ? 'on' : 'off',
					name: $('#name').val(),
					email: $('#email').val(),
					phone: $('#phone').val(),
					company: $('#company').val(),
					recaptchaResponse: $('#recaptcha-response').val(),
					nonceValue: $('input[name="video_revealer_form_nonce"]').val()
				},
				success: function(response) {
					$formErrorMessage.empty();

					if (response.status === 'error') {
						$.each(response.errors, function(field, message) {
							$formErrorMessage.append('<div>' + message + '</div>');
						});
						resetSubmitButton();
					} else {
						$form.hide();
						$('#video-container').css('display', 'flex');
					}
				},
				error: function() {
					$formErrorMessage.text('An unexpected error occurred. Please try again later.');
					resetSubmitButton();
				}
			});
		});
	});
});
