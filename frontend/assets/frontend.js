/* global jQuery, sppaFront */
(function ($) {
	'use strict';

	function collectValues($wrap) {
		var values = {};
		$wrap.find('.sppa-field').each(function () {
			var $f = $(this);
			if ($f.prop('hidden') || $f.hasClass('sppa-field-hidden') || $f.closest('.sppa-group').prop('hidden')) {
				return;
			}
			var id = $f.data('field-id');
			var type = $f.data('type');
			var val = null;

			if (type === 'checkbox_group' || type === 'image_checkbox' || type === 'multiselect') {
				val = [];
				$f.find('input:checked, select option:selected').each(function () {
					var v = $(this).val();
					if (v) {
						val.push(v);
					}
				});
			} else if (type === 'radio' || type === 'image_radio' || type === 'color_swatch') {
				val = $f.find('input:checked').val() || '';
			} else if (type === 'checkbox') {
				val = $f.find('input[type="checkbox"]').is(':checked') ? '1' : '';
			} else if (type === 'file') {
				val = $f.find('input[type="file"]').val() ? '1' : '';
			} else {
				val = $f.find('.sppa-input, input, select, textarea').first().val() || '';
			}
			values[id] = val;
		});
		return values;
	}

	function ruleMatches(rule, values) {
		var actual = values[rule.field];
		var expected = rule.value || '';
		var op = rule.operator || 'is';

		if (Array.isArray(actual)) {
			if (op === 'is' || op === 'equals') {
				return actual.map(String).indexOf(String(expected)) !== -1;
			}
			if (op === 'is_not' || op === 'not_equals') {
				return actual.map(String).indexOf(String(expected)) === -1;
			}
			if (op === 'is_empty') {
				return !actual.length;
			}
			if (op === 'is_not_empty') {
				return !!actual.length;
			}
			return actual.map(String).indexOf(String(expected)) !== -1;
		}

		actual = actual == null ? '' : String(actual);

		switch (op) {
			case 'is':
			case 'equals':
				return actual === String(expected);
			case 'is_not':
			case 'not_equals':
				return actual !== String(expected);
			case 'contains':
				return expected !== '' && actual.toLowerCase().indexOf(String(expected).toLowerCase()) !== -1;
			case 'not_contains':
				return expected === '' || actual.toLowerCase().indexOf(String(expected).toLowerCase()) === -1;
			case 'greater_than':
				return parseFloat(actual) > parseFloat(expected);
			case 'less_than':
				return parseFloat(actual) < parseFloat(expected);
			case 'greater_or_equal':
				return parseFloat(actual) >= parseFloat(expected);
			case 'less_or_equal':
				return parseFloat(actual) <= parseFloat(expected);
			case 'is_empty':
				return actual === '';
			case 'is_not_empty':
				return actual !== '';
			default:
				return actual === String(expected);
		}
	}

	function applyConditions($wrap) {
		var values = collectValues($wrap);
		$wrap.find('.sppa-field').each(function () {
			var $f = $(this);
			var cond = $f.data('conditions') || {};
			if (!cond.enabled || !cond.rules || !cond.rules.length) {
				if (!$f.hasClass('sppa-field-hidden')) {
					$f.prop('hidden', false);
				}
				$f.find(':input').prop('disabled', false);
				return;
			}
			var results = cond.rules.map(function (rule) {
				return ruleMatches(rule, values);
			});
			var ok = cond.match === 'any' ? results.indexOf(true) !== -1 : results.indexOf(false) === -1;
			var visible = cond.action === 'hide' ? !ok : ok;
			$f.prop('hidden', !visible);
			$f.find(':input').prop('disabled', !visible);
		});
	}

	function applyVariations($wrap) {
		var $varInput = $('form.cart input[name="variation_id"]');
		if (!$varInput.length) {
			return;
		}
		var vid = String($varInput.val() || '');
		$wrap.find('.sppa-group').each(function () {
			var raw = String($(this).attr('data-variations') || '');
			if (!raw) {
				$(this).prop('hidden', false);
				$(this).find(':input').prop('disabled', false);
				return;
			}
			var show = vid && raw.split(',').indexOf(vid) !== -1;
			$(this).prop('hidden', !show);
			$(this).find(':input').prop('disabled', !show);
		});
	}

	function paintProductPrice(html, hasAddon) {
		var $price = $('.woocommerce-variation-price .price');
		if (!$price.length) {
			$price = $('.summary p.price, .product p.price').first();
		}
		if (!$price.length || !html) {
			return;
		}
		if (!$price.data('sppaOriginal')) {
			$price.data('sppaOriginal', $price.html());
		}
		$price.html(hasAddon ? html : $price.data('sppaOriginal'));
	}

	function livePrice($wrap) {
		if (!sppaFront.settings || sppaFront.settings.live_price !== 'yes') {
			return;
		}
		var productId = $wrap.data('product-id') || sppaFront.productId;
		var variationId = $('input[name="variation_id"]').val() || 0;
		var qty = $('form.cart input.qty').val() || 1;
		var values = collectValues($wrap);

		$.post(sppaFront.ajax, {
			action: 'sppa_live_price',
			nonce: sppaFront.nonce,
			product_id: productId,
			variation_id: variationId,
			quantity: qty,
			sppa: values,
		}).done(function (res) {
			if (!res || !res.success) {
				return;
			}
			var data = res.data;
			$('.sppa-live-price').remove();

			var $summary = $wrap.find('.sppa-summary');
			if ($summary.length) {
				var $lines = $summary.find('.sppa-summary-lines').empty();
				var lineQty = data.qty || 1;
				var name = data.product_name || '';
				$lines.append(
					$('<p class="sppa-sum-row"/>')
						.append($('<span/>').text(lineQty + 'x ' + name))
						.append($('<span/>').html(data.line_html || data.base_html || ''))
				);
				(data.lines || []).forEach(function (line) {
					var label = (line.label ? line.label + ' - ' : '') + (line.value || '');
					var amt = line.hide_price ? '' : (line.amount_html || '');
					$lines.append(
						$('<p class="sppa-sum-row"/>')
							.append($('<span/>').text(label))
							.append($('<span/>').html(amt))
					);
				});
				$summary.find('.sppa-grand-total').html(data.total_html);
				$summary.prop('hidden', false);
			}
			paintProductPrice(data.display_html, (parseFloat(data.addons) || 0) !== 0);
		});
	}

	function markSelected($wrap) {
		$wrap.find('.sppa-choice').each(function () {
			var $c = $(this);
			$c.toggleClass('is-selected', $c.find('input').is(':checked'));
		});
	}

	function refresh($wrap) {
		applyVariations($wrap);
		applyConditions($wrap);
		markSelected($wrap);
		livePrice($wrap);
	}

	$(document).on('change input', '.sppa-wrap :input', function () {
		refresh($(this).closest('.sppa-wrap'));
	});

	$(document).on('change', 'form.cart input.qty, form.cart input[name="variation_id"]', function () {
		$('.sppa-wrap').each(function () {
			refresh($(this));
		});
	});

	$(document).on('found_variation reset_data', function () {
		$('.sppa-wrap').each(function () {
			refresh($(this));
		});
	});

	$(document).on('change', '.sppa-file', function () {
		var $preview = $(this).closest('.sppa-field').find('.sppa-file-preview').empty();
		var files = this.files || [];
		for (var i = 0; i < files.length; i++) {
			$preview.append($('<div class="sppa-file-name"/>').text(files[i].name));
		}
	});

	$(document).on('change', 'input[type="date"].sppa-input', function () {
		var blocked = String($(this).attr('data-blocked') || '').split(/[\s,]+/);
		if (this.value && blocked.indexOf(this.value) !== -1) {
			this.setCustomValidity('That date is not available.');
			this.reportValidity();
			this.value = '';
		} else {
			this.setCustomValidity('');
		}
	});

	$(function () {
		$('.sppa-wrap').each(function () {
			refresh($(this));
		});
	});
})(jQuery);
