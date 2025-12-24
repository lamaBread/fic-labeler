<?php
/**
 * 샘플링 도구 API - 백엔드
 */

require_once __DIR__ . '/../config.php';

// 샘플링 WIP 폴더 경로
define('SAMPLING_WIP_PATH', __DIR__ . '/../data/sampling_wip');
define('SAMPLING_IMAGES_PATH', __DIR__ . '/../data/sampling_images');
define('SAMPLING_DB_PATH', __DIR__ . '/../data/sampling_wip');  // DB 파일 저장 경로 (WIP와 동일)

// 폴더 생성
if (!is_dir(SAMPLING_WIP_PATH)) mkdir(SAMPLING_WIP_PATH, 0755, true);
if (!is_dir(SAMPLING_IMAGES_PATH)) mkdir(SAMPLING_IMAGES_PATH, 0755, true);

// AJAX 요청 처리
$action = $_GET['action'] ?? $_POST['action'] ?? null;
if (!$action) {
    jsonResponse(false, null, '액션이 필요합니다.');
}

// 관리자 인증 확인 (login 액션 제외)
if ($action !== 'login' && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
    jsonResponse(false, null, '관리자 인증이 필요합니다.');
}

switch ($action) {
    case 'login':
        $key = $_POST['admin_key'] ?? '';
        if ($key === ADMIN_KEY) {
            $_SESSION['is_admin'] = true;
            jsonResponse(true, null, '로그인 성공');
        } else {
            jsonResponse(false, null, '관리자 키가 올바르지 않습니다.');
        }
        break;
        
    case 'logout':
        $_SESSION['is_admin'] = false;
        session_destroy();
        jsonResponse(true, null, '로그아웃 되었습니다.');
        break;
        
    case 'check_session':
        jsonResponse(true, ['is_admin' => isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true]);
        break;
        
    case 'list':
        $files = [];
        if (is_dir(SAMPLING_WIP_PATH)) {
            foreach (glob(SAMPLING_WIP_PATH . '/*.json') as $file) {
                $filename = basename($file);
                $files[] = [
                    'filename' => $filename,
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                    'size' => filesize($file)
                ];
            }
        }
        usort($files, fn($a, $b) => strtotime($b['modified']) - strtotime($a['modified']));
        jsonResponse(true, $files);
        break;
        
    case 'load':
        $filename = basename($_GET['filename'] ?? '');
        if (empty($filename)) jsonResponse(false, null, '파일명이 필요합니다.');
        $filepath = SAMPLING_WIP_PATH . '/' . $filename;
        if (!file_exists($filepath)) jsonResponse(false, null, '파일을 찾을 수 없습니다.');
        $json = json_decode(file_get_contents($filepath), true);
        if ($json === null) jsonResponse(false, null, 'JSON 파싱 오류');
        jsonResponse(true, $json);
        break;
        
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['filename']) || !isset($data['content'])) {
            jsonResponse(false, null, '잘못된 요청입니다.');
        }
        $filename = basename($data['filename']);
        if (!preg_match('/\.json$/i', $filename)) $filename .= '.json';
        $filepath = SAMPLING_WIP_PATH . '/' . $filename;
        $result = file_put_contents($filepath, json_encode($data['content'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        if ($result === false) jsonResponse(false, null, '파일 저장 실패');
        jsonResponse(true, ['filename' => $filename], '저장 완료');
        break;
        
    case 'check_exists':
        $filename = basename($_GET['filename'] ?? '');
        if (empty($filename)) jsonResponse(false, null, '파일명이 필요합니다.');
        if (!preg_match('/\.json$/i', $filename)) $filename .= '.json';
        $filepath = SAMPLING_WIP_PATH . '/' . $filename;
        jsonResponse(true, ['exists' => file_exists($filepath), 'filename' => $filename]);
        break;
        
    case 'download':
        $filename = basename($_GET['filename'] ?? '');
        if (empty($filename)) die('파일명이 필요합니다.');
        $filepath = SAMPLING_WIP_PATH . '/' . $filename;
        if (!file_exists($filepath)) die('파일을 찾을 수 없습니다.');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
        
    case 'delete':
        $filename = basename($_GET['filename'] ?? '');
        if (empty($filename)) jsonResponse(false, null, '파일명이 필요합니다.');
        $filepath = SAMPLING_WIP_PATH . '/' . $filename;
        if (!file_exists($filepath)) jsonResponse(false, null, '파일을 찾을 수 없습니다.');
        if (unlink($filepath)) {
            jsonResponse(true, null, '삭제 완료');
        } else {
            jsonResponse(false, null, '삭제 실패');
        }
        break;

    // ==================== DB 파일 관리 API ====================
    
    case 'load_db':
        // DB 파일 로드 (R_XXX-db.json 형식)
        $docid = basename($_GET['docid'] ?? '');
        if (empty($docid)) jsonResponse(false, null, 'docid가 필요합니다.');
        
        $dbFilename = $docid . '-db.json';
        $dbFilepath = SAMPLING_DB_PATH . '/' . $dbFilename;
        
        if (!file_exists($dbFilepath)) {
            jsonResponse(true, ['exists' => false, 'data' => null], 'DB 파일이 없습니다.');
        }
        
        $json = json_decode(file_get_contents($dbFilepath), true);
        if ($json === null) jsonResponse(false, null, 'DB JSON 파싱 오류');
        jsonResponse(true, ['exists' => true, 'data' => $json, 'filename' => $dbFilename]);
        break;
        
    case 'save_db':
        // DB 파일 저장 (중간 진행 상태 저장용)
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['docid']) || !isset($data['content'])) {
            jsonResponse(false, null, '잘못된 요청입니다. docid와 content가 필요합니다.');
        }
        
        $docid = basename($data['docid']);
        $dbFilename = $docid . '-db.json';
        $dbFilepath = SAMPLING_DB_PATH . '/' . $dbFilename;
        
        $result = file_put_contents($dbFilepath, json_encode($data['content'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        if ($result === false) jsonResponse(false, null, 'DB 파일 저장 실패');
        jsonResponse(true, ['filename' => $dbFilename], 'DB 저장 완료');
        break;
        
    case 'delete_db':
        // DB 파일 삭제
        $docid = basename($_GET['docid'] ?? '');
        if (empty($docid)) jsonResponse(false, null, 'docid가 필요합니다.');
        
        $dbFilename = $docid . '-db.json';
        $dbFilepath = SAMPLING_DB_PATH . '/' . $dbFilename;
        
        if (!file_exists($dbFilepath)) jsonResponse(false, null, 'DB 파일을 찾을 수 없습니다.');
        if (unlink($dbFilepath)) {
            jsonResponse(true, null, 'DB 파일 삭제 완료');
        } else {
            jsonResponse(false, null, 'DB 파일 삭제 실패');
        }
        break;

    // ==================== 이미지 파일 관리 API ====================
    
    case 'check_images':
        // 서버에 이미지가 있는지 확인
        $docid = basename($_GET['docid'] ?? '');
        if (empty($docid)) jsonResponse(false, null, 'docid가 필요합니다.');
        
        $docFolder = SAMPLING_IMAGES_PATH . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $docid);
        
        if (!is_dir($docFolder)) {
            jsonResponse(true, ['exists' => false, 'images' => [], 'count' => 0]);
        }
        
        $images = [];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        
        foreach (glob($docFolder . '/*') as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts)) {
                $images[] = [
                    'filename' => basename($file),
                    'filepath' => $file,
                    'size' => filesize($file),
                    'modified' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }
        
        // 파일명 기준 정렬
        usort($images, fn($a, $b) => strnatcmp($a['filename'], $b['filename']));
        
        jsonResponse(true, [
            'exists' => count($images) > 0,
            'images' => $images,
            'count' => count($images),
            'folder' => $docFolder
        ]);
        break;
        
    case 'delete_images':
        // 특정 docid의 모든 이미지 삭제
        $docid = basename($_GET['docid'] ?? '');
        if (empty($docid)) jsonResponse(false, null, 'docid가 필요합니다.');
        
        $docFolder = SAMPLING_IMAGES_PATH . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $docid);
        
        if (!is_dir($docFolder)) {
            jsonResponse(true, ['deleted' => 0], '삭제할 이미지가 없습니다.');
        }
        
        $deleted = 0;
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        
        foreach (glob($docFolder . '/*') as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts) && is_file($file)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        jsonResponse(true, ['deleted' => $deleted], "{$deleted}개 이미지 삭제 완료");
        break;

    // ==================== OCR 관련 API ====================
    
    case 'upload_image':
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, null, '이미지 업로드 실패');
        }
        
        $docid = $_POST['docid'] ?? 'unknown';
        $pageNum = $_POST['page_num'] ?? '0';
        
        // 이미지 저장 폴더 (문서별)
        $docFolder = SAMPLING_IMAGES_PATH . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $docid);
        if (!is_dir($docFolder)) mkdir($docFolder, 0755, true);
        
        // 파일 확장자 확인
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            jsonResponse(false, null, '허용되지 않는 이미지 형식입니다. (JPG, PNG, WebP만 가능)');
        }
        
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mimeType];
        $filename = "page_{$pageNum}.{$ext}";
        $filepath = $docFolder . '/' . $filename;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            jsonResponse(false, null, '파일 저장 실패');
        }
        
        jsonResponse(true, [
            'filepath' => $filepath,
            'filename' => $filename,
            'page_num' => $pageNum
        ]);
        break;
        
    case 'ocr_analyze':
        // 이미지에서 글자 수와 행 수 분석 (CLOVA OCR 사용)
        $data = json_decode(file_get_contents('php://input'), true);
        $imagePath = $data['image_path'] ?? '';
        
        if (empty($imagePath) || !file_exists($imagePath)) {
            jsonResponse(false, null, '이미지 파일을 찾을 수 없습니다.');
        }
        
        // CLOVA OCR API 호출
        $result = callClovaOcr($imagePath);
        
        if ($result['success']) {
            jsonResponse(true, $result['data']);
        } else {
            jsonResponse(false, null, $result['error']);
        }
        break;
        
    case 'ocr_batch':
        // 여러 이미지 일괄 분석 (CLOVA OCR 사용)
        $data = json_decode(file_get_contents('php://input'), true);
        $imagePaths = $data['image_paths'] ?? [];
        
        if (empty($imagePaths)) {
            jsonResponse(false, null, '분석할 이미지가 없습니다.');
        }
        
        $results = [];
        foreach ($imagePaths as $path) {
            if (!file_exists($path)) {
                $results[] = ['path' => $path, 'success' => false, 'error' => '파일 없음'];
                continue;
            }
            
            $result = callClovaOcr($path);
            
            $results[] = [
                'path' => $path,
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ];
        }
        
        jsonResponse(true, $results);
        break;
    
    case 'check_ocr':
        // CLOVA OCR API 연결 상태 확인
        $invokeUrl = CLOVA_OCR_INVOKE_URL;
        $secretKey = CLOVA_OCR_SECRET_KEY;
        
        if (empty($invokeUrl) || empty($secretKey)) {
            jsonResponse(true, [
                'connected' => false,
                'error' => 'CLOVA OCR API 환경변수가 설정되지 않았습니다. .env 파일을 확인하세요.'
            ]);
        } else {
            jsonResponse(true, [
                'connected' => true,
                'provider' => 'CLOVA OCR',
                'invoke_url_set' => !empty($invokeUrl),
                'secret_key_set' => !empty($secretKey)
            ]);
        }
        break;
        
    default:
        jsonResponse(false, null, '알 수 없는 액션');
}

/**
 * CLOVA OCR API 호출
 * 네이버 클라우드 플랫폼 CLOVA OCR (General) 사용
 * 
 * @param string $imagePath 이미지 파일 경로
 * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
 */
function callClovaOcr($imagePath) {
    $invokeUrl = CLOVA_OCR_INVOKE_URL;
    $secretKey = CLOVA_OCR_SECRET_KEY;
    
    // API 키 검증
    if (empty($invokeUrl) || empty($secretKey)) {
        return [
            'success' => false, 
            'error' => 'CLOVA OCR API 환경변수가 설정되지 않았습니다. .env 파일을 확인하세요.'
        ];
    }
    
    // 이미지 파일 읽기
    if (!file_exists($imagePath)) {
        return ['success' => false, 'error' => '이미지 파일을 찾을 수 없습니다.'];
    }
    
    $imageData = file_get_contents($imagePath);
    if ($imageData === false) {
        return ['success' => false, 'error' => '이미지 파일 읽기 실패'];
    }
    
    // MIME 타입 확인
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $imageData);
    finfo_close($finfo);
    
    $formatMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/tiff' => 'tiff',
        'application/pdf' => 'pdf'
    ];
    
    $format = $formatMap[$mimeType] ?? 'jpg';
    
    // 요청 ID 생성 (UUID v4)
    $requestId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // CLOVA OCR 요청 데이터 구성
    $requestData = [
        'version' => 'V2',
        'requestId' => $requestId,
        'timestamp' => time() * 1000,
        'lang' => 'ko',  // 한국어
        'images' => [
            [
                'format' => $format,
                'name' => basename($imagePath),
                'data' => base64_encode($imageData)
            ]
        ],
        'enableTableDetection' => false  // 테이블 감지 비활성화 (속도 향상)
    ];
    
    // API 호출
    $ch = curl_init($invokeUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-OCR-SECRET: ' . $secretKey
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // cURL 에러 처리
    if ($curlError) {
        return ['success' => false, 'error' => 'CLOVA OCR 연결 실패: ' . $curlError];
    }
    
    // HTTP 상태 코드 확인
    if ($httpCode !== 200) {
        $errorBody = json_decode($response, true);
        $errorMsg = $errorBody['errorMessage'] ?? $errorBody['message'] ?? 'HTTP ' . $httpCode;
        return ['success' => false, 'error' => 'CLOVA OCR API 오류: ' . $errorMsg];
    }
    
    // 응답 파싱
    $result = json_decode($response, true);
    if (!$result) {
        return ['success' => false, 'error' => 'OCR 응답 파싱 실패'];
    }
    
    // 이미지 처리 결과 확인
    if (!isset($result['images']) || empty($result['images'])) {
        return ['success' => false, 'error' => 'OCR 결과가 없습니다.'];
    }
    
    $imageResult = $result['images'][0];
    
    // 에러 확인
    if ($imageResult['inferResult'] !== 'SUCCESS') {
        $errorMsg = $imageResult['message'] ?? '알 수 없는 오류';
        return ['success' => false, 'error' => 'OCR 처리 실패: ' . $errorMsg];
    }
    
    // 텍스트 필드에서 정보 추출
    $fields = $imageResult['fields'] ?? [];
    
    // 통계 계산
    $allText = '';
    
    foreach ($fields as $field) {
        $text = $field['inferText'] ?? '';
        $allText .= $text . ' ';
    }
    
    // 전체 텍스트 정리 (마지막 공백 제거)
    $allText = rtrim($allText);
    
    // 글자 수 계산 (공백 포함)
    $totalCharCount = mb_strlen($allText, 'UTF-8');
    
    // 단어 수 계산 (공백 기준)
    $wordCount = 0;
    if (!empty($allText)) {
        // 한글, 영문, 숫자 단위로 단어 분리
        $words = preg_split('/\s+/u', $allText);
        $wordCount = count(array_filter($words, function($w) { return mb_strlen($w, 'UTF-8') > 0; }));
    }
    
    return [
        'success' => true,
        'data' => [
            'char_count' => $totalCharCount,  // 공백 포함 글자 수
            'word_count' => $wordCount,
            'raw_text' => $allText,  // 전체 추출 텍스트 (디버깅용)
            'provider' => 'CLOVA OCR'
        ]
    ];
}
