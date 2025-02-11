<?php
	/**
	 * Mh Folding Panel Addon
	 * @author 80san <80san@moonhouse.co.kr>
	 * @license GPL-3.0-or-later
	 * @link https://moonhouse.co.kr
	 */

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
	Context::addCSSFile($skin_path . '/style.css');
	Context::addJsFile($skin_path . '/folding.js');

	// 변수 설정을 위한 객체 생성
	$vars = new stdClass();

	// HTML 내용과 토글 텍스트 설정
	$vars->content = $addon_info->contact;
	$vars->toggle1_text = $addon_info->toggle1_text ?: '더보기'; // 기본값 설정
	$vars->toggle2_text = $addon_info->toggle2_text ?: '접기'; // 기본값 설정
	$vars->toggle_form = $addon_info->toggle_form ?: 'H'; // 기본값 설정
	$vars->panel_on = $addon_info->panel_on ?: ''; // 기본값 설정
	$vars->top_panel_bcolor = $addon_info->top_panel_bcolor ?: 'transparent'; // 기본값 설정
	$vars->top_panel_color = $addon_info->top_panel_color ?: '#444'; // 기본값 설정
	$vars->top_panel_size = $addon_info->top_panel_size ?: '14px'; // 기본값 설정

	// Context에 변수 설정
	Context::set('mh_folding_panel', $vars);

	// 템플릿 파일 로드
	$oTemplate = &TemplateHandler::getInstance();
	$panel_html = $oTemplate->compile($skin_path, 'index.html');

	// 본문 시작 부분에 삽입 (여러 패턴 시도)
	$patterns = array(
		'/<div[^>]*class="[^"]*board[^"]*"[^>]*>/i',
	);

	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $output)) {
			$output = preg_replace($pattern, '$0' . $panel_html, $output, 1);
			break;
		}
	}