<?php
/**
 * 샘플링 도구 - 리다이렉트
 * 새로운 sampling/ 폴더로 이동합니다.
 */
header('Location: sampling/index.php');
exit;

/* 
 * 기존 코드는 sampling/ 폴더로 분리되었습니다:
 * - sampling/index.php : 메인 HTML
 * - sampling/api.php : 백엔드 API
 * - sampling/js/*.js : JavaScript
 * - sampling/css/style.css : 스타일
 */

// AJAX 요청 처리
if (isset($_GET['action']) || isset($_POST['action'])) {
    $action = $_GET['action'] ?? $_POST['action'];
    
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
            // WIP 파일 목록 반환
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
            // 수정일 기준 내림차순 정렬
            usort($files, function($a, $b) {
                return strtotime($b['modified']) - strtotime($a['modified']);
            });
            jsonResponse(true, $files);
            break;
            
        case 'load':
            // 특정 WIP 파일 로드
            $filename = $_GET['filename'] ?? '';
            if (empty($filename)) {
                jsonResponse(false, null, '파일명이 필요합니다.');
            }
            $filename = basename($filename); // 보안: 경로 순회 방지
            $filepath = SAMPLING_WIP_PATH . '/' . $filename;
            if (!file_exists($filepath)) {
                jsonResponse(false, null, '파일을 찾을 수 없습니다.');
            }
            $content = file_get_contents($filepath);
            $json = json_decode($content, true);
            if ($json === null) {
                jsonResponse(false, null, 'JSON 파싱 오류');
            }
            jsonResponse(true, $json);
            break;
            
        case 'save':
            // WIP 파일 저장
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['filename']) || !isset($data['content'])) {
                jsonResponse(false, null, '잘못된 요청입니다.');
            }
            $filename = basename($data['filename']); // 보안: 경로 순회 방지
            if (!preg_match('/\.json$/i', $filename)) {
                $filename .= '.json';
            }
            $filepath = SAMPLING_WIP_PATH . '/' . $filename;
            $result = file_put_contents($filepath, json_encode($data['content'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            if ($result === false) {
                jsonResponse(false, null, '파일 저장 실패');
            }
            jsonResponse(true, ['filename' => $filename], '저장 완료');
            break;
            
        case 'check_exists':
            // 파일 존재 여부 확인
            $filename = $_GET['filename'] ?? '';
            if (empty($filename)) {
                jsonResponse(false, null, '파일명이 필요합니다.');
            }
            $filename = basename($filename);
            if (!preg_match('/\.json$/i', $filename)) {
                $filename .= '.json';
            }
            $filepath = SAMPLING_WIP_PATH . '/' . $filename;
            jsonResponse(true, ['exists' => file_exists($filepath), 'filename' => $filename]);
            break;
            
        case 'download':
            // 파일 다운로드
            $filename = $_GET['filename'] ?? '';
            if (empty($filename)) {
                die('파일명이 필요합니다.');
            }
            $filename = basename($filename);
            $filepath = SAMPLING_WIP_PATH . '/' . $filename;
            if (!file_exists($filepath)) {
                die('파일을 찾을 수 없습니다.');
            }
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
            
        case 'delete':
            // WIP 파일 삭제
            $filename = $_GET['filename'] ?? '';
            if (empty($filename)) {
                jsonResponse(false, null, '파일명이 필요합니다.');
            }
            $filename = basename($filename);
            $filepath = SAMPLING_WIP_PATH . '/' . $filename;
            if (!file_exists($filepath)) {
                jsonResponse(false, null, '파일을 찾을 수 없습니다.');
            }
            if (unlink($filepath)) {
                jsonResponse(true, null, '삭제 완료');
            } else {
                jsonResponse(false, null, '삭제 실패');
            }
            break;
            
        default:
            jsonResponse(false, null, '알 수 없는 액션');
    }
    exit;
}

// 세션 상태 확인
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>책 샘플링 도구</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        h2 {
            color: #444;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .full-width {
            grid-column: 1 / -1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="number"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        input[type="number"] {
            width: 120px;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            resize: vertical;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        button.secondary {
            background-color: #6c757d;
        }
        button.secondary:hover {
            background-color: #545b62;
        }
        button.danger {
            background-color: #dc3545;
        }
        button.danger:hover {
            background-color: #c82333;
        }
        button.success {
            background-color: #28a745;
        }
        button.success:hover {
            background-color: #218838;
        }
        /* 로그인 섹션 */
        .login-section {
            max-width: 400px;
            margin: 100px auto;
        }
        .login-section h1 {
            margin-bottom: 20px;
        }
        /* 헤더 네비게이션 */
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .header-nav a {
            color: #007bff;
            text-decoration: none;
            margin-right: 15px;
        }
        .header-nav a:hover {
            text-decoration: underline;
        }
        .header-nav .right-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        /* 모달 스타일 */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .file-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .file-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .file-list li:hover {
            background: #f5f5f5;
        }
        .file-info {
            flex: 1;
            cursor: pointer;
        }
        .file-info .filename {
            font-weight: bold;
            color: #007bff;
        }
        .file-info .meta {
            font-size: 12px;
            color: #666;
        }
        .file-actions {
            display: flex;
            gap: 5px;
        }
        .file-actions button {
            padding: 5px 10px;
            font-size: 12px;
            margin: 0;
        }
        /* 파일 관리 버튼 그룹 */
        .file-management {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .file-management h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .current-file-info {
            background: #d4edda;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            color: #155724;
        }
        .current-file-info.unsaved {
            background: #fff3cd;
            color: #856404;
        }
        .inline-group {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .inline-group > div {
            flex: 1;
            min-width: 100px;
        }
        .result-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            min-height: 60px;
        }
        .result-box.highlight {
            background: #e7f3ff;
            border-color: #007bff;
        }
        .page-list {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            word-break: break-all;
        }
        .char-count {
            font-size: 16px;
            color: #28a745;
            font-weight: bold;
            margin-top: 10px;
        }
        .char-count.warning {
            color: #ffc107;
        }
        .char-count.danger {
            color: #dc3545;
        }
        #jsonOutput {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 500px;
            overflow-y: auto;
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
        }
        .metadata-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .segment-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .segment-info span {
            margin-right: 20px;
            font-weight: bold;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        .note {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        hr {
            border: none;
            border-top: 1px solid #dee2e6;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- 로그인 섹션 -->
    <div id="loginSection" class="login-section panel" style="<?php echo $isAdmin ? 'display:none;' : ''; ?>">
        <h1>🔐 관리자 로그인</h1>
        <p>샘플링 도구는 관리자 전용입니다.</p>
        <form id="loginForm" onsubmit="handleLogin(event)">
            <label for="adminKey">관리자 키</label>
            <input type="password" id="adminKey" placeholder="관리자 키를 입력하세요" required>
            <button type="submit">로그인</button>
        </form>
        <p style="margin-top: 20px;">
            <a href="admin.html">← 관리자 페이지로</a>
        </p>
    </div>

    <!-- 메인 컨텐츠 (로그인 후 표시) -->
    <div id="mainContent" style="<?php echo $isAdmin ? '' : 'display:none;'; ?>">
        <!-- 헤더 네비게이션 -->
        <div class="header-nav">
            <div>
                <a href="admin.html">← 관리자 페이지</a>
            </div>
            <h1 style="margin: 0; flex: 1; text-align: center;">📚 책 샘플링 도구</h1>
            <div class="right-section">
                <span id="currentFileDisplay"></span>
                <button class="secondary" onclick="handleLogout()">로그아웃</button>
            </div>
        </div>

        <!-- 파일 관리 섹션 -->
        <div class="panel full-width file-management">
            <h3>📁 파일 관리</h3>
            <div id="currentFileInfo" class="current-file-info" style="display: none;">
                현재 작업 파일: <strong id="currentFileName">-</strong>
                <span id="unsavedIndicator" style="display: none;"> (저장되지 않은 변경사항 있음)</span>
            </div>
            <button onclick="showFileListModal()">📂 작업 파일 열기</button>
            <button class="success" onclick="saveToServer()">💾 서버에 저장</button>
            <button class="secondary" onclick="downloadCurrentFile()">⬇️ 파일 다운로드</button>
            <button class="danger" onclick="resetAll()">🗑️ 전체 초기화</button>
        </div>

        <div class="container">
        <!-- 페이지 샘플링 섹션 -->
        <div class="panel">
            <h2>📄 페이지 샘플링</h2>
            <p class="note">첫 2구간과 마지막 2구간은 고정, 나머지 12구간을 무작위 추출합니다.</p>
            
            <div class="inline-group">
                <div>
                    <label for="startPage">시작 페이지</label>
                    <input type="number" id="startPage" min="1" value="1">
                </div>
                <div>
                    <label for="endPage">끝 페이지</label>
                    <input type="number" id="endPage" min="1" value="100">
                </div>
                <div>
                    <button onclick="generateSamples()">샘플 생성</button>
                </div>
            </div>

            <div class="result-box">
                <strong>타입 A (시작 1000자가 첫 페이지 내):</strong>
                <p class="note">범위: [시작 ~ 끝]에서 12개 추출</p>
                <div id="resultA" class="page-list">-</div>
            </div>

            <div class="result-box">
                <strong>타입 B (시작 1000자가 첫 페이지 초과):</strong>
                <p class="note">범위: [시작+1 ~ 끝-1]에서 12개 추출</p>
                <div id="resultB" class="page-list">-</div>
            </div>

            <div class="result-box highlight">
                <strong>📋 전체 16개 페이지 (타입 A):</strong>
                <div id="fullListA" class="page-list">-</div>
            </div>

            <div class="result-box highlight">
                <strong>📋 전체 16개 페이지 (타입 B):</strong>
                <div id="fullListB" class="page-list">-</div>
            </div>
        </div>

        <!-- 메타데이터 입력 섹션 -->
        <div class="panel">
            <h2>📝 소설 정보</h2>
            
            <div class="metadata-grid">
                <div>
                    <label for="docid">문서 ID (docid)</label>
                    <input type="text" id="docid" placeholder="예: R_004">
                </div>
                <div>
                    <label for="title">제목 (title)</label>
                    <input type="text" id="title" placeholder="예: 경희">
                </div>
                <div>
                    <label for="author">작가 (author)</label>
                    <input type="text" id="author" placeholder="예: 나혜석">
                </div>
                <div>
                    <label for="source">출처 (source)</label>
                    <input type="text" id="source" placeholder="예: 여자지계">
                </div>
                <div>
                    <label for="originalid">원본 ID (originalid)</label>
                    <input type="text" id="originalid" placeholder="예: 004-나혜석-경희-여자지계">
                </div>
                <div>
                    <label for="numwords">단어 수 (numwords)</label>
                    <input type="number" id="numwords" min="0" value="0">
                </div>
                <div>
                    <label for="numchars">글자 수 (numchars)</label>
                    <input type="number" id="numchars" min="0" value="0">
                </div>
            </div>

            <hr>

            <button onclick="generateFilename()">파일명 자동 생성</button>
            <div>
                <label for="filename">파일명 (JSON 키)</label>
                <input type="text" id="filename" placeholder="예: R-004-나혜석-경희-여자지계">
            </div>

            <button class="success" onclick="initializeJson()">새 JSON 초기화</button>
        </div>

        <!-- 텍스트 입력 섹션 -->
        <div class="panel full-width">
            <h2>✍️ 텍스트 입력</h2>
            
            <div class="segment-info">
                <div class="inline-group" style="align-items: center;">
                    <div style="flex: 0 0 auto;">
                        <label for="segmentIdx">세그먼트 인덱스 (idx; 0부터 시작)</label>
                        <input type="number" id="segmentIdx" min="0" value="0" style="width: 80px;">
                    </div>
                    <div style="flex: 0 0 auto;">
                        <span>저장된 세그먼트 수: <span id="segmentCount">0</span></span>
                    </div>
                </div>
            </div>

            <textarea id="textInput" rows="10" placeholder="샘플링한 텍스트를 여기에 입력하세요..."></textarea>
            
            <div class="char-count" id="charCountDisplay">
                글자 수: 0자 | 단어 수: 0개
            </div>

            <hr>

            <div class="metadata-grid">
                <div class="checkbox-group">
                    <input type="checkbox" id="complete">
                    <label for="complete" style="margin-bottom: 0;">완결 (complete)</label>
                </div>
            </div>

            <div class="metadata-grid">
                <div>
                    <label for="narratedtime">서술 시간 (narratedtime)</label>
                    <input type="text" id="narratedtime" placeholder="null 또는 값 입력">
                </div>
                <div>
                    <label for="ellipsistime">생략 시간 (ellipsistime)</label>
                    <input type="number" id="ellipsistime" value="0">
                </div>
                <div>
                    <label for="subjectivetime">주관 시간 (subjectivetime)</label>
                    <input type="number" id="subjectivetime" value="0">
                </div>
            </div>

            <div class="metadata-grid">
                <div>
                    <label for="ellipsisphrase">생략 구문 (ellipsisphrase)</label>
                    <input type="text" id="ellipsisphrase" placeholder="">
                </div>
                <div>
                    <label for="subjectivephrase">주관 구문 (subjectivephrase)</label>
                    <input type="text" id="subjectivephrase" placeholder="">
                </div>
            </div>

            <hr>

            <button class="success" onclick="saveSegment()">💾 세그먼트 저장</button>
            <button class="secondary" onclick="clearTextInput()">입력 초기화</button>
            <button class="danger" onclick="removeLastSegment()">마지막 세그먼트 삭제</button>
            
            <div class="inline-group" style="margin-top: 15px;">
                <div style="flex: 0 0 auto;">
                    <label for="deleteIdx">삭제할 idx</label>
                    <input type="number" id="deleteIdx" min="0" value="0" style="width: 80px;">
                </div>
                <div style="flex: 0 0 auto;">
                    <button class="danger" onclick="removeSegmentByIdx()">특정 세그먼트 삭제</button>
                </div>
            </div>
        </div>

        <!-- JSON 출력 섹션 -->
        <div class="panel full-width">
            <h2>📄 현재 JSON 상태</h2>
            <button onclick="copyJson()">📋 JSON 복사</button>
            <div id="jsonOutput">JSON이 초기화되지 않았습니다. "새 JSON 초기화" 버튼을 눌러주세요.</div>
        </div>
    </div>
    </div>

    <!-- 파일 목록 모달 -->
    <div id="fileListModal" class="modal-overlay">
        <div class="modal-content">
            <h3>📂 작업 파일 목록</h3>
            <ul id="fileList" class="file-list">
                <li>로딩 중...</li>
            </ul>
            <hr>
            <button class="secondary" onclick="closeFileListModal()">닫기</button>
        </div>
    </div>

    <!-- 덮어쓰기 확인 모달 -->
    <div id="overwriteModal" class="modal-overlay">
        <div class="modal-content">
            <h3>⚠️ 파일 덮어쓰기 확인</h3>
            <p id="overwriteMessage">동일한 파일이 이미 존재합니다. 덮어쓰시겠습니까?</p>
            <button class="danger" onclick="confirmOverwrite()">덮어쓰기</button>
            <button class="secondary" onclick="closeOverwriteModal()">취소</button>
        </div>
    </div>

    <script>
        // 전역 상태
        let currentJson = null;
        let currentServerFilename = null; // 서버에 저장된 파일명
        let hasUnsavedChanges = false;
        let pendingOverwriteCallback = null;

        // ================== 인증 관련 함수 ==================
        
        async function handleLogin(e) {
            e.preventDefault();
            const adminKey = document.getElementById('adminKey').value;
            
            try {
                const response = await fetch('sampling.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=login&admin_key=${encodeURIComponent(adminKey)}`
                });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('loginSection').style.display = 'none';
                    document.getElementById('mainContent').style.display = 'block';
                } else {
                    alert(result.message || '로그인 실패');
                }
            } catch (error) {
                alert('로그인 중 오류 발생: ' + error.message);
            }
        }

        async function handleLogout() {
            if (hasUnsavedChanges && !confirm('저장되지 않은 변경사항이 있습니다. 로그아웃하시겠습니까?')) {
                return;
            }
            
            try {
                await fetch('sampling.php?action=logout');
                document.getElementById('loginSection').style.display = 'block';
                document.getElementById('mainContent').style.display = 'none';
                currentJson = null;
                currentServerFilename = null;
                hasUnsavedChanges = false;
            } catch (error) {
                alert('로그아웃 중 오류 발생');
            }
        }

        // ================== 파일 관리 함수 ==================

        async function loadFileList() {
            try {
                const response = await fetch('sampling.php?action=list');
                const result = await response.json();
                
                if (result.success) {
                    return result.data;
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('파일 목록 로드 실패:', error);
                return [];
            }
        }

        async function showFileListModal() {
            const modal = document.getElementById('fileListModal');
            const fileList = document.getElementById('fileList');
            
            modal.classList.add('active');
            fileList.innerHTML = '<li>로딩 중...</li>';
            
            const files = await loadFileList();
            
            if (files.length === 0) {
                fileList.innerHTML = '<li>저장된 파일이 없습니다.</li>';
            } else {
                fileList.innerHTML = files.map(file => `
                    <li>
                        <div class="file-info" onclick="loadFileFromServer('${file.filename}')">
                            <div class="filename">${file.filename}</div>
                            <div class="meta">수정: ${file.modified} | 크기: ${formatFileSize(file.size)}</div>
                        </div>
                        <div class="file-actions">
                            <button class="secondary" onclick="event.stopPropagation(); downloadFile('${file.filename}')">⬇️</button>
                            <button class="danger" onclick="event.stopPropagation(); deleteFile('${file.filename}')">🗑️</button>
                        </div>
                    </li>
                `).join('');
            }
        }

        function closeFileListModal() {
            document.getElementById('fileListModal').classList.remove('active');
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        async function loadFileFromServer(filename) {
            if (hasUnsavedChanges && !confirm('저장되지 않은 변경사항이 있습니다. 다른 파일을 열시겠습니까?')) {
                return;
            }
            
            try {
                const response = await fetch(`sampling.php?action=load&filename=${encodeURIComponent(filename)}`);
                const result = await response.json();
                
                if (result.success) {
                    currentJson = result.data;
                    currentServerFilename = filename;
                    hasUnsavedChanges = false;
                    
                    // UI 업데이트
                    const key = Object.keys(currentJson)[0];
                    if (key && currentJson[key].metadata) {
                        const meta = currentJson[key].metadata;
                        document.getElementById('docid').value = meta.docid || '';
                        document.getElementById('title').value = meta.title || '';
                        document.getElementById('author').value = meta.author || '';
                        document.getElementById('source').value = meta.source || '';
                        document.getElementById('originalid').value = meta.originalid || '';
                        document.getElementById('numwords').value = meta.numwords || 0;
                        document.getElementById('numchars').value = meta.numchars || 0;
                        document.getElementById('filename').value = key;
                    }
                    
                    updateJsonDisplay();
                    updateSegmentInfo();
                    updateCurrentFileDisplay();
                    closeFileListModal();
                    
                    alert(`파일 "${filename}" 로드 완료!`);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('파일 로드 실패: ' + error.message);
            }
        }

        async function saveToServer(forceOverwrite = false) {
            if (!currentJson) {
                alert('저장할 JSON이 없습니다. 먼저 JSON을 초기화해주세요.');
                return;
            }
            
            const key = Object.keys(currentJson)[0];
            const filename = key.replace(/\.txt$/i, '') + '.json';
            
            // 덮어쓰기 확인 (신규 파일이거나 다른 파일명일 때)
            if (!forceOverwrite && currentServerFilename !== filename) {
                try {
                    const checkResponse = await fetch(`sampling.php?action=check_exists&filename=${encodeURIComponent(filename)}`);
                    const checkResult = await checkResponse.json();
                    
                    if (checkResult.success && checkResult.data.exists) {
                        showOverwriteModal(filename, () => saveToServer(true));
                        return;
                    }
                } catch (error) {
                    console.error('파일 존재 확인 실패:', error);
                }
            }
            
            try {
                const response = await fetch('sampling.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        filename: filename,
                        content: currentJson
                    })
                });
                const result = await response.json();
                
                if (result.success) {
                    currentServerFilename = result.data.filename;
                    hasUnsavedChanges = false;
                    updateCurrentFileDisplay();
                    alert(`서버에 저장 완료: ${result.data.filename}`);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('저장 실패: ' + error.message);
            }
        }

        function showOverwriteModal(filename, callback) {
            document.getElementById('overwriteMessage').textContent = 
                `"${filename}" 파일이 이미 존재합니다. 덮어쓰시겠습니까?`;
            document.getElementById('overwriteModal').classList.add('active');
            pendingOverwriteCallback = callback;
        }

        function closeOverwriteModal() {
            document.getElementById('overwriteModal').classList.remove('active');
            pendingOverwriteCallback = null;
        }

        function confirmOverwrite() {
            closeOverwriteModal();
            if (pendingOverwriteCallback) {
                pendingOverwriteCallback();
            }
        }

        async function downloadFile(filename) {
            window.location.href = `sampling.php?action=download&filename=${encodeURIComponent(filename)}`;
        }

        function downloadCurrentFile() {
            if (!currentJson) {
                alert('다운로드할 JSON이 없습니다.');
                return;
            }
            
            if (currentServerFilename) {
                // 서버에 저장된 파일이 있으면 서버에서 다운로드
                downloadFile(currentServerFilename);
            } else {
                // 없으면 현재 메모리의 JSON을 다운로드
                const key = Object.keys(currentJson)[0];
                const filename = key.replace(/\.txt$/i, '') + '.json';
                const jsonText = JSON.stringify(currentJson, null, 2);
                const blob = new Blob([jsonText], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        }

        async function deleteFile(filename) {
            if (!confirm(`"${filename}" 파일을 삭제하시겠습니까?`)) {
                return;
            }
            
            try {
                const response = await fetch(`sampling.php?action=delete&filename=${encodeURIComponent(filename)}`);
                const result = await response.json();
                
                if (result.success) {
                    if (currentServerFilename === filename) {
                        currentServerFilename = null;
                        updateCurrentFileDisplay();
                    }
                    showFileListModal(); // 목록 새로고침
                    alert('삭제 완료!');
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('삭제 실패: ' + error.message);
            }
        }

        function updateCurrentFileDisplay() {
            const fileInfoDiv = document.getElementById('currentFileInfo');
            const fileNameSpan = document.getElementById('currentFileName');
            const unsavedIndicator = document.getElementById('unsavedIndicator');
            
            if (currentJson) {
                fileInfoDiv.style.display = 'block';
                fileNameSpan.textContent = currentServerFilename || '(저장되지 않음)';
                
                if (hasUnsavedChanges) {
                    fileInfoDiv.classList.add('unsaved');
                    unsavedIndicator.style.display = 'inline';
                } else {
                    fileInfoDiv.classList.remove('unsaved');
                    unsavedIndicator.style.display = 'none';
                }
            } else {
                fileInfoDiv.style.display = 'none';
            }
        }

        function markAsChanged() {
            hasUnsavedChanges = true;
            updateCurrentFileDisplay();
        }

        // ================== 기존 기능들 ==================

        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // 텍스트 입력 실시간 글자 수 카운트
            document.getElementById('textInput').addEventListener('input', updateCharCount);
            
            // 로컬 스토리지에서 이전 상태 복원
            const saved = localStorage.getItem('samplingData');
            if (saved) {
                try {
                    currentJson = JSON.parse(saved);
                    updateJsonDisplay();
                    updateSegmentInfo();
                    updateCurrentFileDisplay();
                } catch (e) {
                    console.error('저장된 데이터 복원 실패:', e);
                }
            }
        });

        // 글자 수 업데이트
        function updateCharCount() {
            const text = document.getElementById('textInput').value;
            const charCount = text.length;
            const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
            
            const display = document.getElementById('charCountDisplay');
            display.textContent = `글자 수: ${charCount}자 | 단어 수: ${wordCount}개`;
            
            // 색상 변경
            display.className = 'char-count';
            if (charCount < 400) {
                display.classList.add('warning');
            } else if (charCount > 600) {
                display.classList.add('danger');
            }
        }

        // 페이지 샘플 생성
        function generateSamples() {
            const start = parseInt(document.getElementById('startPage').value);
            const end = parseInt(document.getElementById('endPage').value);

            if (isNaN(start) || isNaN(end) || start >= end) {
                alert('올바른 페이지 범위를 입력하세요.');
                return;
            }

            // 타입 A: [start ~ end] 범위에서 12개 추출
            const rangeA = [];
            for (let i = start; i <= end; i++) {
                rangeA.push(i);
            }
            const samplesA = getRandomSamples(rangeA, 12);
            
            // 타입 B: [start+1 ~ end-1] 범위에서 12개 추출
            const rangeB = [];
            for (let i = start + 1; i <= end - 1; i++) {
                rangeB.push(i);
            }
            const samplesB = getRandomSamples(rangeB, 12);

            // 결과 표시
            document.getElementById('resultA').textContent = samplesA.length > 0 
                ? samplesA.sort((a, b) => a - b).join(', ') 
                : '범위가 충분하지 않습니다.';
            
            document.getElementById('resultB').textContent = samplesB.length > 0 
                ? samplesB.sort((a, b) => a - b).join(', ') 
                : '범위가 충분하지 않습니다.';

            // 전체 16개 리스트 생성 (첫 2개 + 샘플 12개 + 마지막 2개)
            if (samplesA.length >= 12) {
                const fullA = [start, start + 1, ...samplesA.sort((a, b) => a - b), end - 1, end];
                // 중복 제거 및 정렬
                const uniqueA = [...new Set(fullA)].sort((a, b) => a - b);
                document.getElementById('fullListA').textContent = uniqueA.join(', ');
            } else {
                document.getElementById('fullListA').textContent = '범위가 충분하지 않습니다.';
            }

            if (samplesB.length >= 12) {
                const fullB = [start, start + 1, ...samplesB.sort((a, b) => a - b), end - 1, end];
                // 중복 제거 및 정렬
                const uniqueB = [...new Set(fullB)].sort((a, b) => a - b);
                document.getElementById('fullListB').textContent = uniqueB.join(', ');
            } else {
                document.getElementById('fullListB').textContent = '범위가 충분하지 않습니다.';
            }
        }

        // 무작위 샘플 추출 (Fisher-Yates 셔플 - 균등 분포 보장)
        function getRandomSamples(arr, count) {
            if (arr.length <= count) {
                return [...arr];
            }
            
            const shuffled = [...arr];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled.slice(0, count);
        }

        // 파일명 자동 생성
        function generateFilename() {
            const docid = document.getElementById('docid').value.trim();
            const author = document.getElementById('author').value.trim();
            const title = document.getElementById('title').value.trim();
            const source = document.getElementById('source').value.trim();

            if (!docid || !author || !title || !source) {
                alert('docid, 작가, 제목, 출처를 모두 입력해주세요.');
                return;
            }

            // 각 요소 내부 스페이스를 언더바로 변환
            const docidClean = docid.replace(/\s+/g, '_');
            const authorClean = author.replace(/\s+/g, '_');
            const titleClean = title.replace(/\s+/g, '_');
            const sourceClean = source.replace(/\s+/g, '_');

            const docNum = docid.replace(/\D/g, '');
            // 각 요소를 하이픈으로 연결 (.txt 확장자 제거)
            const filename = `${docidClean}-${authorClean}-${titleClean}-${sourceClean}`;
            document.getElementById('filename').value = filename;
            document.getElementById('originalid').value = `${docNum}_${authorClean}_${titleClean}_${sourceClean}`;
        }

        // JSON 초기화
        async function initializeJson() {
            const filename = document.getElementById('filename').value.trim();
            if (!filename) {
                alert('파일명을 먼저 입력하거나 자동 생성해주세요.');
                return;
            }

            const jsonFilename = filename.replace(/\.txt$/i, '').replace(/\.json$/i, '') + '.json';
            
            // 서버에 파일 존재 여부 확인
            try {
                const checkResponse = await fetch(`sampling.php?action=check_exists&filename=${encodeURIComponent(jsonFilename)}`);
                const checkResult = await checkResponse.json();
                
                if (checkResult.success && checkResult.data.exists) {
                    if (!confirm(`"${jsonFilename}" 파일이 이미 존재합니다. 덮어쓰시겠습니까?`)) {
                        return;
                    }
                }
            } catch (error) {
                console.error('파일 존재 확인 실패:', error);
            }

            const now = new Date().toISOString();
            
            currentJson = {
                [filename]: {
                    metadata: {
                        docid: document.getElementById('docid').value.trim(),
                        title: document.getElementById('title').value.trim(),
                        author: document.getElementById('author').value.trim(),
                        source: document.getElementById('source').value.trim(),
                        originalid: document.getElementById('originalid').value.trim(),
                        numwords: parseInt(document.getElementById('numwords').value) || 0,
                        numchars: parseInt(document.getElementById('numchars').value) || 0,
                        processed_date: now
                    },
                    chunkct: 0,
                    segments: []
                }
            };

            document.getElementById('segmentIdx').value = '0';
            
            // 서버에 즉시 저장
            try {
                const response = await fetch('sampling.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        filename: jsonFilename,
                        content: currentJson
                    })
                });
                const result = await response.json();
                
                if (result.success) {
                    currentServerFilename = result.data.filename;
                    hasUnsavedChanges = false;
                    alert(`새 JSON 파일이 생성되었습니다: ${result.data.filename}`);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('서버 저장 실패: ' + error.message + '\n로컬에만 저장됩니다.');
                hasUnsavedChanges = true;
            }
            
            updateJsonDisplay();
            updateSegmentInfo();
            updateCurrentFileDisplay();
            saveToLocalStorage();
        }

        // 세그먼트 저장
        function saveSegment() {
            if (!currentJson) {
                alert('먼저 JSON을 초기화해주세요.');
                return;
            }

            const text = document.getElementById('textInput').value;
            if (!text.trim()) {
                alert('텍스트를 입력해주세요.');
                return;
            }

            const charCount = text.length;
            const wordCount = text.trim().split(/\s+/).length;
            
            const narratedtimeVal = document.getElementById('narratedtime').value.trim();
            const narratedtime = narratedtimeVal === '' || narratedtimeVal.toLowerCase() === 'null' 
                ? null 
                : narratedtimeVal;

            const segmentIdx = parseInt(document.getElementById('segmentIdx').value) || 0;

            const segment = {
                idx: segmentIdx,
                text: text,
                word_count: wordCount,
                char_count: charCount,
                complete: document.getElementById('complete').checked,
                narratedtime: narratedtime,
                ellipsistime: parseInt(document.getElementById('ellipsistime').value) || 0,
                subjectivetime: parseInt(document.getElementById('subjectivetime').value) || 0,
                ellipsisphrase: document.getElementById('ellipsisphrase').value,
                subjectivephrase: document.getElementById('subjectivephrase').value
            };

            const filename = Object.keys(currentJson)[0];
            currentJson[filename].segments.push(segment);
            currentJson[filename].chunkct = currentJson[filename].segments.length;

            // idx 입력값을 1 증가
            document.getElementById('segmentIdx').value = segmentIdx + 1;
            
            // 입력 필드 초기화
            clearTextInput();
            
            updateJsonDisplay();
            updateSegmentInfo();
            saveToLocalStorage();
            markAsChanged();

            alert(`세그먼트 ${segment.idx} 저장 완료!`);
        }

        // 텍스트 입력 초기화
        function clearTextInput() {
            document.getElementById('textInput').value = '';
            document.getElementById('complete').checked = false;
            document.getElementById('narratedtime').value = '';
            document.getElementById('ellipsistime').value = '0';
            document.getElementById('subjectivetime').value = '0';
            document.getElementById('ellipsisphrase').value = '';
            document.getElementById('subjectivephrase').value = '';
            updateCharCount();
        }

        // 마지막 세그먼트 삭제
        function removeLastSegment() {
            if (!currentJson) {
                alert('초기화된 JSON이 없습니다.');
                return;
            }

            const filename = Object.keys(currentJson)[0];
            if (currentJson[filename].segments.length === 0) {
                alert('삭제할 세그먼트가 없습니다.');
                return;
            }

            if (!confirm('마지막 세그먼트를 삭제하시겠습니까?')) {
                return;
            }

            const removedSegment = currentJson[filename].segments.pop();
            currentJson[filename].chunkct = currentJson[filename].segments.length;
            
            // 삭제된 세그먼트의 idx로 입력값 업데이트
            document.getElementById('segmentIdx').value = removedSegment.idx;

            updateJsonDisplay();
            updateSegmentInfo();
            saveToLocalStorage();
            markAsChanged();
        }

        // 특정 idx 세그먼트 삭제
        function removeSegmentByIdx() {
            if (!currentJson) {
                alert('초기화된 JSON이 없습니다.');
                return;
            }

            const filename = Object.keys(currentJson)[0];
            if (currentJson[filename].segments.length === 0) {
                alert('삭제할 세그먼트가 없습니다.');
                return;
            }

            const targetIdx = parseInt(document.getElementById('deleteIdx').value);
            const segmentIndex = currentJson[filename].segments.findIndex(seg => seg.idx === targetIdx);
            
            if (segmentIndex === -1) {
                alert(`idx가 ${targetIdx}인 세그먼트를 찾을 수 없습니다.`);
                return;
            }

            const segment = currentJson[filename].segments[segmentIndex];
            if (!confirm(`idx ${targetIdx} 세그먼트를 삭제하시겠습니까?\n\n텍스트 미리보기: "${segment.text.substring(0, 50)}..."`)) {
                return;
            }

            currentJson[filename].segments.splice(segmentIndex, 1);
            currentJson[filename].chunkct = currentJson[filename].segments.length;
            
            // 삭제된 idx로 입력값 업데이트
            document.getElementById('segmentIdx').value = targetIdx;

            updateJsonDisplay();
            updateSegmentInfo();
            saveToLocalStorage();
            markAsChanged();
            
            alert(`idx ${targetIdx} 세그먼트가 삭제되었습니다.`);
        }

        // 전체 초기화
        function resetAll() {
            if (hasUnsavedChanges && !confirm('저장되지 않은 변경사항이 있습니다. 모든 데이터를 초기화하시겠습니까?')) {
                return;
            }
            if (!hasUnsavedChanges && !confirm('모든 데이터를 초기화하시겠습니까?')) {
                return;
            }

            currentJson = null;
            currentServerFilename = null;
            hasUnsavedChanges = false;

            // 입력 필드 초기화
            document.getElementById('segmentIdx').value = '0';
            document.getElementById('docid').value = '';
            document.getElementById('title').value = '';
            document.getElementById('author').value = '';
            document.getElementById('source').value = '';
            document.getElementById('originalid').value = '';
            document.getElementById('numwords').value = '0';
            document.getElementById('numchars').value = '0';
            document.getElementById('filename').value = '';
            
            clearTextInput();
            
            document.getElementById('jsonOutput').textContent = 'JSON이 초기화되지 않았습니다. "새 JSON 초기화" 버튼을 눌러주세요.';
            updateSegmentInfo();
            updateCurrentFileDisplay();
            
            localStorage.removeItem('samplingData');
        }

        // JSON 표시 업데이트
        function updateJsonDisplay() {
            if (!currentJson) {
                document.getElementById('jsonOutput').textContent = 'JSON이 초기화되지 않았습니다.';
                return;
            }
            document.getElementById('jsonOutput').textContent = JSON.stringify(currentJson, null, 2);
        }

        // 세그먼트 정보 업데이트
        function updateSegmentInfo() {
            if (currentJson) {
                const filename = Object.keys(currentJson)[0];
                document.getElementById('segmentCount').textContent = currentJson[filename].segments.length;
            } else {
                document.getElementById('segmentCount').textContent = '0';
            }
        }

        // 로컬 스토리지 저장
        function saveToLocalStorage() {
            if (currentJson) {
                localStorage.setItem('samplingData', JSON.stringify(currentJson));
            }
        }

        // JSON 복사
        function copyJson() {
            if (!currentJson) {
                alert('복사할 JSON이 없습니다.');
                return;
            }

            const jsonText = JSON.stringify(currentJson, null, 2);
            navigator.clipboard.writeText(jsonText).then(() => {
                alert('JSON이 클립보드에 복사되었습니다!');
            }).catch(err => {
                // 폴백: textarea 사용
                const textarea = document.createElement('textarea');
                textarea.value = jsonText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('JSON이 클립보드에 복사되었습니다!');
            });
        }
    </script>
</body>
</html>
