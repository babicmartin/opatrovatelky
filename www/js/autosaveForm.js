(function () {
	var formSelector = '.js-autosave-form';
	var controlSelector = '.js-autosave-control';
	var defaultBorder = '1px solid #CED4DA';
	var savedBorder = '2px solid #8A2062';
	var errorBorder = '2px solid #dc3545';

	function isAutosaveControl(control) {
		return control instanceof HTMLElement && control.matches(controlSelector);
	}

	function markControl(control, border) {
		control.style.border = border;
	}

	function resetControl(control) {
		window.setTimeout(function () {
			markControl(control, defaultBorder);
		}, 2000);
	}

	function submitForm(control) {
		var form = control.closest(formSelector);
		if (!form || form.dataset.autosaveSaving === '1') {
			return;
		}

		form.dataset.autosaveSaving = '1';
		form.classList.remove('is-autosave-saved', 'is-autosave-error');
		form.classList.add('is-autosave-saving');
		markControl(control, savedBorder);

		fetch(form.action, {
			method: form.method || 'POST',
			body: new FormData(form),
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			},
			credentials: 'same-origin'
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Autosave failed.');
			}

			form.classList.remove('is-autosave-saving', 'is-autosave-error');
			form.classList.add('is-autosave-saved');
			markControl(control, savedBorder);
			resetControl(control);
			if (control.classList.contains('updateSelectReload')) {
				window.location.reload();
			}
		}).catch(function () {
			form.classList.remove('is-autosave-saving', 'is-autosave-saved');
			form.classList.add('is-autosave-error');
			markControl(control, errorBorder);
		}).finally(function () {
			form.dataset.autosaveSaving = '0';
		});
	}

	document.addEventListener('change', function (event) {
		if (isAutosaveControl(event.target)) {
			submitForm(event.target);
		}
	});

	document.addEventListener('focusout', function (event) {
		var control = event.target;
		if (!isAutosaveControl(control)) {
			return;
		}

		if (control.matches('input[type="text"], input[type="date"], textarea')) {
			submitForm(control);
		}
	});

	if (window.jQuery && window.jQuery.fn.datepicker) {
		window.jQuery(function ($) {
			$(formSelector + ' ' + controlSelector + '.datepicker').datepicker('option', 'onSelect', function () {
				submitForm(this);
			});
		});
	}
})();
