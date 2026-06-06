const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

class FakeClassList {
	constructor(classes = []) {
		this.classes = new Set(classes);
	}

	add(...classes) {
		classes.forEach((className) => this.classes.add(className));
	}

	remove(...classes) {
		classes.forEach((className) => this.classes.delete(className));
	}

	contains(className) {
		return this.classes.has(className);
	}
}

class FakeElement {
	constructor(tagName, options = {}) {
		this.tagName = tagName.toUpperCase();
		this.type = options.type || '';
		this.name = options.name || '';
		this.value = options.value || '';
		this.checked = Boolean(options.checked);
		this.dataset = options.dataset || {};
		this.style = {};
		this.classList = new FakeClassList(options.classes || []);
		this._controls = options.controls || [];
		this._parentForm = options.parentForm || null;
		this.action = options.action || 'https://example.test/save';
		this.method = options.method || 'POST';
	}

	matches(selector) {
		return selector.split(',').some((rawSelector) => {
			const item = rawSelector.trim();
			if (item === '.js-autosave-control') {
				return this.classList.contains('js-autosave-control');
			}
			if (item === 'input' || item === 'select' || item === 'textarea') {
				return this.tagName.toLowerCase() === item;
			}
			if (item === 'input[type="text"]') {
				return this.tagName === 'INPUT' && this.type === 'text';
			}
			if (item === 'input[type="date"]') {
				return this.tagName === 'INPUT' && this.type === 'date';
			}
			if (item === 'textarea') {
				return this.tagName === 'TEXTAREA';
			}

			return false;
		});
	}

	closest(selector) {
		if (selector === '.js-autosave-form') {
			return this._parentForm;
		}
		if (selector === '.js-autosave-control' && this.classList.contains('js-autosave-control')) {
			return this;
		}

		return null;
	}

	querySelectorAll(selector) {
		if (selector === 'input[type="hidden"]') {
			return this._controls.filter((control) => control.tagName === 'INPUT' && control.type === 'hidden');
		}

		return [];
	}
}

class FakeFormData {
	constructor(form = null) {
		this.values = new Map();
		if (form && Array.isArray(form._controls)) {
			form._controls.forEach((control) => {
				if (control.name) {
					this.append(control.name, control.value);
				}
			});
		}
	}

	append(name, value) {
		const values = this.values.get(name) || [];
		values.push(String(value));
		this.values.set(name, values);
	}

	set(name, value) {
		this.values.set(name, [String(value)]);
	}

	get(name) {
		const values = this.values.get(name) || [];

		return values.length > 0 ? values[0] : null;
	}

	getAll(name) {
		return this.values.get(name) || [];
	}
}

function loadAutosaveForm(fetchImpl) {
	const listeners = {};
	const context = {
		console,
		HTMLElement: FakeElement,
		FormData: FakeFormData,
		fetch: fetchImpl,
		document: {
			addEventListener: (type, callback) => {
				listeners[type] = callback;
			},
		},
		window: {
			setTimeout: (callback) => callback(),
			location: {
				reload: () => {},
			},
		},
	};
	context.globalThis = context;

	const source = fs.readFileSync(path.join(__dirname, '../../www/js/autosaveForm.js'), 'utf8');
	vm.runInNewContext(source, context, { filename: 'autosaveForm.js' });

	return listeners;
}

function createForm(options = {}) {
	const form = new FakeElement('form', {
		action: 'https://example.test/admin/update/42',
		classes: options.classes || ['js-autosave-form'],
		dataset: options.dataset || {},
		method: 'POST',
	});
	const id = new FakeElement('input', { type: 'hidden', name: 'id', value: options.id || '42' });
	const token = new FakeElement('input', { type: 'hidden', name: '_token_', value: 'csrf-token' });
	form._controls = [id, token];

	return form;
}

async function dispatchChange(listener, control) {
	listener({ target: control });
	await new Promise((resolve) => setImmediate(resolve));
}

async function testPartialTextPayload() {
	let request = null;
	const listeners = loadAutosaveForm((url, options) => {
		request = { url, options };
		return Promise.resolve({
			ok: true,
			headers: { get: () => 'application/json' },
			json: () => Promise.resolve({ success: true }),
		});
	});
	const form = createForm({ dataset: { autosaveContext: 'babysitter.main' } });
	const control = new FakeElement('textarea', {
		name: 'notice',
		value: 'New notice',
		classes: ['js-autosave-control'],
		parentForm: form,
	});
	form._controls.push(control);

	await dispatchChange(listeners.change, control);

	assert.equal(request.url, 'https://example.test/admin/update/42');
	assert.equal(request.options.headers['X-Requested-With'], 'XMLHttpRequest');
	assert.equal(request.options.body.get('_do'), 'autosavePartial');
	assert.equal(request.options.body.get('__autosave_context'), 'babysitter.main');
	assert.equal(request.options.body.get('__autosave_field'), 'notice');
	assert.equal(request.options.body.get('__autosave_value'), 'New notice');
	assert.equal(request.options.body.get('id'), '42');
	assert.equal(request.options.body.get('notice'), 'New notice');
	assert.equal(form.dataset.autosaveSaving, '0');
	assert.equal(form.classList.contains('is-autosave-saved'), true);
}

async function testPartialCheckboxArrayPayload() {
	let request = null;
	const listeners = loadAutosaveForm((url, options) => {
		request = { url, options };
		return Promise.resolve({
			ok: true,
			headers: { get: () => 'application/json' },
			json: () => Promise.resolve({ success: true }),
		});
	});
	const form = createForm({ classes: ['js-autosave-form', 'babysitter-profile-form'] });
	const control = new FakeElement('input', {
		type: 'checkbox',
		name: 'diseaseIds[]',
		value: '7',
		checked: true,
		classes: ['js-autosave-control'],
		parentForm: form,
	});
	form._controls.push(control);

	await dispatchChange(listeners.change, control);

	assert.equal(request.options.body.get('__autosave_context'), 'babysitter.profile');
	assert.equal(request.options.body.get('__autosave_field'), 'diseaseIds');
	assert.equal(request.options.body.get('__autosave_value'), '7');
	assert.equal(request.options.body.get('__autosave_checked'), '1');
	assert.equal(request.options.body.get('__autosave_item_id'), '7');
	assert.deepEqual(request.options.body.getAll('diseaseIds[]'), ['7']);
}

async function testLegacyFallbackPayload() {
	let request = null;
	const listeners = loadAutosaveForm((url, options) => {
		request = { url, options };
		return Promise.resolve({
			ok: true,
			headers: { get: () => 'text/html' },
			json: () => Promise.reject(new Error('Legacy request must not parse JSON.')),
		});
	});
	const form = createForm({ classes: ['js-autosave-form'], dataset: {}, id: '77' });
	const control = new FakeElement('input', {
		type: 'text',
		name: 'notice',
		value: 'Legacy notice',
		classes: ['js-autosave-control'],
		parentForm: form,
	});
	form._controls.push(control);

	await dispatchChange(listeners.change, control);

	assert.equal(request.options.body.get('id'), '77');
	assert.equal(request.options.body.get('notice'), 'Legacy notice');
	assert.equal(request.options.body.get('__autosave_context'), null);
	assert.equal(form.classList.contains('is-autosave-saved'), true);
}

(async () => {
	await testPartialTextPayload();
	await testPartialCheckboxArrayPayload();
	await testLegacyFallbackPayload();
})();
