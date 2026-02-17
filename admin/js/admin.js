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
				$btn.text('Copied!');
				setTimeout(function () { $btn.text('Copy'); }, 2000);
				return;
			}

			navigator.clipboard.writeText(text).then(function () {
				var original = $btn.text();
				$btn.text('Copied!');
				setTimeout(function () { $btn.text(original); }, 2000);
			});
		});

		// ── Select2 post search (Templates tab) ───────────────────────────

		function getSelectedPostType() {
			return $('#wpmcp_template_post_type').val() || 'post';
		}

		if ($.fn.select2) {
			$('#wpmcp_template_reference_post').select2({
				placeholder: 'Search for a post…',
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

			if (!window.confirm('Delete the template for "' + postType + '"? This cannot be undone.')) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true).text('Deleting…');

			$.post(wmcpAdmin.ajaxUrl, {
				action: 'wpmcp_delete_template',
				nonce: wmcpAdmin.nonce,
				post_type: postType,
			}).done(function (response) {
				if (response.success) {
					$btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
				} else {
					alert('Error deleting template.');
					$btn.prop('disabled', false).text('Delete');
				}
			}).fail(function () {
				alert('Request failed. Please try again.');
				$btn.prop('disabled', false).text('Delete');
			});
		});

		// ── Save license key (Add-ons tab) ────────────────────────────────

		$(document).on('click', '.wpmcp-save-license', function () {
			var $wrapper = $(this).closest('.wpmcp-license-input');
			var addonSlug = $wrapper.data('addon');
			var licenseKey = $wrapper.find('.wpmcp-license-key').val().trim();
			var $btn = $(this);

			$btn.prop('disabled', true).text('Saving…');

			$.post(wmcpAdmin.ajaxUrl, {
				action: 'wpmcp_save_license',
				nonce: wmcpAdmin.nonce,
				addon_slug: addonSlug,
				license_key: licenseKey,
			}).done(function (response) {
				if (response.success) {
					$btn.text(licenseKey ? 'Update' : 'Activate');
					// Flash success state
					$btn.addClass('wpmcp-btn-success');
					setTimeout(function () { $btn.removeClass('wpmcp-btn-success'); }, 2000);
				} else {
					alert('Error saving license.');
					$btn.text(licenseKey ? 'Update' : 'Activate');
				}
			}).fail(function () {
				alert('Request failed. Please try again.');
				$btn.text(licenseKey ? 'Update' : 'Activate');
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
						$statusBadge.removeClass('wpmcp-badge-inactive').addClass('wpmcp-badge-active').text('Enabled');
					} else {
						$statusBadge.removeClass('wpmcp-badge-active').addClass('wpmcp-badge-inactive').text('Disabled');
					}
				} else {
					// Revert on failure.
					$checkbox.prop('checked', !enabled);
					alert('Error toggling add-on.');
				}
			}).fail(function () {
				$checkbox.prop('checked', !enabled);
				alert('Request failed. Please try again.');
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
