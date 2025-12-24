<?php
/**
 * 샘플링 도구 - 메인 페이지
 */
require_once __DIR__ . '/../config.php';

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>책 샘플링 도구</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 로그인 섹션 -->
    <div id="loginSection" class="login-section panel" style="<?= $isAdmin ? 'display:none;' : '' ?>">
        <h1>🔐 관리자 로그인</h1>
        <p>샘플링 도구는 관리자 전용입니다.</p>
        <form id="loginForm" onsubmit="handleLogin(event)">
            <label for="adminKey">관리자 키</label>
            <input type="password" id="adminKey" placeholder="관리자 키를 입력하세요" required>
            <button type="submit">로그인</button>
        </form>
        <p style="margin-top: 20px;"><a href="../admin.html">← 관리자 페이지로</a></p>
    </div>

    <!-- 메인 컨텐츠 -->
    <div id="mainContent" style="<?= $isAdmin ? '' : 'display:none;' ?>">
        <!-- 헤더 -->
        <div class="header-nav">
            <div><a href="../admin.html">← 관리자 페이지</a></div>
            <h1 style="margin: 0; flex: 1; text-align: center;">📚 책 샘플링 도구</h1>
            <div class="right-section">
                <span id="ocrStatus"><span class="status-indicator loading"></span> 확인 중...</span>
                <button class="secondary" onclick="handleLogout()">로그아웃</button>
            </div>
        </div>

        <!-- 파일 관리 -->
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
            <!-- 좌측: 샘플링 프로세스 -->
            <div class="panel">
                <h2>📄 페이지 샘플링 (Underwood 방식)</h2>
                
                <!-- 스텝 인디케이터 -->
                <div class="steps">
                    <div class="step active" onclick="goToStep(1)">
                        <div class="step-number">1</div>
                        <div class="step-title">페이지 범위</div>
                    </div>
                    <div class="step" onclick="goToStep(2)">
                        <div class="step-number">2</div>
                        <div class="step-title">이미지 업로드</div>
                    </div>
                    <div class="step" onclick="goToStep(3)">
                        <div class="step-number">3</div>
                        <div class="step-title">OCR 분석</div>
                    </div>
                    <div class="step" onclick="goToStep(4)">
                        <div class="step-number">4</div>
                        <div class="step-title">샘플링 결과</div>
                    </div>
                </div>

                <!-- Step 1: 페이지 범위 설정 -->
                <div id="step1Panel" class="step-panel">
                    <h3>Step 1: 페이지 범위 설정</h3>
                    <p class="note">소설 본문의 시작 페이지와 끝 페이지를 입력하세요.</p>
                    
                    <div class="inline-group">
                        <div>
                            <label for="startPage">시작 페이지</label>
                            <input type="number" id="startPage" min="1" value="1">
                        </div>
                        <div>
                            <label for="endPage">끝 페이지</label>
                            <input type="number" id="endPage" min="1" value="100">
                        </div>
                    </div>
                    
                    <div class="inline-group" style="margin-top: 15px;">
                        <div>
                            <label for="maxLinesPerPage">📏 페이지당 행 수 (본문이 가득 찬 페이지 기준) <span style="color: #e74c3c;">*필수</span></label>
                            <input type="number" id="maxLinesPerPage" min="1" value="" placeholder="예: 15" required>
                            <p class="note" style="margin-top: 5px; font-size: 12px;">본문이 가득 찬 일반적인 페이지에서 행 수를 세어 입력하세요.<br>(챕터 시작이나 끝 페이지 제외)<br><strong>⚠️ 다음 단계로 넘어가려면 반드시 입력해야 합니다.</strong></p>
                        </div>
                    </div>
                    
                    <button onclick="calculateSamplePages()">📸 샘플 페이지 계산</button>
                    
                    <div id="samplePagesForOcr" class="result-box">
                        페이지 범위를 입력하고 "샘플 페이지 계산" 버튼을 누르세요.
                    </div>
                    
                    <!-- 직접 페이지 지정 -->
                    <div class="manual-pages-section" style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ccc;">
                        <h4>📝 또는 분석용 페이지 직접 지정</h4>
                        <p class="note">쉼표로 구분된 페이지 번호를 입력하세요 (예: 16, 38, 60, 73, 80)</p>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <input type="text" id="manualPages" placeholder="16, 38, 60, 73, 80, 92, 114, 122..." style="flex: 1;">
                            <button onclick="applyManualPages()">✅ 적용</button>
                        </div>
                    </div>
                    
                    <hr>
                    <button id="goToStep2Btn" class="success" onclick="goToStep(2)" disabled>다음: 이미지 업로드 →</button>
                </div>

                <!-- Step 2: 이미지 업로드 -->
                <div id="step2Panel" class="step-panel" style="display: none;">
                    <h3>Step 2: 페이지 이미지 업로드</h3>
                    <p class="note">샘플 페이지들을 촬영하여 업로드하세요. 평균 글자 수/행 수 추정에 사용됩니다.</p>
                    
                    <!-- 기존 이미지 알림 영역 (요청사항 2) -->
                    <div id="existingImagesAlert" class="existing-images-alert" style="display: none;">
                        <div class="alert-content">
                            <span class="alert-icon">📂</span>
                            <span class="alert-text">서버에 기존 이미지가 <strong id="existingImageCount">0</strong>개 있습니다.</span>
                            <button class="secondary small" onclick="showExistingImages()">보기</button>
                            <button class="danger small" onclick="confirmDeleteExistingImages()">모두 삭제</button>
                        </div>
                    </div>
                    
                    <!-- 다중 파일 업로드용 (숨김) -->
                    <input type="file" id="multiImageFileInput" accept="image/*" multiple style="display: none;">
                    
                    <!-- 다중 업로드 영역 -->
                    <div class="multi-upload-section">
                        <div id="dropZone" class="drop-zone" onclick="triggerMultiImageUpload()">
                            <div class="drop-zone-icon">📁</div>
                            <div class="drop-zone-text">이미지를 드래그하여 놓거나 클릭하여 선택</div>
                            <div class="drop-zone-hint">여러 장의 샘플 페이지 사진을 업로드하세요</div>
                        </div>
                        <div id="multiUploadStatus" class="multi-upload-status" style="display: none;"></div>
                    </div>
                    
                    <div id="imageGrid" class="image-grid">
                        <!-- 동적으로 생성됨 -->
                    </div>
                    
                    <div class="image-actions" style="margin-top: 10px;">
                        <button class="danger small" onclick="clearAllImages()">🗑️ 업로드 이미지 모두 삭제</button>
                    </div>
                    
                    <hr>
                    <button class="secondary" onclick="goToStep(1)">← 이전</button>
                    <button id="goToStep3Btn" class="success" onclick="goToStep(3)">다음: OCR 분석 →</button>
                </div>

                <!-- Step 3: OCR 분석 -->
                <div id="step3Panel" class="step-panel" style="display: none;">
                    <h3>Step 3: OCR 분석</h3>
                    <p class="note">CLOVA OCR을 사용하여 각 페이지의 단어 수와 글자 수를 분석합니다.</p>
                    
                    <button id="runOcrBtn" class="success" onclick="runOcrAnalysis()">🔍 OCR 분석 실행</button>
                    
                    <div id="ocrProgress"></div>
                    <div id="ocrResults" class="result-box">분석 결과가 여기에 표시됩니다.</div>
                    
                    <hr>
                    <button class="secondary" onclick="goToStep(2)">← 이전</button>
                    <button id="goToStep4Btn" class="success" onclick="goToStep(4)" disabled>다음: 샘플링 결과 →</button>
                </div>

                <!-- Step 4: 샘플링 결과 -->
                <div id="step4Panel" class="step-panel" style="display: none;">
                    <h3>Step 4: 최종 샘플링</h3>
                    <p class="note">Underwood 방식: 첫 2개 + 끝 2개 고정, 중간 12개 무작위 추출</p>
                    
                    <button class="success" onclick="generateFinalSampling()">🎯 샘플링 실행</button>
                    
                    <div id="samplingResults"></div>
                    
                    <hr>
                    <button class="secondary" onclick="goToStep(3)">← 이전</button>
                </div>
            </div>

            <!-- 우측: 메타데이터 입력 -->
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
                        <label for="numwords">추정 단어 수</label>
                        <input type="number" id="numwords" min="0" value="0" readonly>
                    </div>
                    <div>
                        <label for="numchars">추정 글자 수</label>
                        <input type="number" id="numchars" min="0" value="0" readonly>
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
                
                <div id="currentSegmentGuide"></div>
                
                <div class="segment-info">
                    <div class="inline-group" style="align-items: center;">
                        <div style="flex: 0 0 auto;">
                            <label for="segmentIdx">세그먼트 인덱스 (idx)</label>
                            <input type="number" id="segmentIdx" min="0" value="0" style="width: 80px;">
                        </div>
                        <div style="flex: 0 0 auto;">
                            <span>저장된 세그먼트 수: <span id="segmentCount">0</span> / 16</span>
                        </div>
                    </div>
                </div>

                <textarea id="textInput" rows="10" placeholder="샘플링한 텍스트를 여기에 입력하세요... (500자 기준)"></textarea>
                
                <div class="char-count" id="charCountDisplay">글자 수: 0자 | 단어 수: 0개</div>

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

            <!-- JSON 출력 -->
            <div class="panel full-width">
                <h2>📄 현재 JSON 상태</h2>
                <button onclick="copyJson()">📋 JSON 복사</button>
                <div id="jsonOutput">JSON이 초기화되지 않았습니다. "새 JSON 초기화" 버튼을 눌러주세요.</div>
            </div>
        </div>
    </div>

    <!-- 모달들 -->
    <div id="fileListModal" class="modal-overlay">
        <div class="modal-content">
            <h3>📂 작업 파일 목록</h3>
            <ul id="fileList" class="file-list"><li>로딩 중...</li></ul>
            <hr>
            <button class="secondary" onclick="closeFileListModal()">닫기</button>
        </div>
    </div>

    <div id="overwriteModal" class="modal-overlay">
        <div class="modal-content">
            <h3>⚠️ 파일 덮어쓰기 확인</h3>
            <p id="overwriteMessage">동일한 파일이 이미 존재합니다. 덮어쓰시겠습니까?</p>
            <button class="danger" onclick="confirmOverwrite()">덮어쓰기</button>
            <button class="secondary" onclick="closeOverwriteModal()">취소</button>
        </div>
    </div>

    <!-- JavaScript 로드 -->
    <script src="js/auth.js"></script>
    <script src="js/files.js"></script>
    <script src="js/sampling.js"></script>
    <script src="js/segments.js"></script>
</body>
</html>
