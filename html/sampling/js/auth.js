/**
 * 샘플링 도구 JavaScript - Part 1: 전역 상태 및 인증
 */

// 전역 상태
let currentJson = null;
let currentServerFilename = null;
let hasUnsavedChanges = false;
let pendingOverwriteCallback = null;

// 샘플링 관련 상태
let samplingState = {
    step: 1,  // 1: 페이지 정보, 2: 이미지 업로드, 3: OCR 분석, 4: 샘플링 결과
    startPage: 1,
    endPage: 100,
    uploadedImages: [],  // {page, filepath, filename, ocrResult}
    ocrResults: [],
    estimatedCharsPerPage: 0,
    estimatedLinesPerPage: 0,
    samplingPositions: [],  // 최종 샘플링 위치 [{idx, relativePos, page, line}]
};

// API 기본 경로
const API_BASE = 'api.php';

// ==================== 인증 관련 함수 ====================

async function handleLogin(e) {
    e.preventDefault();
    const adminKey = document.getElementById('adminKey').value;
    
    try {
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=login&admin_key=${encodeURIComponent(adminKey)}`
        });
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('loginSection').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
            checkOcrStatus();
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
        await fetch(API_BASE + '?action=logout');
        document.getElementById('loginSection').style.display = 'block';
        document.getElementById('mainContent').style.display = 'none';
        currentJson = null;
        currentServerFilename = null;
        hasUnsavedChanges = false;
    } catch (error) {
        alert('로그아웃 중 오류 발생');
    }
}

// ==================== CLOVA OCR 상태 확인 ====================

async function checkOcrStatus() {
    const statusEl = document.getElementById('ocrStatus');
    if (!statusEl) return;
    
    statusEl.innerHTML = '<span class="status-indicator loading"></span> OCR 상태 확인 중...';
    
    try {
        const response = await fetch(API_BASE + '?action=check_ocr');
        const result = await response.json();
        
        if (result.success && result.data.connected) {
            statusEl.innerHTML = '<span class="status-indicator connected"></span> CLOVA OCR 연결됨';
        } else {
            const errorMsg = result.data?.error || 'API 키가 설정되지 않음';
            statusEl.innerHTML = `<span class="status-indicator disconnected"></span> OCR 미설정 <span class="note">(${errorMsg})</span>`;
        }
    } catch (error) {
        statusEl.innerHTML = '<span class="status-indicator disconnected"></span> OCR 상태 확인 오류';
    }
}
