(() => {
	'use strict';

	const form = document.querySelector('.password-form');
	if (!form) return;

	const password = form.elements.namedItem('password');
	const repeat = form.elements.namedItem('passwordRepeat');
	const match = form.querySelector('.password-match');
	// Nette trims single-line controls before checking length, patterns and equality.
	const { trimCharacters } = JSON.parse(password.dataset.passwordOptions);
	const trimEdges = new RegExp('^[' + trimCharacters + ']+|[' + trimCharacters + ']+$', 'gu');
	const rules = Array.from(form.querySelectorAll('[data-validator]'), item => {
		const { validator, argument } = item.dataset;
		const pattern = validator === ':pattern' ? new RegExp('^(?:' + argument + ')$', 'su') : null;
		return {
			item,
			status: item.querySelector('[data-rule-status]'),
			satisfied: value => pattern ? pattern.test(value) : [...value].length >= Number(argument),
		};
	});

	function updateFeedback() {
		const value = password.value.replace(trimEdges, '');
		const confirmation = repeat.value.replace(trimEdges, '');
		for (const rule of rules) {
			const met = rule.satisfied(value);
			if (rule.item.classList.contains('is-met') !== met) {
				rule.item.classList.toggle('is-met', met);
				rule.status.textContent = met ? ' – splnené' : ' – nesplnené';
			}
		}

		const matches = confirmation !== '' && value === confirmation;
		const message = confirmation === '' ? '' : matches ? 'Heslá sa zhodujú.' : 'Heslá sa nezhodujú.';
		if (match.textContent !== message) {
			match.textContent = message;
			match.classList.toggle('is-met', matches);
		}
	}

	for (const field of [password, repeat]) {
		field.addEventListener('input', updateFeedback);
		field.addEventListener('change', updateFeedback);
	}
	window.addEventListener('pageshow', updateFeedback);
	updateFeedback();
})();
