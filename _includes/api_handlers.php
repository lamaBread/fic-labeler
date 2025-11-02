<?php
/**
 * API 핸들러 함수들
 */

/**
 * 파일 로드 액션
 */
function handleLoadFile() {
    $filename = $_POST['filename'] ?? '';
    if (empty($filename)) sendError('파일명이 필요합니다.');
    
    $result = loadDataFile($filename);
    if ($result === null) sendError('파일을 읽을 수 없습니다.');
    
    $_SESSION['filename'] = $filename;
    $_SESSION['custom_output_name'] = $_POST['custom_output_name'] ?? '';
    $_SESSION['headers'] = $result['headers'];
    $_SESSION['data'] = $result['data'];
    $_SESSION['current_index'] = 0;
    
    // 새 파일을 로드할 때 열 잠금 상태 초기화
    $_SESSION['columns_locked'] = false;
    $_SESSION['output_format'] = null;
    
    sendJSON([
        'success' => true,
        'headers' => $result['headers'],
        'total' => count($result['data'])
    ]);
}

/**
 * 열 추가 액션 (2단계에서만 가능)
 */
function handleAddColumn() {
    $columnName = $_POST['column_name'] ?? '';
    if (empty($columnName)) sendError('열 이름이 필요합니다.');
    
    if (!isset($_SESSION['headers'])) sendError('먼저 파일을 로드해주세요.');
    
    // 3단계로 진입한 후에는 열 추가 불가
    if (isset($_SESSION['columns_locked']) && $_SESSION['columns_locked']) {
        sendError('3단계 진입 후에는 열을 추가할 수 없습니다.');
    }
    
    if (in_array($columnName, $_SESSION['headers'])) {
        sendError('이미 존재하는 열 이름입니다.');
    }
    
    $_SESSION['headers'][] = $columnName;
    foreach ($_SESSION['data'] as &$row) {
        $row[$columnName] = '';
    }
    
    sendJSON(['success' => true, 'headers' => $_SESSION['headers']]);
}

/**
 * 행 저장 액션
 */
function handleSaveRow() {
    $index = (int)($_POST['index'] ?? 0);
    $rowData = json_decode($_POST['row_data'] ?? '{}', true);
    
    if (!isset($_SESSION['data'])) sendError('데이터가 없습니다.');
    if ($index < 0 || $index >= count($_SESSION['data'])) sendError('잘못된 인덱스입니다.');
    
    // 데이터 업데이트
    foreach ($rowData as $key => $value) {
        $_SESSION['data'][$index][$key] = $value;
    }
    
    // 자동 저장
    $format = $_POST['format'] ?? 'csv';
    $customOutputName = $_POST['custom_output_name'] ?? ($_SESSION['custom_output_name'] ?? '');
    $saved = saveData($_SESSION['filename'], $format, $_SESSION['headers'], $_SESSION['data'], $customOutputName);
    
    sendJSON([
        'success' => true,
        'saved' => $saved,
        'total' => count($_SESSION['data'])
    ]);
}

/**
 * 행 가져오기 액션
 */
function handleGetRow() {
    $index = (int)($_POST['index'] ?? 0);
    
    if (!isset($_SESSION['data'])) sendError('데이터가 없습니다.');
    if ($index < 0 || $index >= count($_SESSION['data'])) {
        sendJSON(['success' => false, 'message' => '더 이상 데이터가 없습니다.']);
    }
    
    $_SESSION['current_index'] = $index;
    
    sendJSON([
        'success' => true,
        'row' => $_SESSION['data'][$index],
        'index' => $index,
        'total' => count($_SESSION['data'])
    ]);
}

/**
 * 파일 목록 가져오기 액션
 */
function handleListFiles() {
    $files = array_diff(scandir(DATA_DIR), ['.', '..']);
    $validFiles = array_filter($files, function($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, ['csv', 'tsv', 'json']);
    });
    
    sendJSON(['success' => true, 'files' => array_values($validFiles)]);
}

/**
 * 출력 파일 초기화 액션 (2단계 -> 3단계 전환 시)
 */
function handleInitializeOutput() {
    if (!isset($_SESSION['filename']) || !isset($_SESSION['headers']) || !isset($_SESSION['data'])) {
        sendError('먼저 파일을 로드하고 열을 설정해주세요.');
    }
    
    $format = $_POST['format'] ?? 'csv';
    $customOutputName = $_POST['custom_output_name'] ?? ($_SESSION['custom_output_name'] ?? '');
    
    // 열 구조 확정
    $_SESSION['output_format'] = $format;
    $_SESSION['columns_locked'] = true;
    if (!empty($customOutputName)) {
        $_SESSION['custom_output_name'] = $customOutputName;
    }
    
    // 출력 파일 생성
    $saved = saveData($_SESSION['filename'], $format, $_SESSION['headers'], $_SESSION['data'], $customOutputName);
    
    if (!$saved) {
        sendError('출력 파일 생성에 실패했습니다.');
    }
    
    sendJSON([
        'success' => true,
        'message' => '출력 파일이 생성되었습니다.',
        'output_file' => generateOutputFilename($_SESSION['filename'], $format, $customOutputName)
    ]);
}

/**
 * 열 삭제 액션 (2단계에서만 가능)
 */
function handleDeleteColumn() {
    $columnName = $_POST['column_name'] ?? '';
    if (empty($columnName)) sendError('열 이름이 필요합니다.');
    
    if (!isset($_SESSION['headers'])) sendError('먼저 파일을 로드해주세요.');
    
    // 3단계로 진입한 후에는 열 삭제 불가
    if (isset($_SESSION['columns_locked']) && $_SESSION['columns_locked']) {
        sendError('3단계 진입 후에는 열을 삭제할 수 없습니다.');
    }
    
    // 열이 존재하는지 확인
    $index = array_search($columnName, $_SESSION['headers']);
    if ($index === false) {
        sendError('존재하지 않는 열입니다.');
    }
    
    // 헤더에서 제거
    unset($_SESSION['headers'][$index]);
    $_SESSION['headers'] = array_values($_SESSION['headers']); // 인덱스 재정렬
    
    // 모든 데이터 행에서 해당 열 제거
    foreach ($_SESSION['data'] as &$row) {
        unset($row[$columnName]);
    }
    
    sendJSON(['success' => true, 'headers' => $_SESSION['headers']]);
}

/**
 * 열 순서 변경 액션 (2단계에서만 가능)
 */
function handleReorderColumns() {
    $newOrder = json_decode($_POST['columns'] ?? '[]', true);
    
    if (!isset($_SESSION['headers'])) sendError('먼저 파일을 로드해주세요.');
    
    // 3단계로 진입한 후에는 열 순서 변경 불가
    if (isset($_SESSION['columns_locked']) && $_SESSION['columns_locked']) {
        sendError('3단계 진입 후에는 열 순서를 변경할 수 없습니다.');
    }
    
    if (empty($newOrder)) sendError('새로운 열 순서가 필요합니다.');
    
    // 새 순서가 유효한지 확인 (모든 열이 포함되어 있는지)
    if (count($newOrder) !== count($_SESSION['headers'])) {
        sendError('열 개수가 일치하지 않습니다.');
    }
    
    foreach ($newOrder as $col) {
        if (!in_array($col, $_SESSION['headers'])) {
            sendError('존재하지 않는 열이 포함되어 있습니다: ' . $col);
        }
    }
    
    // 헤더 순서 업데이트
    $_SESSION['headers'] = $newOrder;
    
    // 각 데이터 행의 키 순서도 업데이트
    $newData = [];
    foreach ($_SESSION['data'] as $row) {
        $newRow = [];
        foreach ($_SESSION['headers'] as $header) {
            $newRow[$header] = $row[$header] ?? '';
        }
        $newData[] = $newRow;
    }
    $_SESSION['data'] = $newData;
    
    sendJSON(['success' => true, 'headers' => $_SESSION['headers']]);
}
