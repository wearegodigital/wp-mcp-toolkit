/* global jQuery, wmcpAdmin */
(function ($) {
	'use strict';

	$(function () {

		// ── Copy buttons (Connection tab) ─────────────────────────────────

		$(document).on('click', '.wpmcp-copy-btn', function () {
			var targetId = $(this).data('target');
			var text = $('#' + targetId).text();
			var $btn = $(this);

			if (!navigator.clipboard) {
				// Fallback for older browsers
				var $tmp = $('<textarea>').val(text).appendTo('body').select();
				document.execCommand('copy');
				$tmp.remove();
				$btn.text(wmcpAdmin.strings.copied);
				setTimeout(function () { $btn.text(wmcpAdmin.strings.copy); }, 2000);
				return;
			}

			navigator.clipboard.writeText(text).then(function () {
				var original = $btn.text();
				$btn.text(wmcpAdmin.strings.copied);
				setTimeout(function () { $btn.text(original); }, 2000);
			});
		});

		// ── Select2 post search (Templates tab) ───────────────────────────

		function getSelectedPostType() {
			return $('#wpmcp_template_post_type').val() || 'post';
		}

		if ($.fn.select2) {
			$('#wpmcp_template_reference_post').select2({
				placeholder: wmcpAdmin.strings.searchPlaceholder,
				allowClear: true,
				minimumInputLength: 1,
				ajax: {
					url: wmcpAdmin.ajaxUrl,
					dataType: 'json',
					delay: 300,
					data: function (params) {
						return {
							action: 'wpmcp_search_posts',
							nonce: wmcpAdmin.nonce,
							q: params.term,
							post_type: getSelectedPostType(),
						};
					},
					processResults: function (response) {
						if (!response.success) {
							return { results: [] };
						}
						return {
							results: response.data.map(function (p) {
								return {
									id: p.id,
									text: p.title + ' (' + p.date + ')',
								};
							}),
						};
					},
				},
			});

			// Re-init Select2 when post type changes (clears previous selection).
			$('#wpmcp_template_post_type').on('change', function () {
				var $select = $('#wpmcp_template_reference_post');
				$select.val(null).trigger('change');
			});
		}

		// ── Delete template (Templates tab) ───────────────────────────────

		$(document).on('click', '.wpmcp-delete-template', function () {
			var postType = $(this).data('post-type');
			if (!postType) return;

			if (!window.confirm(wmcpAdmin.strings.confirmDeleteTpl.replace('%s', postType))) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true).text(wmcpAdmin.strings.deleting);

			$.post(wmcpAdmin.ajaxUrl, {
				action: 'wpmcp_delete_template',
				nonce: wmcpAdmin.nonce,
				post_type: postType,
			}).done(function (response) {
				if (response.success) {
					$btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
				} else {
					alert(wmcpAdmin.strings.errorDeleting);
					$btn.prop('disabled', false).text(wmcpAdmin.strings.delete);
				}
			}).fail(function () {
				alert(wmcpAdmin.strings.requestFailed);
				$btn.prop('disabled', false).text(wmcpAdmin.strings.delete);
			});
		});

		// ── Save license key (Add-ons tab) ────────────────────────────────

		$(document).on('click', '.wpmcp-save-license', function () {
			var $wrapper = $(this).closest('.wpmcp-license-input');
			var addonSlug = $wrapper.data('addon');
			var licenseKey = $wrapper.find('.wpmcp-license-key').val().trim();
			var $btn = $(this);

			$btn.prop('disabled', true).text(wmcpAdmin.strings.saving);

			$.post(wmcpAdmin.ajaxUrl, {
				action: 'wpmcp_save_license',
				nonce: wmcpAdmin.nonce,
				addon_slug: addonSlug,
				license_key: licenseKey,
			}).done(function (response) {
				if (response.success) {
					$btn.text(licenseKey ? wmcpAdmin.strings.update : wmcpAdmin.strings.activate);
					// Flash success state
					$btn.addClass('wpmcp-btn-success');
					setTimeout(function () { $btn.removeClass('wpmcp-btn-success'); }, 2000);
				} else {
					alert(wmcpAdmin.strings.errorSavingLicense);
					$btn.text(licenseKey ? wmcpAdmin.strings.update : wmcpAdmin.strings.activate);
				}
			}).fail(function () {
				alert(wmcpAdmin.strings.requestFailed);
				$btn.text(licenseKey ? wmcpAdmin.strings.update : wmcpAdmin.strings.activate);
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});

		// ── Toggle add-on enabled/disabled (Add-ons tab) ─────────────────

		$(document).on('change', '.wpmcp-addon-enabled', function () {
			var $checkbox = $(this);
			var addonSlug = $checkbox.data('addon');
			var enabled = $checkbox.is(':checked');
			var $card = $checkbox.closest('.wpmcp-addon-card');

			$.post(wmcpAdmin.ajaxUrl, {
				action: 'wpmcp_toggle_addon',
				nonce: wmcpAdmin.nonce,
				addon_slug: addonSlug,
				enabled: enabled ? '1' : '',
			}).done(function (response) {
				if (response.success) {
					// Update card appearance.
					$card.toggleClass('wpmcp-addon-disabled', !enabled);
					// Update status badge.
					var $statusBadge = $card.find('.wpmcp-addon-footer .wpmcp-badge').first();
					if (enabled) {
						$statusBadge.removeClass('wpmcp-badge-inactive').addClass('wpmcp-badge-active').text(wmcpAdmin.strings.enabled);
					} else {
						$statusBadge.removeClass('wpmcp-badge-active').addClass('wpmcp-badge-inactive').text(wmcpAdmin.strings.disabled);
					}
				} else {
					// Revert on failure.
					$checkbox.prop('checked', !enabled);
					alert(wmcpAdmin.strings.errorTogglingAddon);
				}
			}).fail(function () {
				$checkbox.prop('checked', !enabled);
				alert(wmcpAdmin.strings.requestFailed);
			});
		});

		// ── Ability group collapse/expand (Abilities tab) ─────────────────

		$(document).on('click', '.wpmcp-ability-group-header', function () {
			var $group = $(this).closest('.wpmcp-ability-group');
			var $body = $group.find('.wpmcp-ability-group-body');
			var $icon = $(this).find('.dashicons');

			$body.slideToggle(200);
			$icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-right-alt2');
		});

	});

}(jQuery));
