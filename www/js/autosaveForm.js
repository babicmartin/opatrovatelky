(function () {
	var formSelector = '.js-autosave-form';
	var controlSelector = '.js-autosave-control';
	var defaultBorder = '1px solid #CED4DA';
	var savedBorder = '2px solid #8A2062';
	var errorBorder = '2px solid #dc3545';
	var contextByFormClass = {
		'agency-update-form': 'agency.update',
		'partner-update-form': 'partner.update',
		'babysitter-main-form': 'babysitter.main',
		'babysitter-address-form': 'babysitter.address',
		'babysitter-education-form': 'babysitter.education',
		'babysitter-profile-form': 'babysitter.profile',
		'babysitter-pdf-form': 'babysitter.pdf',
		'babysitter-work-profile-form': 'babysitter.workProfile',
		'family-short-info-form': 'family.shortInfo',
		'family-info-form': 'family.info',
		'family-address-form': 'family.address',
		'turnus-update-form': 'turnus.update',
		'turnus-status-a1-form': 'turnus.statusA1',
		'todo-update-form': 'todo.update',
		'proposal-update-form': 'proposal.update',
		'missing-registry-form': 'missingRegistry.row',
		'country-update-form': 'country.update',
		'translation-update-form': 'translation.update',
		'user-profile-update-form': 'user.profile',
		'user-access-update-form': 'user.access',
		'babysitter-document-form': 'documents.babysitter',
		'family-document-form': 'documents.family',
		'agency-document-form': 'documents.agency',
		'partner-document-form': 'documents.partner',
		'turnus-document-form': 'documents.turnus'
	};

	function isAutosaveControl(control) {
		return control instanceof HTMLElement && control.matches(controlSelector);
	}

	function findAutosaveControl(target) {
		if (!(target instanceof HTMLElement)) {
			return null;
		}

		if (isAutosaveControl(target)) {
			return target;
		}

		var wrapper = target.closest(controlSelector);
		if (wrapper && target.matches('input, select, textarea')) {
			return target;
		}

		return null;
	}

	function markControl(control, border) {
		control.style.border = border;
	}

	function resetControl(control) {
		window.setTimeout(function () {
			markControl(control, defaultBorder);
		}, 2000);
	}

	function getFormContext(form) {
		if (form.dataset.autosaveContext) {
			return form.dataset.autosaveContext;
		}

		for (var className in contextByFormClass) {
			if (Object.prototype.hasOwnProperty.call(contextByFormClass, className) && form.classList.contains(className)) {
				return contextByFormClass[className];
			}
		}

		return '';
	}

	function getFieldName(control) {
		if (!control.name) {
			return '';
		}

		return control.name.replace(/\[\]$/, '');
	}

	function getControlValue(control) {
		if (control.type === 'checkbox') {
			return control.name && control.name.indexOf('[]') !== -1
				? control.value
				: (control.checked ? '1' : '0');
		}

		if (control.type === 'radio') {
			return control.checked ? control.value : '';
		}

		return control.value;
	}

	function appendHiddenFields(form, formData) {
		Array.prototype.forEach.call(form.querySelectorAll('input[type="hidden"]'), function (hidden) {
			if (hidden.name) {
				formData.append(hidden.name, hidden.value);
			}
		});
	}

	function buildPartialFormData(form, control, context) {
		var fieldName = getFieldName(control);
		if (!context || !fieldName) {
			return new FormData(form);
		}

		var formData = new FormData();
		appendHiddenFields(form, formData);
		formData.set('_do', 'autosavePartial');
		formData.set('__autosave_context', context);
		formData.set('__autosave_field', fieldName);
		formData.set('__autosave_value', getControlValue(control));

		if (control.type === 'checkbox') {
			formData.set('__autosave_checked', control.checked ? '1' : '0');
			if (control.name.indexOf('[]') !== -1) {
				formData.set('__autosave_item_id', control.value);
			}
		}

		if (control.name && control.name.indexOf('[]') === -1) {
			formData.set(control.name, getControlValue(control));
		} else if (control.name && control.checked) {
			formData.append(control.name, control.value);
		}

		return formData;
	}

	function buildAutosaveRequest(form, control) {
		var context = getFormContext(form);
		if (!context) {
			return {
				body: new FormData(form),
				expectsJson: false,
				url: form.action
			};
		}

		return {
			body: buildPartialFormData(form, control, context),
			expectsJson: true,
			url: form.action
		};
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
		var request = buildAutosaveRequest(form, control);

		/*
		 * Legacy full-row autosave kept intentionally. Use this body to return to
		 * the previous behavior where every blur/change posts the whole form:
		 *
		 * fetch(form.action, {
		 *     method: form.method || 'POST',
		 *     body: new FormData(form),
		 *     headers: {
		 *         'X-Requested-With': 'XMLHttpRequest',
		 *         'Accept': 'application/json'
		 *     },
		 *     credentials: 'same-origin'
		 * })
		 */
		fetch(request.url, {
			method: form.method || 'POST',
			body: request.body,
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			},
			credentials: 'same-origin'
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Autosave failed.');
			}

			if (!request.expectsJson) {
				return null;
			}

			if ((response.headers.get('content-type') || '').indexOf('application/json') === -1) {
				throw new Error('Autosave did not return JSON.');
			}

			return response.json().then(function (payload) {
				if (!payload || payload.success !== true) {
					throw new Error(payload && payload.message ? payload.message : 'Autosave was rejected.');
				}

				return payload;
			});
		}).then(function () {
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
		var control = findAutosaveControl(event.target);
		if (control) {
			submitForm(control);
		}
	});

	document.addEventListener('focusout', function (event) {
		var control = findAutosaveControl(event.target);
		if (!control) {
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
