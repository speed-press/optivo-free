/* global jQuery, sppaAdmin, wp */
(function ($) {
	'use strict';

	function uid(prefix) {
		return prefix + '_' + Math.random().toString(36).slice(2, 10);
	}

	function nextIndex($list) {
		return Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
	}

	function template(id, map) {
		var html = document.getElementById(id).innerHTML;
		Object.keys(map).forEach(function (k) {
			html = html.split(k).join(map[k]);
		});
		return html;
	}

	function optionTypes() {
		return [ 'select', 'radio' ];
	}

	function bindSortable($root) {
		$root.find('.sppa-groups').sortable({
			handle: '.sppa-group-header > .sppa-handle',
			items: '> .sppa-group',
			placeholder: 'sppa-sort-placeholder',
		});
		$root.find('.sppa-fields').sortable({
			handle: '.sppa-field-header > .sppa-handle',
			items: '> .sppa-field',
			connectWith: '.sppa-fields',
			placeholder: 'sppa-sort-placeholder',
		});
		$root.find('.sppa-options').sortable({
			handle: '.sppa-handle',
			items: '> .sppa-option',
		});
	}

	$(document).on('click', '.sppa-add-group', function (e) {
		e.preventDefault();
		var $builder = $(this).closest('.sppa-builder');
		var gi = nextIndex();
		var html = template('tmpl-sppa-group', {
			'__GI__': gi,
			'__GID__': uid('grp'),
		});
		$builder.find('.sppa-groups').append(html);
		bindSortable($builder);
	});

	$(document).on('click', '.sppa-duplicate-group', function (e) {
		e.preventDefault();
		var $group = $(this).closest('.sppa-group');
		var $clone = $group.clone();
		var gi = nextIndex();
		$clone.attr('data-index', gi);
		$clone.find('[name]').each(function () {
			var done = false;
			var name = ($(this).attr('name') || '').replace(/\[(\d+)\]/, function (match) {
				if (done) {
					return match;
				}
				done = true;
				return '[' + gi + ']';
			});
			$(this).attr('name', name);
		});
		$clone.find('.sppa-field').each(function () {
			var $field = $(this);
			var oldId = $field.find('.sppa-field-id').val();
			var newId = uid('fld');
			$field.attr('data-index', nextIndex());
			$field.find('.sppa-field-id').val(newId);
			$field.find('.sppa-field-id-display').text(newId);
			$clone.find('[name*="[conditions]"]').each(function () {
				if ($(this).val() === oldId) {
					$(this).val(newId);
				}
			});
		});
		$group.after($clone);
		bindSortable($group.closest('.sppa-builder'));
	});

	var pendingRemove = null;

	function modal() {
		return $('.sppa-builder .sppa-modal').first();
	}

	function openModal(title, text, $target) {
		pendingRemove = $target;
		var $m = modal();
		$m.find('.sppa-modal-title').text(title);
		$m.find('.sppa-modal-text').text(text);
		$m.prop('hidden', false);
	}

	function closeModal() {
		pendingRemove = null;
		modal().prop('hidden', true);
	}

	$(document).on('click', '.sppa-remove-group', function (e) {
		e.preventDefault();
		openModal(
			sppaAdmin.i18n.removeGroup || 'Remove this group?',
			sppaAdmin.i18n.removeGroupTx || '',
			$(this).closest('.sppa-group')
		);
	});

	$(document).on('click', '.sppa-add-field', function (e) {
		e.preventDefault();
		var $group = $(this).closest('.sppa-group');
		var gi = $group.attr('data-index');
		var fi = nextIndex();
		var html = template('tmpl-sppa-field', {
			'__GI__': gi,
			'__FI__': fi,
			'__FID__': uid('fld'),
		});
		$group.find('.sppa-fields').append(html);
		$group.find('.sppa-type-panel').attr('hidden', 'hidden').removeClass('is-open');
		syncFieldSettings($group.find('.sppa-field').last());
		bindSortable($group.closest('.sppa-builder'));
	});

	$(document).on('click', '.sppa-remove-field', function (e) {
		e.preventDefault();
		openModal(
			sppaAdmin.i18n.removeField || 'Remove this field?',
			sppaAdmin.i18n.removeFieldTx || '',
			$(this).closest('.sppa-field')
		);
	});

	$(document).on('click', '.sppa-modal-cancel, .sppa-modal-backdrop', function (e) {
		e.preventDefault();
		closeModal();
	});

	$(document).on('click', '.sppa-modal-ok', function (e) {
		e.preventDefault();
		if (pendingRemove && pendingRemove.length) {
			pendingRemove.remove();
		}
		closeModal();
	});

	$(document).on('click', '.sppa-tile', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $grid = $btn.closest('.sppa-tile-grid');
		var target = $grid.data('target');
		var value = $btn.data('value');
		$grid.find('.sppa-tile').removeClass('is-active');
		$btn.addClass('is-active');
		$btn.closest('.sppa-picker-block').find('input.sppa-' + target + '-input').val(value);
	});

	function closeTypePanels($except) {
		$('.sppa-type-panel').each(function () {
			if ($except && this === $except[0]) {
				return;
			}
			$(this).attr('hidden', 'hidden').removeClass('is-open');
		});
	}

	$(document).on('click', '.sppa-type-trigger', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $panel = $(this).siblings('.sppa-type-panel');
		var open = $panel.hasClass('is-open');
		closeTypePanels();
		if (!open) {
			$panel.removeAttr('hidden').addClass('is-open');
		}
	});

	$(document).on('click', '.sppa-type-card', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $card = $(this);
		var value = $card.data('value');
		var label = $.trim($card.text());
		var $picker = $card.closest('.sppa-type-picker');
		$picker.find('.sppa-type-card').removeClass('is-active');
		$card.addClass('is-active');
		$picker.find('.sppa-field-type').val(value).trigger('change');
		$picker.find('.sppa-type-trigger-label').text(label);
		closeTypePanels();
	});

	$(document).on('click', function () {
		closeTypePanels();
	});

	$(document).on('click', '.sppa-type-picker', function (e) {
		e.stopPropagation();
	});

	$(document).on('click', '.sppa-toggle-field', function (e) {
		e.preventDefault();
		var $settings = $(this).closest('.sppa-field').find('.sppa-field-settings').first();
		$settings.prop('hidden', !$settings.prop('hidden'));
	});

	function syncFieldSettings($field) {
		var type = $field.find('.sppa-field-type').val() || $field.attr('data-type') || 'text';
		$field.attr('data-type', type);
		$field.find('.sppa-set[data-for]').each(function () {
			var allowed = String($(this).attr('data-for') || '').split(',');
			$(this).toggle(allowed.indexOf(type) !== -1);
		});
		$field.find('.sppa-options-wrap').prop('hidden', optionTypes().indexOf(type) === -1);
		if (type === 'heading' || type === 'paragraph' || type === 'hidden') {
			$field.children('.sppa-field-header').find('.sppa-switch').hide();
		} else {
			$field.children('.sppa-field-header').find('.sppa-switch').show();
		}
	}

	$(document).on('change', '.sppa-field-type', function () {
		syncFieldSettings($(this).closest('.sppa-field'));
	});

	$(document).on('click', '.sppa-add-option', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.sppa-field');
		var $group = $field.closest('.sppa-group');
		var gi = $group.attr('data-index');
		var fi = $field.attr('data-index');
		var oi = nextIndex();
		var html = template('tmpl-sppa-option', {
			'__GI__': gi,
			'__FI__': fi,
			'__OI__': oi,
			'__OID__': uid('opt'),
		});
		$field.find('.sppa-options').append(html);
	});

	$(document).on('click', '.sppa-remove-option', function (e) {
		e.preventDefault();
		$(this).closest('.sppa-option').remove();
	});

	$(document).on('change', '.sppa-cond-enabled', function () {
		$(this).closest('.sppa-conditions-wrap').find('.sppa-cond-body').prop('hidden', !this.checked);
	});

	$(document).on('click', '.sppa-add-rule', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.sppa-field');
		var $group = $field.closest('.sppa-group');
		var gi = $group.attr('data-index');
		var fi = $field.attr('data-index');
		var ri = nextIndex();
		var html = template('tmpl-sppa-rule', {
			'__GI__': gi,
			'__FI__': fi,
			'__RI__': ri,
		});
		$field.find('.sppa-rules').append(html);
	});

	$(document).on('click', '.sppa-remove-rule', function (e) {
		e.preventDefault();
		$(this).closest('.sppa-rule').remove();
	});

	$(document).on('click', '.sppa-pick-image', function (e) {
		e.preventDefault();
		var $opt = $(this).closest('.sppa-option');
		var frame = wp.media({
			title: 'Select option image',
			button: { text: 'Use image' },
			multiple: false,
		});
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			$opt.find('.sppa-option-image').val(att.id);
			var $img = $opt.find('.sppa-opt-thumb');
			if (!$img.length) {
				$img = $('<img class="sppa-opt-thumb" alt="" />');
				$opt.find('.sppa-pick-image').after($img);
			}
			$img.attr('src', att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
		});
		frame.open();
	});

	$(document).on('submit', 'form', function () {
		var $builders = $(this).find('.sppa-builder');
		if (!$builders.length) {
			return;
		}
		$builders.each(function () {
			var name = $(this).attr('data-name') || 'sppa_groups';
			$(this).find('.sppa-groups > .sppa-group').each(function (gi) {
				$(this).attr('data-index', gi);
				$(this).find('[name^="' + name + '["]').each(function () {
					var n = $(this).attr('name');
					if (!n) {
						return;
					}
					$(this).attr('name', n.replace(new RegExp('^' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[[^\\]]+\\]'), name + '[' + gi + ']'));
				});
				$(this).find('.sppa-field').each(function (fi) {
					$(this).attr('data-index', fi);
					$(this).find('[name*="[fields]["]').each(function () {
						var n = $(this).attr('name');
						if (!n) {
							return;
						}
						$(this).attr('name', n.replace(/\[fields\]\[[^\]]+\]/, '[fields][' + fi + ']'));
					});
				});
			});
		});
	});

	$(function () {
		$(".sppa-builder").each(function () {
			bindSortable($(this));
			$(this).find(".sppa-field").each(function () {
				syncFieldSettings($(this));
			});
		});
	});
})(jQuery);
