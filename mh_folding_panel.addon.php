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
				);
			}
		}
	}

	// mid별 내용 및 panel_on 설정
	if (isset($addon_info->mid_1no) && isset($addon_info->mid_1contents)) {
		$mid_contents[$addon_info->mid_1no] = array(
			'content' => $addon_info->mid_1contents,
			'panel_on' => $addon_info->mid_1panel_on ?? '',
		);
	}
	if (isset($addon_info->mid_2no) && isset($addon_info->mid_2contents)) {
		$mid_contents[$addon_info->mid_2no] = array(
			'content' => $addon_info->mid_2contents,
			'panel_on' => $addon_info->mid_2panel_on ?? '',
		);
	}
	if (isset($addon_info->mid_3no) && isset($addon_info->mid_3contents)) {
		$mid_contents[$addon_info->mid_3no] = array(
			'content' => $addon_info->mid_3contents,
			'panel_on' => $addon_info->mid_3panel_on ?? '',
		);
	}

	// 현재 mid에 해당하는 내용이 있으면 사용, 없으면 기본 내용 사용
	if (isset($mid_contents[$current_mid])) {
		$vars->content = $mid_contents[$current_mid]['content'];
		$vars->panel_on = $mid_contents[$current_mid]['panel_on'];
	} else {
		$vars->content = $addon_info->contact;
		$vars->panel_on = $addon_info->panel_on ?? ''; // 기본값 사용
	}

	// 나머지 설정 (기본값 사용)
	$vars->toggle1_text = $addon_info->toggle1_text ?: '더보기';
	$vars->toggle2_text = $addon_info->toggle2_text ?: '접기';
	$vars->toggle_form = $addon_info->toggle_form ?: 'H';
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
		'/<div[^>]*class="[^"]*mhfold[^"]*"[^>]*>/i',
		'/<form[^>]*class="[^"]*mhfold[^"]*"[^>]*>/i',
	);

	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $output)) {
			$output = preg_replace($pattern, '$0' . $panel_html, $output, 1);
			break;
		}
	}