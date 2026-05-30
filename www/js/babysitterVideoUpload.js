(function () {
	var form = document.querySelector('.js-babysitter-video-upload-form');
	var modalEl = document.getElementById('videoUploadModal');
	if (!form || !modalEl || !window.bootstrap) {
		return;
	}

	var fileInput = form.querySelector('.js-babysitter-video-input');
	var submitBtn = form.querySelector('.js-babysitter-video-submit');
	var errorEl = form.querySelector('.js-babysitter-video-error');
	var ringEl = modalEl.querySelector('.pdf-loader-ring');
	var progressEl = modalEl.querySelector('.pdf-loader-progress');
	var percentEl = modalEl.querySelector('.pdf-loader-percent');
	var statusEl = modalEl.querySelector('.pdf-loader-status');
	var closeBtn = modalEl.querySelector('.pdf-loader-close');
	var modal = new bootstrap.Modal(modalEl);
	var CIRCUMFERENCE = 2 * Math.PI * 52;

	progressEl.style.strokeDasharray = CIRCUMFERENCE;
	progressEl.style.strokeDashoffset = CIRCUMFERENCE;

	function setError(message) {
		if (errorEl) {
			errorEl.textContent = message || '';
		}
	}

	function setProgress(percent) {
		var clamped = Math.max(0, Math.min(100, percent));
		progressEl.style.strokeDashoffset = CIRCUMFERENCE * (1 - clamped / 100);
		percentEl.textContent = Math.round(clamped) + '%';
	}

	function resetModal() {
		ringEl.dataset.state = 'loading';
		statusEl.textContent = statusEl.dataset.statusLoading;
		closeBtn.hidden = true;
		setProgress(0);
	}

	function finishSuccess() {
		setProgress(100);
		ringEl.dataset.state = 'success';
		statusEl.textContent = statusEl.dataset.statusSuccess;
		window.setTimeout(function () {
			window.location.reload();
		}, 800);
	}

	function finishError(message) {
		ringEl.dataset.state = 'error';
		statusEl.textContent = message || statusEl.dataset.statusError;
		closeBtn.hidden = false;
		if (submitBtn) {
			submitBtn.disabled = false;
		}
	}

	function validateClientFile(file) {
		var allowed = (fileInput.dataset.allowedExtensions || '').split(',').filter(Boolean);
		var maxSize = parseInt(fileInput.dataset.maxSize || '0', 10);
		var extension = file.name.split('.').pop().toLowerCase();

		if (allowed.indexOf(extension) === -1) {
			return 'Povolené sú iba videá typu: ' + allowed.join(', ') + '.';
		}
		if (maxSize > 0 && file.size > maxSize) {
			return 'Video je väčšie ako povolený limit.';
		}

		return '';
	}

	function parseJson(text) {
		try {
			return JSON.parse(text);
		} catch (e) {
			return null;
		}
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		setError('');

		if (!fileInput || fileInput.files.length === 0) {
			setError('Vyberte video.');
			return;
		}

		var clientError = validateClientFile(fileInput.files[0]);
		if (clientError) {
			setError(clientError);
			fileInput.value = '';
			return;
		}

		resetModal();
		modal.show();
		if (submitBtn) {
			submitBtn.disabled = true;
		}

		var xhr = new XMLHttpRequest();
		xhr.open((form.method || 'POST').toUpperCase(), form.action, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('Accept', 'application/json');

		xhr.upload.addEventListener('progress', function (event) {
			if (event.lengthComputable) {
				setProgress((event.loaded / event.total) * 100);
			}
		});

		xhr.addEventListener('load', function () {
			var data = parseJson(xhr.responseText);
			if (xhr.status >= 200 && xhr.status < 300 && data && data.success) {
				finishSuccess();
				return;
			}

			finishError(data && data.message ? data.message : null);
		});

		xhr.addEventListener('error', function () {
			finishError(null);
		});

		xhr.send(new FormData(form));
	});
})();
