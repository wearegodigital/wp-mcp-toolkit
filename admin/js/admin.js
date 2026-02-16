(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var buttons = document.querySelectorAll('.wpmcp-copy-btn');

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var targetId = btn.getAttribute('data-target');
				var pre = document.getElementById(targetId);

				if (!pre) return;

				navigator.clipboard.writeText(pre.textContent).then(function () {
					var original = btn.textContent;
					btn.textContent = 'Copied!';
					setTimeout(function () {
						btn.textContent = original;
					}, 2000);
				});
			});
		});
	});
})();
