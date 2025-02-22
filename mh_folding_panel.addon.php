<?php
	/**
	 * @file mh_folding_panel.addon.php
	 * @brief 게시판 상단 접이식 패널 애드온
	 * @author 80san <80san@moonhouse.co.kr>
	 * @license GPL-3.0-or-later
	 * @link https://moonhouse.co.kr/xemy/597524
	 **/

	if (!defined('RX_VERSION')) {
		exit;
	}

	// HTML 응답이 아니거나 관리자 페이지면 종료
	if (Context::getResponseMethod() !== 'HTML' || Context::get('module') === 'admin') {
		return;
	}

	// before_display_content 위치가 아니면 종료
	if ($called_position !== 'before_display_content') {
		return;
	}

	// 현재 mid 가져오기
	$current_mid = Context::get('mid');

	// 애드온 설정 정리
	$addon_info = (object)$addon_info;

	// 스킨 경로 설정
	$skin_path = './addons/mh_folding_panel/skins/';
	$skin_name = $addon_info->skin ?? 'default';

	// 스킨 존재 여부 확인 및 기본값 설정
	if (!is_dir($skin_path . $skin_name) || !file_exists($skin_path . $skin_name . '/index.html')) {
		$skin_name = 'default';
	}
	$skin_path .= $skin_name;

	// CSS, JS 파일 로드
	Context::addCSSFile($skin_path . '/css/style.css');
	Context::addJsFile($skin_path . '/css/folding.js');

	// 변수 설정을 위한 객체 생성
	$vars = new stdClass();

	// 게시판별 판넬 mid별 내용 설정
	$mid_contents = array();
	if (isset($addon_info->mid_contents) && is_string($addon_info->mid_contents)) {
		$lines = explode("\n", trim($addon_info->mid_contents));
		foreach ($lines as $line) {
			$parts = explode('|', trim($line));
			if (count($parts) >= 2) {
				$mid = trim($parts[0]);
				$content = trim($parts[1]);
				$mid_contents[$mid] = array(
					'content' => $content,
					'panel_on' => $addon_info->panel_on ?? '', // 기본값 사용
					'width_full' => $addon_info->width_full ?? '', // 기본값 사용
				);
			}
		}
	}

	// mid별 콘텐츠와 패널 상태 확인
	$content = $addon_info->contact;
	$panel_on = $addon_info->panel_on;
	$width_full = $addon_info->width_full;

	// 게시판별 설정된 내용이 있는지 확인
	if (isset($mid_contents[$current_mid])) {
		$content = $mid_contents[$current_mid]['content'];
		$panel_on = $mid_contents[$current_mid]['panel_on'];
		$width_full = $mid_contents[$current_mid]['width_full'];
	}

	for ($i = 1; $i <= 3; $i++) {
		$mid_no = "mid_{$i}no";
		$mid_contents = "mid_{$i}contents";
		$mid_panel_on = "mid_{$i}panel_on";
		$mid_width_full = "mid_{$i}width_full";
		
		if (isset($addon_info->$mid_no) && $addon_info->$mid_no === $current_mid) {
			if (isset($addon_info->$mid_contents)) {
				$content = $addon_info->$mid_contents;
			}
			if (isset($addon_info->$mid_panel_on) && $addon_info->$mid_panel_on !== '') {
				$panel_on = $addon_info->$mid_panel_on;
			}
			if (isset($addon_info->$mid_width_full) && $addon_info->$mid_width_full !== '') {
				$width_full = $addon_info->$mid_width_full;
			}
			break;
		}
	}

	// 변수 설정
	$vars->content = $content;
	$vars->toggle1_text = $addon_info->toggle1_text ?: '더보기';
	$vars->toggle2_text = $addon_info->toggle2_text ?: '접기';
	$vars->toggle_form = $addon_info->toggle_form ?: 'H';
	$vars->width_full = $width_full;
	$vars->panel_on = $panel_on;
	$vars->top_panel_bcolor = $addon_info->top_panel_bcolor ?: 'transparent';
	$vars->top_panel_color = $addon_info->top_panel_color ?: '#444';
	$vars->top_panel_size = $addon_info->top_panel_size ?: '14px';

	// Context에 변수 설정
	Context::set('mh_folding_panel', $vars);

	// 템플릿 파일 로드
	$oTemplate = &TemplateHandler::getInstance();
	$panel_html = $oTemplate->compile($skin_path, 'index.html');

	// 본문 시작 부분에 삽입 (여러 패턴 시도)
	$patterns = array(
		'/<div[^>]*class="[^"]*board[^"]*"[^>]*>/i',
		'/<div[^>]*id="[^"]*board[^"]*"[^>]*>/i',
		'/<form[^>]*class="[^"]*mhfold[^"]*"[^>]*>/i',
	);

	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $output)) {
			$output = preg_replace($pattern, '$0' . $panel_html, $output, 1);
			break;
		}
	}