(function () {
	var formSelector = '.js-autosave-form';
	var controlSelector = '.js-autosave-control';
	var defaultBorder = '1px solid #CED4DA';
	var savedBorder = '2px solid #8A2062';
	var errorBorder = '2px solid #dc3545';
	var datepickerDuplicateWindowMs = 1200;
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

	function isAutosaveDatepickerControl(control) {
		return isAutosaveControl(control) && control.matches('input.datepicker');
	}

	function getTimestamp() {
		return Date.now ? Date.now() : new Date().getTime();
	}

	function wasDatepickerSubmittedRecently(control) {
		var previousValue = control.dataset.autosaveDatepickerValue || '';
		var previousTime = parseInt(control.dataset.autosaveDatepickerTime || '0', 10);

		return previousValue === getControlValue(control)
			&& getTimestamp() - previousTime < datepickerDuplicateWindowMs;
	}

	function markDatepickerSubmitted(control) {
		control.dataset.autosaveDatepickerValue = getControlValue(control);
		control.dataset.autosaveDatepickerTime = String(getTimestamp());
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
			if (form._autosavePendingDatepickerControl instanceof HTMLElement) {
				var pendingDatepickerControl = form._autosavePendingDatepickerControl;
				form._autosavePendingDatepickerControl = null;
				window.setTimeout(function () {
					submitDatepickerControl(pendingDatepickerControl);
				}, 0);
			}
		});
	}

	function submitDatepickerControl(control) {
		var form = control.closest(formSelector);
		if (!form || !isAutosaveDatepickerControl(control)) {
			return;
		}

		if (form.dataset.autosaveSaving === '1') {
			form._autosavePendingDatepickerControl = control;
			return;
		}

		if (wasDatepickerSubmittedRecently(control)) {
			return;
		}

		markDatepickerSubmitted(control);
		submitForm(control);
	}

	document.addEventListener('change', function (event) {
		var control = findAutosaveControl(event.target);
		if (control) {
			if (isAutosaveDatepickerControl(control)) {
				submitDatepickerControl(control);
				return;
			}

			submitForm(control);
		}
	});

	document.addEventListener('focusout', function (event) {
		var control = findAutosaveControl(event.target);
		if (!control) {
			return;
		}

		if (control.matches('input[type="text"], input[type="date"], textarea')) {
			if (isAutosaveDatepickerControl(control)) {
				submitDatepickerControl(control);
				return;
			}

			submitForm(control);
		}
	});

	if (window.jQuery && window.jQuery.fn.datepicker) {
		function createDatepickerCallback(previousCallback) {
			var callback = function () {
				var result;

				if (typeof previousCallback === 'function') {
					result = previousCallback.apply(this, arguments);
				}

				submitDatepickerControl(this);

				return result;
			};

			callback.autosaveWrapped = true;

			return callback;
		}

		function wrapDatepickerCallback($control, optionName) {
			var currentCallback = $control.datepicker('option', optionName);
			if (currentCallback && currentCallback.autosaveWrapped === true) {
				return;
			}

			$control.datepicker('option', optionName, createDatepickerCallback(currentCallback));
		}

		function ensureAutosaveDatepicker($, control) {
			var $control;

			if (!isAutosaveDatepickerControl(control)) {
				return;
			}

			$control = $(control);
			if (!$control.data('datepicker') && !control.classList.contains('hasDatepicker')) {
				$control.datepicker({
					changeMonth: true,
					changeYear: true,
					dateFormat: 'dd.mm.yy'
				});
			}

			wrapDatepickerCallback($control, 'onSelect');
			wrapDatepickerCallback($control, 'onClose');
		}

		function installAutosaveDatepickers($, root) {
			$(formSelector + ' ' + controlSelector + '.datepicker', root || document).each(function () {
				ensureAutosaveDatepicker($, this);
			});
		}

		window.jQuery(function ($) {
			installAutosaveDatepickers($, document);
		});

		document.addEventListener('focusin', function (event) {
			var control = findAutosaveControl(event.target);
			if (control && isAutosaveDatepickerControl(control)) {
				ensureAutosaveDatepicker(window.jQuery, control);
			}
		});
	}
})();
