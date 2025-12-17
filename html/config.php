<?php
/**
 * 라벨링 시스템 설정 파일
 * 
 * [중요] 배포 전 아래 설정들을 확인하세요:
 * 1. ADMIN_KEY: 관리자 접근용 비밀키 (반드시 변경 필요!)
 */

// 에러 리포팅 설정 (프로덕션에서는 display_errors를 0으로 유지)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 경로 설정
define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('LABELERS_PATH', DATA_PATH . '/labelers');
define('MASTER_JSON', DATA_PATH . '/master_passages.json');
define('USERS_JSON', DATA_PATH . '/users.json');
define('ANNOUNCEMENTS_JSON', DATA_PATH . '/announcements.json');

// ============================================
// 관리자 키 - 환경변수에서 로드
// .env 파일에서 ADMIN_KEY를 설정하세요
// ============================================
$adminKey = getenv('ADMIN_KEY');
if (empty($adminKey)) {
    // 환경변수가 없으면 에러 (보안을 위해 기본값 제공 안 함)
    error_log('ADMIN_KEY environment variable is not set!');
    $adminKey = 'ENV_NOT_SET_' . bin2hex(random_bytes(8)); // 임시 무작위 키
}
define('ADMIN_KEY', $adminKey);

// 세션 설정
session_start();

/**
 * JSON 응답 헬퍼 함수
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 사용자 데이터 로드
 */
function loadUsers() {
    if (!file_exists(USERS_JSON)) {
        return [];
    }
    $content = file_get_contents(USERS_JSON);
    return json_decode($content, true) ?: [];
}

/**
 * 사용자 데이터 저장 (파일 잠금 사용)
 */
function saveUsers($users) {
    $dir = dirname(USERS_JSON);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(USERS_JSON, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * 마스터 JSON 로드
 */
function loadMasterJson() {
    if (!file_exists(MASTER_JSON)) {
        return [];
    }
    $content = file_get_contents(MASTER_JSON);
    return json_decode($content, true) ?: [];
}

/**
 * 마스터 JSON 저장 (파일 잠금 사용)
 */
function saveMasterJson($data) {
    $dir = dirname(MASTER_JSON);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(MASTER_JSON, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * 라벨러 JSON 경로 반환
 * @param string $labelerId 라벨러 ID
 * @param string|null $nickname 라벨러 닉네임 (선택적)
 * @return string JSON 파일 경로
 */
function getLabelerJsonPath($labelerId, $nickname = null) {
    // 닉네임이 제공되면 파일명에 포함 (파일시스템 안전 문자로 변환)
    if ($nickname) {
        $safeNickname = preg_replace('/[^가-힣a-zA-Z0-9_-]/u', '_', $nickname);
        return LABELERS_PATH . '/' . $labelerId . '_' . $safeNickname . '.json';
    }
    // 닉네임 없이 호출된 경우 기존 파일 찾기 (하위 호환성)
    return findLabelerJsonPath($labelerId);
}

/**
 * 라벨러 JSON 파일 경로 찾기 (ID 기반 검색)
 * @param string $labelerId 라벨러 ID
 * @return string|null JSON 파일 경로 또는 null
 */
function findLabelerJsonPath($labelerId) {
    $pattern = LABELERS_PATH . '/' . $labelerId . '*.json';
    $files = glob($pattern);
    if (!empty($files)) {
        return $files[0];
    }
    // 기존 방식 (닉네임 없는 파일)과의 호환성
    $legacyPath = LABELERS_PATH . '/' . $labelerId . '.json';
    return $legacyPath;
}

/**
 * 라벨러 JSON 로드
 * @param string $labelerId 라벨러 ID
 * @return array|null 라벨러 데이터 또는 null
 */
function loadLabelerJson($labelerId) {
    $path = findLabelerJsonPath($labelerId);
    if (!$path || !file_exists($path)) {
        return null;
    }
    $content = file_get_contents($path);
    return json_decode($content, true);
}

/**
 * 라벨러 JSON 저장
 * @param string $labelerId 라벨러 ID
 * @param array $data 저장할 데이터
 * @param string|null $nickname 라벨러 닉네임 (선택적, 제공시 파일명에 포함)
 */
function saveLabelerJson($labelerId, $data, $nickname = null) {
    $dir = LABELERS_PATH;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // 기존 파일이 있으면 삭제 (닉네임 변경 대응)
    $existingPath = findLabelerJsonPath($labelerId);
    if ($existingPath && file_exists($existingPath)) {
        // 새 경로와 다르면 기존 파일 삭제
        $newPath = $nickname ? getLabelerJsonPath($labelerId, $nickname) : $existingPath;
        if ($existingPath !== $newPath) {
            unlink($existingPath);
        }
    }
    
    // 닉네임이 제공되면 닉네임 포함 경로로, 아니면 기존 경로로 저장
    $path = $nickname ? getLabelerJsonPath($labelerId, $nickname) : findLabelerJsonPath($labelerId);
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * 고유 키 생성
 */
function generateUniqueKey($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 키로 사용자 찾기
 */
function findUserByKey($key) {
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['key'] === $key) {
            return $user;
        }
    }
    return null;
}

/**
 * R_XXX 형태의 문서 ID 추출
 */
function extractDocId($filename) {
    if (preg_match('/^(R_\d+)/', $filename, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * 라벨러 JSON에 새 문서들을 안전하게 추가 (파일 잠금 사용)
 * 관리자가 문서를 추가할 때 라벨러의 기존 작업 내용을 보존합니다.
 * @param string $labelerId 라벨러 ID
 * @param array $newDocs 추가할 새 문서들
 * @param string|null $nickname 라벨러 닉네임
 * @return bool 성공 여부
 */
function appendDocumentsToLabelerJson($labelerId, $newDocs, $nickname = null) {
    $path = $nickname ? getLabelerJsonPath($labelerId, $nickname) : findLabelerJsonPath($labelerId);
    
    if (!$path) {
        return false;
    }
    
    // 파일 핸들을 열고 배타적 잠금 획득
    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }
    
    // 배타적 잠금 (쓰기 잠금) - 다른 프로세스가 읽기/쓰기 완료될 때까지 대기
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    
    try {
        // 잠금 상태에서 최신 데이터 읽기
        $content = stream_get_contents($fp);
        $labelerData = json_decode($content, true) ?: [];
        
        // 새 문서들 추가 (기존 데이터 보존)
        foreach ($newDocs as $docKey => $docData) {
            if (!isset($labelerData[$docKey])) {
                $labelerData[$docKey] = $docData;
            }
        }
        
        // 파일 처음으로 이동하고 내용 덮어쓰기
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($labelerData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        fflush($fp);
        
        return true;
    } finally {
        // 잠금 해제 및 파일 닫기
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * 라벨러의 진척도 계산
 */
function calculateProgress($labelerData) {
    $totalSegments = 0;
    $completedSegments = 0;
    
    foreach ($labelerData as $docKey => $docData) {
        if (isset($docData['segments'])) {
            foreach ($docData['segments'] as $segment) {
                $totalSegments++;
                if ($segment['narratedtime'] !== null) {
                    $completedSegments++;
                }
            }
        }
    }
    
    return [
        'total' => $totalSegments,
        'completed' => $completedSegments,
        'percentage' => $totalSegments > 0 ? round(($completedSegments / $totalSegments) * 100, 1) : 0
    ];
}

/**
 * 공지사항 로드
 */
function loadAnnouncements() {
    if (!file_exists(ANNOUNCEMENTS_JSON)) {
        return [];
    }
    $content = file_get_contents(ANNOUNCEMENTS_JSON);
    return json_decode($content, true) ?: [];
}

/**
 * 공지사항 저장 (파일 잠금 사용)
 */
function saveAnnouncements($announcements) {
    $dir = dirname(ANNOUNCEMENTS_JSON);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(ANNOUNCEMENTS_JSON, json_encode($announcements, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}
?>
