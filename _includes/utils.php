<?php
/**
 * 유틸리티 함수들
 */

/**
 * 에러 메시지 전송
 */
function sendError($message) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

/**
 * JSON 응답 전송
 */
function sendJSON($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * 필요한 디렉토리 생성
 */
function ensureDirectories() {
    if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
    if (!file_exists(OUTPUT_DIR)) mkdir(OUTPUT_DIR, 0777, true);
}
