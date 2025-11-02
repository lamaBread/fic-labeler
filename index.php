<?php
/**
 * 데이터 라벨링 도구 - 메인 컨트롤러
 */

session_start();

// 설정
define('DATA_DIR', __DIR__ . '/data');
define('OUTPUT_DIR', __DIR__ . '/output');

// 필요한 파일 로드
require_once __DIR__ . '/_includes/utils.php';
require_once __DIR__ . '/_includes/data_reader.php';
require_once __DIR__ . '/_includes/data_writer.php';
require_once __DIR__ . '/_includes/api_handlers.php';

// 디렉토리 초기화
ensureDirectories();

// API 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'load':
            handleLoadFile();
            break;
            
        case 'add_column':
            handleAddColumn();
            break;
            
        case 'delete_column':
            handleDeleteColumn();
            break;
            
        case 'reorder_columns':
            handleReorderColumns();
            break;
            
        case 'initialize_output':
            handleInitializeOutput();
            break;
            
        case 'save_row':
            handleSaveRow();
            break;
            
        case 'get_row':
            handleGetRow();
            break;
            
        case 'list_files':
            handleListFiles();
            break;
    }
}

// 메인 HTML 뷰 렌더링
include __DIR__ . '/_views/index.html.php';
