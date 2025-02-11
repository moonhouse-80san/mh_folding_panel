// 판넬 접고 펴기 설정
document.addEventListener("DOMContentLoaded", function() {
	const panels = document.querySelectorAll('.mh-folding-panel');
	panels.forEach(panel => {
		const panelOn = panel.getAttribute('data-panel-on');
		if (panelOn === 'Y') {
			panel.setAttribute('open', true);
		}
	});
});