(function () {
	var btn = document.getElementById('pdfGenerateBtn');
	var modalEl = document.getElementById('pdfLoaderModal');
	if (!btn || !modalEl || !window.bootstrap) {
		return;
	}

	var CIRCUMFERENCE = 2 * Math.PI * 52;
	var EXPECTED_MS = 3500;
	var FILL_DURATION = 280;
	var SUCCESS_HOLD = 800;

	var ringEl = modalEl.querySelector('.pdf-loader-ring');
	var progressEl = modalEl.querySelector('.pdf-loader-progress');
	var percentEl = modalEl.querySelector('.pdf-loader-percent');
	var statusEl = modalEl.querySelector('.pdf-loader-status');
	var closeBtn = modalEl.querySelector('.pdf-loader-close');
	var modal = new bootstrap.Modal(modalEl);

	progressEl.style.strokeDasharray = CIRCUMFERENCE;
	progressEl.style.strokeDashoffset = CIRCUMFERENCE;

	var rafId = null;
	var startTs = 0;
	var lastShown = 0;
	var finalising = false;

	function setProgress(percent) {
		var clamped = Math.max(0, Math.min(100, percent));
		progressEl.style.strokeDashoffset = CIRCUMFERENCE * (1 - clamped / 100);
		percentEl.textContent = Math.round(clamped) + '%';
		lastShown = clamped;
	}

	function tick(ts) {
		if (finalising) {
			return;
		}
		if (!startTs) {
			startTs = ts;
		}
		var elapsed = ts - startTs;
		var target = 92 * (1 - Math.exp(-elapsed / EXPECTED_MS));
		setProgress(Math.max(target, lastShown));
		rafId = window.requestAnimationFrame(tick);
	}

	function reset() {
		finalising = false;
		startTs = 0;
		lastShown = 0;
		ringEl.dataset.state = 'loading';
		statusEl.textContent = statusEl.dataset.statusLoading;
		closeBtn.hidden = true;
		setProgress(0);
	}

	function finishTo(percent, callback) {
		finalising = true;
		if (rafId) {
			window.cancelAnimationFrame(rafId);
			rafId = null;
		}
		var from = lastShown;
		var to = percent;
		var startTime = performance.now();
		function step(now) {
			var t = Math.min(1, (now - startTime) / FILL_DURATION);
			setProgress(from + (to - from) * t);
			if (t < 1) {
				window.requestAnimationFrame(step);
			} else if (callback) {
				callback();
			}
		}
		window.requestAnimationFrame(step);
	}

	function showSuccess() {
		finishTo(100, function () {
			ringEl.dataset.state = 'success';
			statusEl.textContent = statusEl.dataset.statusSuccess;
			window.setTimeout(function () {
				window.location.reload();
			}, SUCCESS_HOLD);
		});
	}

	function showError(message) {
		finalising = true;
		if (rafId) {
			window.cancelAnimationFrame(rafId);
			rafId = null;
		}
		ringEl.dataset.state = 'error';
		statusEl.textContent = message || statusEl.dataset.statusError;
		closeBtn.hidden = false;
	}

	btn.addEventListener('click', function (event) {
		event.preventDefault();
		reset();
		modal.show();
		rafId = window.requestAnimationFrame(tick);

		fetch(btn.href, {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			},
			credentials: 'same-origin'
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}
			return response.json();
		}).then(function (data) {
			if (data && data.success) {
				showSuccess();
			} else {
				showError(data && data.message ? data.message : null);
			}
		}).catch(function () {
			showError(null);
		});
	});
})();
