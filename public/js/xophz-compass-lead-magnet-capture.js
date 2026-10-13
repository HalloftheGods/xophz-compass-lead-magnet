/**
 * Lead Magnet capture form: posts [lead_magnet_capture] forms to lead-magnet/v1/capture and reports
 * the result in place. Sends the page URL so the server can read attribution (utm_*, fm_thread,
 * fm_reply) from the landing URL. No dependencies.
 */
(function () {
	'use strict';

	var FORM_SELECTOR = 'form[data-lead-magnet-capture]';

	function clearErrors(form) {
		form.querySelectorAll('[aria-invalid="true"]').forEach(function (el) {
			el.removeAttribute('aria-invalid');
		});
		form.querySelectorAll('[data-lm-error]').forEach(function (el) {
			el.textContent = '';
		});
	}

	function showErrors(form, errors) {
		var first = null;
		Object.keys(errors).forEach(function (name) {
			var slot = form.querySelector('[data-lm-error="' + name + '"]');
			var input = form.elements.namedItem(name);
			var canMark = Boolean(input) && typeof input.setAttribute === 'function';
			if (slot) {
				slot.textContent = String(errors[name]);
			}
			if (canMark) {
				input.setAttribute('aria-invalid', 'true');
				first = first || input;
			}
		});
		var canFocus = Boolean(first) && typeof first.focus === 'function';
		if (canFocus) {
			first.focus();
		}
	}

	function payload(form) {
		var data = {};
		new FormData(form).forEach(function (value, key) {
			data[key] = value;
		});
		var consent = form.elements.namedItem('consent');
		data.consent = consent && consent.checked ? '1' : '0';
		data.page_url = window.location.href;
		return data;
	}

	function readJson(response) {
		return response.json().catch(function () {
			return {};
		}).then(function (body) {
			return { status: response.status, body: body || {} };
		});
	}

	function report(form, result) {
		var body = result.body;
		var status = form.querySelector('[data-lm-status]');
		var succeeded = result.status >= 200 && result.status < 300 && body.ok === true;
		var hasFieldErrors = Boolean(body.errors) && typeof body.errors === 'object';
		if (succeeded) {
			form.reset();
			form.querySelector('[data-lm-fields]').hidden = true;
			status.textContent = body.message || 'Thank you.';
			return;
		}
		if (hasFieldErrors) {
			showErrors(form, body.errors);
		}
		status.textContent = body.message || 'Something went wrong. Please try again.';
	}

	function send(form) {
		var status = form.querySelector('[data-lm-status]');
		var button = form.querySelector('[type="submit"]');
		var headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
		var restNonce = form.getAttribute('data-rest-nonce');
		if (restNonce) {
			headers['X-WP-Nonce'] = restNonce;
		}

		clearErrors(form);
		status.textContent = 'Sending...';
		button.disabled = true;

		fetch(form.getAttribute('data-endpoint'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: headers,
			body: JSON.stringify(payload(form))
		})
			.then(readJson)
			.then(function (result) {
				report(form, result);
			})
			.catch(function () {
				status.textContent = 'Could not reach the server. Check your connection and try again.';
			})
			.then(function () {
				button.disabled = false;
			});
	}

	/**
	 * One delegated submit listener. It runs once and re-arms itself on every submit, so there is
	 * never more than one listener on the document and nothing to tear down.
	 */
	function listen() {
		document.addEventListener('submit', onSubmit, { once: true });
	}

	function onSubmit(event) {
		listen();
		var form = event.target;
		var isCaptureForm = Boolean(form) && typeof form.matches === 'function' && form.matches(FORM_SELECTOR);
		if (!isCaptureForm) {
			return;
		}
		event.preventDefault();
		send(form);
	}

	listen();
})();
