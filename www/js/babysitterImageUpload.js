document.addEventListener('DOMContentLoaded', () => {
	const wrap = document.querySelector('.js-babysitter-image-form-wrap');
	if (!wrap) return;

	const fileInput = wrap.querySelector('.js-babysitter-image-input');
	const submitBtn = wrap.querySelector('.js-babysitter-image-upload');
	if (!fileInput || !submitBtn) return;

	submitBtn.addEventListener('click', (e) => {
		if (fileInput.files.length === 0) {
			e.preventDefault();
			fileInput.click();
		}
	});

	fileInput.addEventListener('change', () => {
		if (fileInput.files.length > 0) {
			submitBtn.click();
		}
	});
});
