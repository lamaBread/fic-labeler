<?php
/**
 * 라벨링 시스템 API 엔드포인트
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // ========================
    // 인증 관련
    // ========================
    case 'login':
        handleLogin();
        break;
    
    case 'logout':
        handleLogout();
        break;
    
    case 'check_session':
        handleCheckSession();
        break;
    
    // ========================
    // 라벨러 기능
    // ========================
    case 'get_progress':
        handleGetProgress();
        break;
    
    case 'get_segments':
        handleGetSegments();
        break;
    
    case 'get_next_segment':
        handleGetNextSegment();
        break;
    
    case 'save_label':
        handleSaveLabel();
        break;
    
    case 'get_document_list':
        handleGetDocumentList();
        break;
    
    // ========================
    // 관리자 기능
    // ========================
    case 'admin_login':
        handleAdminLogin();
        break;
    
    case 'get_labelers':
        handleGetLabelers();
        break;
    
    case 'add_labeler':
        handleAddLabeler();
        break;
    
    case 'delete_labeler':
        handleDeleteLabeler();
        break;
    
    case 'import_json':
        handleImportJson();
        break;
    
    case 'replace_work':
        handleReplaceWork();
        break;
    
    case 'add_single_work':
        handleAddSingleWork();
        break;
    
    case 'get_all_progress':
        handleGetAllProgress();
        break;
    
    case 'export_results':
        handleExportResults();
        break;
    
    // ========================
    // 공지사항 관련
    // ========================
    case 'get_announcements':
        handleGetAnnouncements();
        break;
    
    case 'add_announcement':
        handleAddAnnouncement();
        break;
    
    case 'delete_announcement':
        handleDeleteAnnouncement();
        break;
    
    // ========================
    // 활동 추적
    // ========================
    case 'heartbeat':
        handleHeartbeat();
        break;
    
    case 'get_user_activity':
        handleGetUserActivity();
        break;
    
    default:
        jsonResponse(false, null, '알 수 없는 액션입니다.');
}

// ========================
// 핸들러 함수들
// ========================

function handleLogin() {
    $key = $_POST['key'] ?? '';
    
    if (empty($key)) {
        jsonResponse(false, null, '키를 입력해주세요.');
    }
    
    $user = findUserByKey($key);
    
    if (!$user) {
        jsonResponse(false, null, '유효하지 않은 키입니다.');
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_key'] = $user['key'];
    $_SESSION['is_labeler'] = true;
    
    // 로그인 시 활동 시간 업데이트
    updateUserActivity($user['id']);
    
    jsonResponse(true, [
        'id' => $user['id'],
        'nickname' => $user['nickname']
    ], '로그인 성공');
}

function handleLogout() {
    session_destroy();
    jsonResponse(true, null, '로그아웃 되었습니다.');
}

function handleCheckSession() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['is_labeler'])) {
        $user = findUserByKey($_SESSION['user_key']);
        if ($user) {
            jsonResponse(true, [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'is_admin' => false
            ]);
        }
    }
    
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        jsonResponse(true, [
            'is_admin' => true
        ]);
    }
    
    jsonResponse(false, null, '세션이 만료되었습니다.');
}

function handleGetProgress() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, '로그인이 필요합니다.');
    }
    
    $labelerId = $_SESSION['user_id'];
    $labelerData = loadLabelerJson($labelerId);
    
    if (!$labelerData) {
        jsonResponse(false, null, '라벨러 데이터를 찾을 수 없습니다.');
    }
    
    $progress = calculateProgress($labelerData);
    
    // 문서별 진행률도 계산
    $docProgress = [];
    foreach ($labelerData as $docKey => $docData) {
        if (isset($docData['segments'])) {
            $docTotal = count($docData['segments']);
            $docCompleted = 0;
            foreach ($docData['segments'] as $segment) {
                if ($segment['narratedtime'] !== null) {
                    $docCompleted++;
                }
            }
            $docProgress[$docKey] = [
                'docid' => $docData['metadata']['docid'] ?? extractDocId($docKey),
                'title' => $docData['metadata']['title'] ?? $docKey,
                'author' => $docData['metadata']['author'] ?? '',
                'total' => $docTotal,
                'completed' => $docCompleted,
                'percentage' => $docTotal > 0 ? round(($docCompleted / $docTotal) * 100, 1) : 0
            ];
        }
    }
    
    jsonResponse(true, [
        'overall' => $progress,
        'documents' => $docProgress
    ]);
}

function handleGetSegments() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, '로그인이 필요합니다.');
    }
    
    $docKey = $_GET['doc_key'] ?? '';
    if (empty($docKey)) {
        jsonResponse(false, null, '문서 키가 필요합니다.');
    }
    
    $labelerId = $_SESSION['user_id'];
    $labelerData = loadLabelerJson($labelerId);
    
    if (!$labelerData || !isset($labelerData[$docKey])) {
        jsonResponse(false, null, '문서를 찾을 수 없습니다.');
    }
    
    // 문서 열기 시 활동 시간 업데이트
    updateUserActivity($labelerId);
    
    jsonResponse(true, $labelerData[$docKey]);
}

function handleGetNextSegment() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, '로그인이 필요합니다.');
    }
    
    $labelerId = $_SESSION['user_id'];
    $labelerData = loadLabelerJson($labelerId);
    
    if (!$labelerData) {
        jsonResponse(false, null, '라벨러 데이터를 찾을 수 없습니다.');
    }
    
    // 아직 라벨링되지 않은 첫 번째 세그먼트 찾기
    foreach ($labelerData as $docKey => $docData) {
        if (isset($docData['segments'])) {
            foreach ($docData['segments'] as $segIdx => $segment) {
                if ($segment['narratedtime'] === null) {
                    jsonResponse(true, [
                        'doc_key' => $docKey,
                        'segment_idx' => $segIdx,
                        'segment' => $segment,
                        'metadata' => $docData['metadata']
                    ]);
                }
            }
        }
    }
    
    jsonResponse(true, null, '모든 라벨링이 완료되었습니다.');
}

function handleSaveLabel() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, '로그인이 필요합니다.');
    }
    
    $docKey = $_POST['doc_key'] ?? '';
    $segmentIdx = $_POST['segment_idx'] ?? '';
    $narratedtime = $_POST['narratedtime'] ?? null;
    $ellipsistime = $_POST['ellipsistime'] ?? 0;
    $subjectivetime = $_POST['subjectivetime'] ?? 0;
    $ellipsisphrase = $_POST['ellipsisphrase'] ?? '';
    $subjectivephrase = $_POST['subjectivephrase'] ?? '';
    
    if (empty($docKey) || $segmentIdx === '') {
        jsonResponse(false, null, '필수 파라미터가 누락되었습니다.');
    }
    
    if ($narratedtime === null || $narratedtime === '') {
        jsonResponse(false, null, 'narratedtime은 필수 입력 항목입니다.');
    }
    
    // narratedtime 값 검증: -1 이상이어야 함 (-1은 '정답 없음'을 의미)
    if (floatval($narratedtime) < -1) {
        jsonResponse(false, null, 'narratedtime은 -1 이상이어야 합니다.');
    }
    
    $labelerId = $_SESSION['user_id'];
    $labelerData = loadLabelerJson($labelerId);
    
    if (!$labelerData || !isset($labelerData[$docKey])) {
        jsonResponse(false, null, '문서를 찾을 수 없습니다.');
    }
    
    $segmentIdx = intval($segmentIdx);
    $found = false;
    
    foreach ($labelerData[$docKey]['segments'] as $idx => &$segment) {
        if ($idx == $segmentIdx) {
            $segment['narratedtime'] = floatval($narratedtime);
            $segment['ellipsistime'] = floatval($ellipsistime);
            $segment['subjectivetime'] = floatval($subjectivetime);
            $segment['ellipsisphrase'] = $ellipsisphrase;
            $segment['subjectivephrase'] = $subjectivephrase;
            $segment['complete'] = true;
            $segment['labeled_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        jsonResponse(false, null, '해당 세그먼트를 찾을 수 없습니다.');
    }
    
    // 현재 사용자의 닉네임 가져오기
    $user = findUserByKey($_SESSION['user_key']);
    $nickname = $user ? $user['nickname'] : null;
    
    saveLabelerJson($labelerId, $labelerData, $nickname);
    
    // 저장 시 활동 시간 업데이트
    updateUserActivity($labelerId);
    
    jsonResponse(true, null, '저장되었습니다.');
}

function handleGetDocumentList() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, '로그인이 필요합니다.');
    }
    
    $labelerId = $_SESSION['user_id'];
    $labelerData = loadLabelerJson($labelerId);
    
    if (!$labelerData) {
        jsonResponse(false, null, '라벨러 데이터를 찾을 수 없습니다.');
    }
    
    $documents = [];
    foreach ($labelerData as $docKey => $docData) {
        if (isset($docData['metadata'])) {
            $documents[] = [
                'key' => $docKey,
                'docid' => $docData['metadata']['docid'] ?? '',
                'title' => $docData['metadata']['title'] ?? '',
                'author' => $docData['metadata']['author'] ?? ''
            ];
        }
    }
    
    jsonResponse(true, $documents);
}

// ========================
// 관리자 핸들러
// ========================

function handleAdminLogin() {
    $key = $_POST['key'] ?? '';
    
    if ($key === ADMIN_KEY) {
        $_SESSION['is_admin'] = true;
        jsonResponse(true, null, '관리자 로그인 성공');
    }
    
    jsonResponse(false, null, '관리자 키가 올바르지 않습니다.');
}

function handleGetLabelers() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $users = loadUsers();
    
    // 각 라벨러의 진척도 추가
    foreach ($users as &$user) {
        $labelerData = loadLabelerJson($user['id']);
        if ($labelerData) {
            $user['progress'] = calculateProgress($labelerData);
        } else {
            $user['progress'] = ['total' => 0, 'completed' => 0, 'percentage' => 0];
        }
    }
    
    jsonResponse(true, $users);
}

function handleAddLabeler() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $nickname = $_POST['nickname'] ?? '';
    
    if (empty($nickname)) {
        jsonResponse(false, null, '별명을 입력해주세요.');
    }
    
    $users = loadUsers();
    
    // 새 사용자 생성
    $newUser = [
        'id' => generateUniqueKey(16),
        'key' => generateUniqueKey(32),
        'nickname' => $nickname,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    // 마스터 JSON을 복사하여 라벨러용 JSON 생성 (닉네임 포함 파일명)
    $masterData = loadMasterJson();
    if (!empty($masterData)) {
        saveLabelerJson($newUser['id'], $masterData, $newUser['nickname']);
    }
    
    jsonResponse(true, $newUser, '라벨러가 추가되었습니다.');
}

function handleDeleteLabeler() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $labelerId = $_POST['labeler_id'] ?? '';
    
    if (empty($labelerId)) {
        jsonResponse(false, null, '라벨러 ID가 필요합니다.');
    }
    
    $users = loadUsers();
    $users = array_filter($users, function($u) use ($labelerId) {
        return $u['id'] !== $labelerId;
    });
    $users = array_values($users);
    saveUsers($users);
    
    // 라벨러 JSON 파일 삭제
    $labelerJsonPath = getLabelerJsonPath($labelerId);
    if (file_exists($labelerJsonPath)) {
        unlink($labelerJsonPath);
    }
    
    jsonResponse(true, null, '라벨러가 삭제되었습니다.');
}

function handleImportJson() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, null, 'JSON 파일 업로드에 실패했습니다.');
    }
    
    $content = file_get_contents($_FILES['json_file']['tmp_name']);
    $newData = json_decode($content, true);
    
    if (!$newData) {
        jsonResponse(false, null, 'JSON 파일을 파싱할 수 없습니다.');
    }
    
    // 마스터 JSON 로드
    $masterData = loadMasterJson();
    
    // 기존에 없는 문서만 추가
    $existingDocIds = [];
    foreach ($masterData as $docKey => $docData) {
        $docId = extractDocId($docKey);
        if ($docId) {
            $existingDocIds[$docId] = true;
        }
    }
    
    $addedCount = 0;
    $newDocs = [];
    
    foreach ($newData as $docKey => $docData) {
        $docId = extractDocId($docKey);
        if ($docId && !isset($existingDocIds[$docId])) {
            $masterData[$docKey] = $docData;
            $newDocs[$docKey] = $docData;
            $addedCount++;
        }
    }
    
    if ($addedCount > 0) {
        // 마스터 JSON 저장
        saveMasterJson($masterData);
        
        // 모든 라벨러의 JSON에도 새 문서 추가 (안전한 방식 사용)
        // 라벨러가 작업 중이어도 기존 라벨링 결과가 보존됨
        $users = loadUsers();
        foreach ($users as $user) {
            appendDocumentsToLabelerJson($user['id'], $newDocs, $user['nickname']);
        }
    }
    
    jsonResponse(true, ['added' => $addedCount], "{$addedCount}개의 새로운 작품이 추가되었습니다.");
}

/**
 * 개별 작품 교체 핸들러
 * 동일한 R_XXX 번호를 가진 기존 작품을 제거하고 새 내용으로 교체
 * 모든 라벨러의 해당 작품 라벨링 데이터도 초기화됨
 */
function handleReplaceWork() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $jsonContent = $_POST['json_content'] ?? '';
    
    if (empty($jsonContent)) {
        jsonResponse(false, null, 'JSON 내용이 필요합니다.');
    }
    
    $newData = json_decode($jsonContent, true);
    
    if (!$newData || !is_array($newData)) {
        jsonResponse(false, null, 'JSON 파싱에 실패했습니다. 올바른 형식인지 확인해주세요.');
    }
    
    // 입력된 JSON에서 첫 번째 작품 추출
    $newDocKey = array_key_first($newData);
    if (!$newDocKey) {
        jsonResponse(false, null, '작품 데이터가 없습니다.');
    }
    
    $newDocData = $newData[$newDocKey];
    $targetDocId = extractDocId($newDocKey);
    
    if (!$targetDocId) {
        jsonResponse(false, null, 'R_XXX 형식의 문서 ID를 찾을 수 없습니다.');
    }
    
    // 마스터 JSON 로드
    $masterData = loadMasterJson();
    
    // 기존에 동일한 R_XXX를 가진 작품 찾아서 제거
    $oldDocKey = null;
    foreach ($masterData as $docKey => $docData) {
        if (extractDocId($docKey) === $targetDocId) {
            $oldDocKey = $docKey;
            break;
        }
    }
    
    if (!$oldDocKey) {
        jsonResponse(false, null, "기존에 {$targetDocId} 작품이 존재하지 않습니다. 작품 추가 기능을 사용해주세요.");
    }
    
    // 마스터에서 기존 작품 제거 후 새 작품 추가
    unset($masterData[$oldDocKey]);
    $masterData[$newDocKey] = $newDocData;
    
    // R_XXX 번호순으로 정렬
    $masterData = sortByDocId($masterData);
    
    // 마스터 JSON 저장
    saveMasterJson($masterData);
    
    // 모든 라벨러의 JSON에서도 해당 작품 교체 (라벨링 데이터 초기화)
    $users = loadUsers();
    foreach ($users as $user) {
        replaceWorkInLabelerJson($user['id'], $oldDocKey, $newDocKey, $newDocData, $user['nickname']);
    }
    
    jsonResponse(true, [
        'replaced_doc_id' => $targetDocId,
        'old_key' => $oldDocKey,
        'new_key' => $newDocKey
    ], "{$targetDocId} 작품이 성공적으로 교체되었습니다. 모든 라벨러의 해당 작품 라벨링 데이터가 초기화되었습니다.");
}

/**
 * 개별 작품 추가 핸들러
 * 새 작품을 추가하고 R_XXX 번호순으로 정렬
 */
function handleAddSingleWork() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $jsonContent = $_POST['json_content'] ?? '';
    
    if (empty($jsonContent)) {
        jsonResponse(false, null, 'JSON 내용이 필요합니다.');
    }
    
    $newData = json_decode($jsonContent, true);
    
    if (!$newData || !is_array($newData)) {
        jsonResponse(false, null, 'JSON 파싱에 실패했습니다. 올바른 형식인지 확인해주세요.');
    }
    
    // 입력된 JSON에서 첫 번째 작품 추출
    $newDocKey = array_key_first($newData);
    if (!$newDocKey) {
        jsonResponse(false, null, '작품 데이터가 없습니다.');
    }
    
    $newDocData = $newData[$newDocKey];
    $newDocId = extractDocId($newDocKey);
    
    if (!$newDocId) {
        jsonResponse(false, null, 'R_XXX 형식의 문서 ID를 찾을 수 없습니다.');
    }
    
    // 마스터 JSON 로드
    $masterData = loadMasterJson();
    
    // 이미 동일한 R_XXX를 가진 작품이 있는지 확인
    foreach ($masterData as $docKey => $docData) {
        if (extractDocId($docKey) === $newDocId) {
            jsonResponse(false, null, "{$newDocId} 작품이 이미 존재합니다. 작품 교체 기능을 사용해주세요.");
        }
    }
    
    // 새 작품 추가
    $masterData[$newDocKey] = $newDocData;
    
    // R_XXX 번호순으로 정렬
    $masterData = sortByDocId($masterData);
    
    // 마스터 JSON 저장
    saveMasterJson($masterData);
    
    // 모든 라벨러의 JSON에도 새 작품 추가 (정렬된 상태로)
    $users = loadUsers();
    foreach ($users as $user) {
        addWorkToLabelerJson($user['id'], $newDocKey, $newDocData, $user['nickname']);
    }
    
    jsonResponse(true, [
        'added_doc_id' => $newDocId,
        'doc_key' => $newDocKey
    ], "{$newDocId} 작품이 성공적으로 추가되었습니다.");
}

function handleGetAllProgress() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $users = loadUsers();
    $results = [];
    
    foreach ($users as $user) {
        $labelerData = loadLabelerJson($user['id']);
        if ($labelerData) {
            $progress = calculateProgress($labelerData);
            $results[] = [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'progress' => $progress
            ];
        }
    }
    
    jsonResponse(true, $results);
}

function handleExportResults() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
        exit;
    }
    
    $labelerPath = LABELERS_PATH;
    
    if (!is_dir($labelerPath)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => '라벨러 데이터 폴더가 없습니다.']);
        exit;
    }
    
    // 사용자 정보 로드하여 파일명 매핑 생성
    $users = loadUsers();
    $userMap = [];
    foreach ($users as $user) {
        $userMap[$user['id']] = $user['nickname'];
    }
    
    // ZipArchive 사용 가능 여부 확인
    if (class_exists('ZipArchive')) {
        // ZIP 파일 생성
        $zipFileName = 'labeling_results_' . date('Y-m-d_His') . '.zip';
        $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;
        
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'ZIP 파일을 생성할 수 없습니다.']);
            exit;
        }
        
        // labelers 폴더 내 JSON 파일들을 ZIP에 추가
        $files = glob($labelerPath . '/*.json');
        foreach ($files as $file) {
            $basename = basename($file);
            $labelerId = pathinfo($basename, PATHINFO_FILENAME);
            
            if (isset($userMap[$labelerId])) {
                $newFileName = $userMap[$labelerId] . '.json';
            } else {
                $newFileName = $basename;
            }
            
            $zip->addFile($file, 'labelers/' . $newFileName);
        }
        
        if (file_exists(USERS_JSON)) {
            $zip->addFile(USERS_JSON, 'users.json');
        }
        
        if (file_exists(MASTER_JSON)) {
            $zip->addFile(MASTER_JSON, 'master_passages.json');
        }
        
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        
        readfile($zipFilePath);
        unlink($zipFilePath);
        exit;
    } else {
        // ZipArchive가 없으면 JSON으로 내보내기 (fallback)
        $exportData = [
            'exported_at' => date('Y-m-d H:i:s'),
            'users' => $users,
            'labelers' => []
        ];
        
        $files = glob($labelerPath . '/*.json');
        foreach ($files as $file) {
            $basename = basename($file);
            $labelerId = pathinfo($basename, PATHINFO_FILENAME);
            $content = json_decode(file_get_contents($file), true);
            
            $nickname = isset($userMap[$labelerId]) ? $userMap[$labelerId] : $labelerId;
            $exportData['labelers'][$nickname] = $content;
        }
        
        if (file_exists(MASTER_JSON)) {
            $exportData['master_passages'] = json_decode(file_get_contents(MASTER_JSON), true);
        }
        
        $jsonFileName = 'labeling_results_' . date('Y-m-d_His') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $jsonFileName . '"');
        header('Cache-Control: no-cache, must-revalidate');
        
        echo json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// ========================
// 공지사항 핸들러
// ========================

function handleGetAnnouncements() {
    $announcements = loadAnnouncements();
    // 최신순으로 정렬 (id가 큰 것이 최신)
    usort($announcements, function($a, $b) {
        return $b['id'] - $a['id'];
    });
    jsonResponse(true, $announcements);
}

function handleAddAnnouncement() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($title)) {
        jsonResponse(false, null, '제목을 입력해주세요.');
    }
    
    if (empty($content)) {
        jsonResponse(false, null, '내용을 입력해주세요.');
    }
    
    $announcements = loadAnnouncements();
    
    // 새 공지사항 ID 생성 (가장 큰 ID + 1)
    $maxId = 0;
    foreach ($announcements as $ann) {
        if ($ann['id'] > $maxId) {
            $maxId = $ann['id'];
        }
    }
    
    $newAnnouncement = [
        'id' => $maxId + 1,
        'title' => $title,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $announcements[] = $newAnnouncement;
    saveAnnouncements($announcements);
    
    jsonResponse(true, $newAnnouncement, '공지사항이 등록되었습니다.');
}

function handleDeleteAnnouncement() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $announcementId = $_POST['announcement_id'] ?? '';
    
    if (empty($announcementId)) {
        jsonResponse(false, null, '공지사항 ID가 필요합니다.');
    }
    
    $announcements = loadAnnouncements();
    $announcements = array_filter($announcements, function($ann) use ($announcementId) {
        return $ann['id'] != $announcementId;
    });
    $announcements = array_values($announcements);
    saveAnnouncements($announcements);
    
    jsonResponse(true, null, '공지사항이 삭제되었습니다.');
}

// ========================
// 활동 추적 핸들러
// ========================

/**
 * Heartbeat - 라벨러 활동 시간 업데이트
 * 문장 이동, 페이지 접속 등에서 호출
 */
function handleHeartbeat() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, '로그인이 필요합니다.');
    }
    
    $labelerId = $_SESSION['user_id'];
    $result = updateUserActivity($labelerId);
    
    if ($result) {
        jsonResponse(true, null, '활동 시간이 업데이트되었습니다.');
    } else {
        jsonResponse(false, null, '활동 시간 업데이트에 실패했습니다.');
    }
}

/**
 * 관리자용 - 모든 사용자의 활동 상태 조회
 */
function handleGetUserActivity() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        jsonResponse(false, null, '관리자 권한이 필요합니다.');
    }
    
    $activity = getUsersActivity();
    jsonResponse(true, $activity);
}
?>
