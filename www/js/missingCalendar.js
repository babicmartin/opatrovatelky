/**
 * Team absence calendar (MissingCalendarControl).
 *
 * The page is rendered by the server; this script only opens the dialog, sends its forms
 * with fetch and swaps the Nette snippets that come back. It decides nothing about rights
 * or day counting - both stay on the server.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-missing-calendar]');
	if (!root || !window.bootstrap) {
		return;
	}

	var modalElement = root.querySelector('#mc-entry-modal');
	var form = root.querySelector('.js-mc-entry-form');
	var deleteForm = root.querySelector('.js-mc-delete-form');
	var deleteButton = root.querySelector('.js-mc-delete');
	var errorBox = root.querySelector('.js-mc-errors');
	var previewBox = root.querySelector('.js-mc-preview');
	var title = root.querySelector('#mc-entry-title');
	var userName = root.querySelector('.js-mc-user-name');
	var toast = root.querySelector('.js-mc-toast');
	var modal = new window.bootstrap.Modal(modalElement);
	var hiddenTypes = {};
	var selection = null;
	var previewTimer = null;
	var previewRequest = 0;
	var toastTimer = null;

	function plural(count, one, few, many) {
		if (count === 1) {
			return count + ' ' + one;
		}

		return count + ' ' + (count >= 2 && count <= 4 ? few : many);
	}

	function showToast(message, isError) {
		toast.textContent = message;
		toast.classList.toggle('is-error', Boolean(isError));
		toast.classList.add('is-visible');
		window.clearTimeout(toastTimer);
		toastTimer = window.setTimeout(function () {
			toast.classList.remove('is-visible');
		}, 3500);
	}

	function showErrors(messages) {
		errorBox.textContent = '';
		messages.forEach(function (message) {
			var line = document.createElement('div');
			line.textContent = message;
			errorBox.appendChild(line);
		});
		errorBox.classList.toggle('d-none', messages.length === 0);
	}

	/**
	 * Sends a request the way Nette recognises as AJAX and returns the JSON payload.
	 */
	function request(url, options) {
		options = options || {};
		options.credentials = 'same-origin';
		options.headers = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

		return window.fetch(url, options).then(function (response) {
			var isJson = (response.headers.get('Content-Type') || '').indexOf('application/json') !== -1;
			if (!isJson) {
				throw new Error(response.status === 403
					? 'Na túto zmenu nemáte oprávnenie.'
					: response.status === 404 ? 'Záznam už neexistuje.' : 'Požiadavku sa nepodarilo spracovať.');
			}

			return response.json();
		}).then(function (payload) {
			if (payload.redirect) {
				window.location.href = payload.redirect;
				throw new Error('Presmerovanie…');
			}

			return payload;
		});
	}

	/**
	 * Swaps Nette snippets. The HTML is the same Latte output the page was built from
	 * (escaped on the server, same origin), not text composed in the browser.
	 */
	function applySnippets(payload) {
		Object.keys(payload.snippets || {}).forEach(function (id) {
			var element = document.getElementById(id);
			if (element) {
				element.innerHTML = payload.snippets[id];
			}
		});
		applyFilter();
	}

	// The legend is part of the redrawn snippet, so the pressed state is put back after every redraw.
	function applyFilter() {
		root.querySelectorAll('.js-mc-filter').forEach(function (button) {
			button.setAttribute('aria-pressed', hiddenTypes[button.getAttribute('data-type')] === true ? 'false' : 'true');
		});
		root.querySelectorAll('.mc-entry').forEach(function (entry) {
			var types = (entry.getAttribute('data-types') || '').split(',').filter(Boolean);
			var isHidden = types.length > 0 && types.every(function (type) {
				return hiddenTypes[type] === true;
			});
			entry.classList.toggle('is-filtered', isHidden);
		});
	}

	function navigate(url, pushState) {
		root.classList.add('is-loading');
		request(url).then(function (payload) {
			applySnippets(payload);
			if (pushState) {
				window.history.pushState({ missingCalendar: true }, '', url);
			}
		}).catch(function () {
			window.location.href = url;
		}).then(function () {
			root.classList.remove('is-loading');
		});
	}

	function resetDeleteButton() {
		deleteButton.textContent = deleteButton.getAttribute('data-label');
		deleteButton.removeAttribute('data-armed');
	}

	function setTypes(types) {
		form.querySelectorAll('input[name="types[]"]').forEach(function (input) {
			input.checked = types.indexOf(input.value) !== -1;
		});
	}

	/**
	 * @param {{id: string, userId: string, userName: string, from: string, to: string, types: string[], notice: string}} record
	 */
	function openDialog(record) {
		var isEdit = record.id !== '0';

		form.elements.id.value = record.id;
		form.elements.dateFrom.value = record.from;
		form.elements.dateTo.value = record.to === record.from ? '' : record.to;
		form.elements.notice.value = record.notice;
		if (form.elements.userId) {
			form.elements.userId.value = record.userId;
		}
		if (userName) {
			userName.textContent = record.userName;
		}
		setTypes(record.types);

		title.textContent = title.getAttribute(isEdit ? 'data-title-edit' : 'data-title-create');
		deleteButton.classList.toggle('d-none', !isEdit);
		resetDeleteButton();
		showErrors([]);
		updatePreview();
		modal.show();
	}

	function openCreateDialog(cell, from, to) {
		openDialog({
			id: '0',
			userId: cell.getAttribute('data-user-id'),
			userName: cell.getAttribute('data-user-name'),
			from: from,
			to: to,
			types: [],
			notice: ''
		});
	}

	function updatePreview() {
		var from = form.elements.dateFrom.value;
		var to = form.elements.dateTo.value || from;
		var current = ++previewRequest;

		window.clearTimeout(previewTimer);
		if (!from) {
			previewBox.textContent = '';
			return;
		}

		previewTimer = window.setTimeout(function () {
			var url = root.getAttribute('data-preview-url')
				.replace('__FROM__', encodeURIComponent(from))
				.replace('__TO__', encodeURIComponent(to));

			request(url).then(function (payload) {
				if (current !== previewRequest) {
					return;
				}

				previewBox.textContent = '';
				if (!payload.success) {
					previewBox.textContent = 'Rozsah dátumov nie je platný.';
					return;
				}

				var working = document.createElement('strong');
				working.textContent = plural(payload.workingDays, 'pracovný deň', 'pracovné dni', 'pracovných dní');
				previewBox.appendChild(working);

				var rest = ' z ' + plural(payload.calendarDays, 'kalendárneho dňa', 'kalendárnych dní', 'kalendárnych dní');
				if (payload.holidays.length > 0) {
					rest += ' · sviatok: ' + payload.holidays.join(', ');
				}
				previewBox.appendChild(document.createTextNode(rest));
			}).catch(function () {
				if (current === previewRequest) {
					previewBox.textContent = '';
				}
			});
		}, 200);
	}

	/**
	 * Posts a Nette form to the page that is shown now (the month may have changed without a reload).
	 */
	function submitForm(targetForm, submitName) {
		var data = new window.FormData(targetForm);
		data.append(submitName, '1');

		return request(window.location.href, { method: 'POST', body: data });
	}

	function handleSaved(payload) {
		if (!payload.success) {
			showErrors(payload.errors && payload.errors.length ? payload.errors : ['Zmenu sa nepodarilo uložiť.']);
			return;
		}

		modal.hide();
		applySnippets(payload);
		showToast(payload.message || 'Uložené.', false);
	}

	function handleFailure(error) {
		showErrors([error.message || 'Požiadavku sa nepodarilo spracovať.']);
	}

	// --- dialog -----------------------------------------------------------------------------

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		showErrors([]);
		submitForm(form, 'save').then(handleSaved).catch(handleFailure);
	});

	['change', 'input'].forEach(function (eventName) {
		form.elements.dateFrom.addEventListener(eventName, updatePreview);
		form.elements.dateTo.addEventListener(eventName, updatePreview);
	});

	// Two clicks instead of a browser confirm(): the first one arms the button.
	deleteButton.addEventListener('click', function () {
		if (!deleteButton.hasAttribute('data-armed')) {
			deleteButton.setAttribute('data-armed', '1');
			deleteButton.textContent = deleteButton.getAttribute('data-confirm-label');
			return;
		}

		deleteForm.elements.id.value = form.elements.id.value;
		submitForm(deleteForm, 'delete').then(handleSaved).catch(handleFailure);
	});

	// --- calendar ---------------------------------------------------------------------------

	root.addEventListener('click', function (event) {
		var link = event.target.closest('a.js-mc-nav');
		if (link) {
			event.preventDefault();
			navigate(link.href, true);
			return;
		}

		var filter = event.target.closest('.js-mc-filter');
		if (filter) {
			hiddenTypes[filter.getAttribute('data-type')] = filter.getAttribute('aria-pressed') === 'true';
			applyFilter();
			return;
		}

		var entry = event.target.closest('.js-mc-edit');
		if (entry) {
			openDialog({
				id: entry.getAttribute('data-id'),
				userId: entry.getAttribute('data-user-id'),
				userName: entry.getAttribute('data-user-name'),
				from: entry.getAttribute('data-from'),
				to: entry.getAttribute('data-to'),
				types: (entry.getAttribute('data-types') || '').split(',').filter(Boolean),
				notice: entry.getAttribute('data-notice') || ''
			});
			return;
		}

		// Keyboard activation of "+"; a mouse click is handled by the drag selection below.
		var add = event.target.closest('.js-mc-add');
		if (add && event.detail === 0) {
			var cell = add.closest('.mc-cell');
			openCreateDialog(cell, cell.getAttribute('data-date'), cell.getAttribute('data-date'));
		}
	});

	function columnCells(userId) {
		return Array.prototype.slice.call(root.querySelectorAll('.mc-cell[data-user-id="' + userId + '"]'));
	}

	function markSelection() {
		var cells = columnCells(selection.userId);
		var start = cells.indexOf(selection.start);
		var end = cells.indexOf(selection.end);
		var low = Math.min(start, end);
		var high = Math.max(start, end);

		cells.forEach(function (cell, index) {
			cell.classList.toggle('is-selecting', index >= low && index <= high);
		});
	}

	// Drag over the days of one person selects a range; a plain click selects one day.
	root.addEventListener('mousedown', function (event) {
		var cell = event.target.closest('.mc-cell[data-can-add]');
		if (!cell || event.button !== 0 || event.target.closest('.mc-entry')) {
			return;
		}

		event.preventDefault();
		selection = { userId: cell.getAttribute('data-user-id'), start: cell, end: cell };
		markSelection();
	});

	root.addEventListener('mouseover', function (event) {
		if (!selection) {
			return;
		}

		var cell = event.target.closest('.mc-cell');
		if (cell && cell.getAttribute('data-user-id') === selection.userId) {
			selection.end = cell;
			markSelection();
		}
	});

	document.addEventListener('mouseup', function () {
		if (!selection) {
			return;
		}

		var dates = [selection.start.getAttribute('data-date'), selection.end.getAttribute('data-date')].sort();
		var cell = selection.start;

		columnCells(selection.userId).forEach(function (item) {
			item.classList.remove('is-selecting');
		});
		selection = null;
		openCreateDialog(cell, dates[0], dates[1]);
	});

	window.addEventListener('popstate', function () {
		navigate(window.location.href, false);
	});
}());
