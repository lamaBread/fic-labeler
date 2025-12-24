/**
 * 샘플링 도구 JavaScript - Part 3: 페이지 샘플링 (OCR 기반)
 */

// ==================== DB 파일 관리 함수 ====================

// DB 파일 로드 (중간 진행 상태)
async function loadDbFile(docid) {
    if (!docid) return null;
    
    try {
        const response = await fetch(`${API_BASE}?action=load_db&docid=${encodeURIComponent(docid)}`);
        const result = await response.json();
        
        if (result.success && result.data.exists) {
            return result.data.data;
        }
        return null;
    } catch (error) {
        console.error('DB 파일 로드 실패:', error);
        return null;
    }
}

// DB 파일 저장 (중간 진행 상태)
async function saveDbFile(docid, content) {
    if (!docid) return false;
    
    try {
        const response = await fetch(API_BASE + '?action=save_db', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ docid: docid, content: content })
        });
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('DB 파일 저장 실패:', error);
        return false;
    }
}

// 현재 진행 상태를 DB에 저장
async function saveCurrentProgressToDb(currentStep) {
    const docid = document.getElementById('docid').value;
    if (!docid) return false;
    
    const dbContent = {
        current_step: currentStep,
        timestamp: new Date().toISOString(),
        state: {
            startPage: samplingState.startPage,
            endPage: samplingState.endPage,
            samplePagesForOcr: samplingState.samplePagesForOcr || [],
            samplingMethod: samplingState.samplingMethod || null,
            numStrata: samplingState.numStrata || null,
            maxLinesPerPage: samplingState.maxLinesPerPage || parseInt(document.getElementById('maxLinesPerPage').value) || 0,
            uploadedImages: samplingState.uploadedImages || [],
            ocrResults: samplingState.ocrResults || [],
            estimatedCharsPerPage: samplingState.estimatedCharsPerPage || 0,
            estimatedWordsPerPage: samplingState.estimatedWordsPerPage || 0,
            confidenceIntervals: samplingState.confidenceIntervals || null,
            samplingPositions: samplingState.samplingPositions || []
        }
    };
    
    return await saveDbFile(docid, dbContent);
}

// DB에서 진행 상태 복원
async function restoreProgressFromDb(docid) {
    const dbData = await loadDbFile(docid);
    if (!dbData) return null;
    
    return dbData;
}

// 서버에 이미지가 있는지 확인
async function checkServerImages(docid) {
    if (!docid) return { exists: false, images: [], count: 0 };
    
    try {
        const response = await fetch(`${API_BASE}?action=check_images&docid=${encodeURIComponent(docid)}`);
        const result = await response.json();
        
        if (result.success) {
            return result.data;
        }
        return { exists: false, images: [], count: 0 };
    } catch (error) {
        console.error('이미지 확인 실패:', error);
        return { exists: false, images: [], count: 0 };
    }
}

// 서버의 기존 이미지 삭제
async function deleteServerImages(docid) {
    if (!docid) return false;
    
    try {
        const response = await fetch(`${API_BASE}?action=delete_images&docid=${encodeURIComponent(docid)}`);
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('이미지 삭제 실패:', error);
        return false;
    }
}

// Step 2로 이동 시 기존 이미지 확인 (요청사항 2)
async function checkAndShowExistingImages() {
    const docid = document.getElementById('docid').value;
    if (!docid) return;
    
    const alertEl = document.getElementById('existingImagesAlert');
    const countEl = document.getElementById('existingImageCount');
    
    const result = await checkServerImages(docid);
    
    if (result.exists && result.count > 0) {
        alertEl.style.display = 'block';
        countEl.textContent = result.count;
        
        // 전역에 저장 (모달에서 사용)
        window._existingImages = result.images;
    } else {
        alertEl.style.display = 'none';
        window._existingImages = [];
    }
}

// 기존 이미지 보기 모달
function showExistingImages() {
    const images = window._existingImages || [];
    const docid = document.getElementById('docid').value;
    
    if (images.length === 0) {
        alert('표시할 이미지가 없습니다.');
        return;
    }
    
    // 간단한 모달 생성
    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.id = 'existingImagesModal';
    modal.innerHTML = `
        <div class="modal-content existing-images-modal-content">
            <h3>📂 서버의 기존 이미지 (${images.length}개)</h3>
            <div class="existing-images-grid">
                ${images.map(img => `
                    <div class="existing-img-item">
                        <img class="img-thumb" src="../data/sampling_images/${docid}/${img.filename}" 
                             alt="${img.filename}" title="${img.filename}">
                        <div class="img-name" style="font-size:10px; text-align:center; margin-top:3px;">${img.filename}</div>
                    </div>
                `).join('')}
            </div>
            <hr>
            <button class="secondary" onclick="closeExistingImagesModal()">닫기</button>
            <button class="danger" onclick="closeExistingImagesModal(); confirmDeleteExistingImages();">모두 삭제</button>
        </div>
    `;
    document.body.appendChild(modal);
}

// 기존 이미지 모달 닫기
function closeExistingImagesModal() {
    const modal = document.getElementById('existingImagesModal');
    if (modal) modal.remove();
}

// 기존 이미지 삭제 확인
async function confirmDeleteExistingImages() {
    const docid = document.getElementById('docid').value;
    const images = window._existingImages || [];
    
    if (images.length === 0) {
        alert('삭제할 이미지가 없습니다.');
        return;
    }
    
    if (!confirm(`서버의 기존 이미지 ${images.length}개를 모두 삭제하시겠습니까?\n\n⚠️ 이 작업은 되돌릴 수 없습니다.`)) {
        return;
    }
    
    const success = await deleteServerImages(docid);
    
    if (success) {
        alert('기존 이미지가 삭제되었습니다.');
        
        // 알림 숨기기
        document.getElementById('existingImagesAlert').style.display = 'none';
        window._existingImages = [];
        
        // 업로드된 이미지 상태도 초기화
        samplingState.uploadedImages = [];
        updateImageUploadUI();
    } else {
        alert('이미지 삭제에 실패했습니다.');
    }
}

// ==================== 페이지 샘플링 함수 ====================

// Fisher-Yates 셔플 (균등 분포 보장)
function fisherYatesShuffle(arr) {
    const shuffled = [...arr];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
}

// 무작위 샘플 추출 (비복원 추출)
function getRandomSamples(arr, count) {
    if (arr.length <= count) return [...arr];
    return fisherYatesShuffle(arr).slice(0, count);
}

// t-분포 임계값 테이블 (양측 95% 신뢰구간)
const T_TABLE_95 = {
    1: 12.706, 2: 4.303, 3: 3.182, 4: 2.776, 5: 2.571,
    6: 2.447, 7: 2.365, 8: 2.306, 9: 2.262, 10: 2.228,
    11: 2.201, 12: 2.179, 13: 2.160, 14: 2.145, 15: 2.131,
    16: 2.120, 17: 2.110, 18: 2.101, 19: 2.093, 20: 2.086,
    25: 2.060, 30: 2.042, 40: 2.021, 50: 2.009, 100: 1.984
};

function getTValue(df) {
    if (T_TABLE_95[df]) return T_TABLE_95[df];
    // 근사값 (df > 30)
    if (df > 100) return 1.96;
    // 보간
    const keys = Object.keys(T_TABLE_95).map(Number).sort((a, b) => a - b);
    for (let i = 0; i < keys.length - 1; i++) {
        if (df > keys[i] && df < keys[i + 1]) {
            const ratio = (df - keys[i]) / (keys[i + 1] - keys[i]);
            return T_TABLE_95[keys[i]] * (1 - ratio) + T_TABLE_95[keys[i + 1]] * ratio;
        }
    }
    return 2.0;
}

// 신뢰구간 계산 함수
function calculateConfidenceInterval(values, confidence = 0.95) {
    const n = values.length;
    if (n < 2) return { mean: values[0] || 0, std: 0, se: 0, ci_lower: values[0] || 0, ci_upper: values[0] || 0, margin_of_error: 0, relative_error: 'N/A' };
    
    const mean = values.reduce((a, b) => a + b, 0) / n;
    const variance = values.reduce((sum, x) => sum + Math.pow(x - mean, 2), 0) / (n - 1);
    const std = Math.sqrt(variance);
    const se = std / Math.sqrt(n);
    
    // t-분포 임계값
    const tCritical = getTValue(n - 1);
    const marginOfError = tCritical * se;
    
    return {
        mean: Math.round(mean),
        std: Math.round(std * 10) / 10,
        se: Math.round(se * 10) / 10,
        ci_lower: Math.round(mean - marginOfError),
        ci_upper: Math.round(mean + marginOfError),
        margin_of_error: Math.round(marginOfError),
        relative_error: mean > 0 ? (marginOfError / mean * 100).toFixed(1) + '%' : 'N/A',
        n: n
    };
}

// 층화 무작위 추출 (Stratified Random Sampling)
function stratifiedRandomSampling(startPage, endPage, totalSamples) {
    const totalPages = endPage - startPage + 1;
    const samplePages = [];
    
    // 층(strata) 수 결정: 샘플 수의 절반 (각 층에서 2개씩 선택하거나, 1개씩 선택)
    const numStrata = Math.min(totalSamples, Math.max(5, Math.floor(totalSamples / 2)));
    const samplesPerStratum = Math.max(1, Math.floor(totalSamples / numStrata));
    const strataSize = Math.floor(totalPages / numStrata);
    
    for (let i = 0; i < numStrata; i++) {
        const strataStart = startPage + i * strataSize;
        const strataEnd = (i === numStrata - 1) ? endPage : strataStart + strataSize - 1;
        const strataRange = strataEnd - strataStart + 1;
        
        // 각 층에서 무작위로 samplesPerStratum개 선택
        const strataPages = Array.from({ length: strataRange }, (_, j) => strataStart + j);
        const selected = getRandomSamples(strataPages, samplesPerStratum);
        samplePages.push(...selected);
    }
    
    // 정렬 후 반환
    return samplePages.sort((a, b) => a - b);
}

// Step 1: 페이지 범위 설정 및 샘플링용 페이지 산출
function calculateSamplePages() {
    const startPage = parseInt(document.getElementById('startPage').value);
    const endPage = parseInt(document.getElementById('endPage').value);
    
    if (isNaN(startPage) || isNaN(endPage) || startPage >= endPage) {
        alert('올바른 페이지 범위를 입력하세요.');
        return;
    }
    
    samplingState.startPage = startPage;
    samplingState.endPage = endPage;
    
    const totalPages = endPage - startPage + 1;
    
    // 개선된 샘플 크기: 최소 10개, 최대 20개, sqrt(totalPages) 기반
    // 통계적으로 유효한 추정을 위해 샘플 수 증가
    const sampleCount = Math.min(20, Math.max(10, Math.ceil(Math.sqrt(totalPages))));
    
    // 층화 무작위 추출 적용
    const samplePages = stratifiedRandomSampling(startPage, endPage, sampleCount);
    
    // 층 정보 계산
    const numStrata = Math.min(sampleCount, Math.max(5, Math.floor(sampleCount / 2)));
    const strataSize = Math.floor(totalPages / numStrata);
    
    // 결과 표시
    const resultEl = document.getElementById('samplePagesForOcr');
    resultEl.innerHTML = `
        <strong>📸 OCR 분석용 페이지 (${samplePages.length}개):</strong>
        <div class="page-list">${samplePages.join(', ')}</div>
        <div class="sampling-info">
            <p class="note">📊 <strong>층화 무작위 추출</strong> 적용</p>
            <ul class="sampling-details">
                <li>총 페이지: ${totalPages}쪽</li>
                <li>층(Strata) 수: ${numStrata}개 (각 ~${strataSize}쪽)</li>
                <li>샘플 수: ${samplePages.length}개 (통계적 유효성 확보)</li>
            </ul>
            <p class="note">위 페이지들의 사진을 촬영하여 업로드하세요.</p>
        </div>
    `;
    
    // 샘플 페이지 저장
    samplingState.samplePagesForOcr = samplePages;
    samplingState.samplingMethod = 'stratified_random';
    samplingState.numStrata = numStrata;
    
    // 메타데이터에 즉시 기록
    saveSamplePagesMetadata(samplePages, 'stratified_random', numStrata);
    
    // 이미지 업로드 UI 업데이트
    updateImageUploadUI();
    
    // Step 2로 이동 가능하도록 버튼 활성화
    document.getElementById('goToStep2Btn').disabled = false;
}

// 직접 페이지 번호 입력으로 분석용 페이지 지정
function applyManualPages() {
    const input = document.getElementById('manualPages').value.trim();
    
    if (!input) {
        alert('페이지 번호를 입력해주세요.');
        return;
    }
    
    // 시작/끝 페이지는 사용자가 직접 입력한 값 사용
    const startPage = parseInt(document.getElementById('startPage').value);
    const endPage = parseInt(document.getElementById('endPage').value);
    
    if (isNaN(startPage) || isNaN(endPage) || startPage >= endPage) {
        alert('먼저 위에서 책 본문의 시작 페이지와 끝 페이지를 올바르게 입력해주세요.');
        return;
    }
    
    // 쉼표로 구분된 숫자 파싱 (공백 제거)
    const pages = input.split(',')
        .map(s => parseInt(s.trim(), 10))
        .filter(n => !isNaN(n) && n > 0);
    
    if (pages.length === 0) {
        alert('유효한 페이지 번호가 없습니다. 쉼표로 구분된 숫자를 입력해주세요.\n예: 16, 38, 60, 73, 80');
        return;
    }
    
    // 중복 제거 및 정렬
    const uniquePages = [...new Set(pages)].sort((a, b) => a - b);
    
    // 페이지 범위 검증
    const outOfRange = uniquePages.filter(p => p < startPage || p > endPage);
    if (outOfRange.length > 0) {
        const proceed = confirm(
            `다음 페이지가 지정된 범위(${startPage}~${endPage}) 밖에 있습니다:\n${outOfRange.join(', ')}\n\n그래도 계속하시겠습니까?`
        );
        if (!proceed) return;
    }
    
    samplingState.startPage = startPage;
    samplingState.endPage = endPage;
    samplingState.samplePagesForOcr = uniquePages;
    samplingState.samplingMethod = 'manual';
    samplingState.numStrata = null;
    
    // 메타데이터에 즉시 기록
    saveSamplePagesMetadata(uniquePages, 'manual', null);
    
    // 결과 표시
    const resultEl = document.getElementById('samplePagesForOcr');
    resultEl.innerHTML = `
        <strong>📸 OCR 분석용 페이지 (${uniquePages.length}개 - 직접 지정):</strong>
        <div class="page-list">${uniquePages.join(', ')}</div>
        <div class="sampling-info">
            <p class="note">📝 <strong>직접 지정</strong> 방식</p>
            <ul class="sampling-details">
                <li>책 본문 범위: ${startPage} ~ ${endPage}쪽 (총 ${endPage - startPage + 1}쪽)</li>
                <li>지정된 샘플 페이지: ${uniquePages.length}개</li>
            </ul>
            <p class="note">위 페이지들의 사진을 촬영하여 업로드하세요.</p>
        </div>
    `;
    
    // 이미지 업로드 UI 업데이트
    updateImageUploadUI();
    
    // Step 2로 이동 가능하도록 버튼 활성화
    document.getElementById('goToStep2Btn').disabled = false;
}

// 샘플 페이지 메타데이터 즉시 저장 (페이지 계산 직후 호출)
async function saveSamplePagesMetadata(samplePages, method, numStrata) {
    if (!currentJson) return;
    
    const key = Object.keys(currentJson)[0];
    if (!key) return;
    
    const startPage = samplingState.startPage;
    const endPage = samplingState.endPage;
    const totalPages = endPage - startPage + 1;
    const maxLinesPerPage = parseInt(document.getElementById('maxLinesPerPage').value) || null;
    
    // metadata에 기록 (ocr_pages 포함하여 복원 가능하도록)
    currentJson[key].metadata.ocr_pages = samplePages;
    currentJson[key].metadata.ocr_sampling_method = method;
    currentJson[key].metadata.ocr_sample_count = samplePages.length;
    currentJson[key].metadata.page_range = {
        start: startPage,
        end: endPage,
        total: totalPages
    };
    if (numStrata) {
        currentJson[key].metadata.ocr_num_strata = numStrata;
    }
    // 페이지당 행 수도 함께 저장 (입력된 경우)
    if (maxLinesPerPage && maxLinesPerPage > 0) {
        currentJson[key].metadata.lines_per_page = maxLinesPerPage;
        samplingState.maxLinesPerPage = maxLinesPerPage;
    }
    currentJson[key].metadata.pages_calculated_date = new Date().toISOString();
    
    updateJsonDisplay();
    markAsChanged();
    
    // DB 파일에도 Step 1 완료 상태 저장
    await saveCurrentProgressToDb(1);
}

// 이미지 업로드 UI 업데이트 (단순화 - 페이지 매핑 없음)
function updateImageUploadUI() {
    const gridEl = document.getElementById('imageGrid');
    if (!gridEl) return;
    
    const images = samplingState.uploadedImages || [];
    
    if (images.length === 0) {
        gridEl.innerHTML = '<p class="note">업로드된 이미지가 없습니다.</p>';
        return;
    }
    
    gridEl.innerHTML = images.map((img, idx) => {
        const statusClass = img.analyzing ? 'analyzing' : (img.ocrResult ? 'success' : 'pending');
        const statusText = img.analyzing ? '🔄 분석 중...' : 
                          (img.ocrResult ? `${img.ocrResult.word_count}단어 / ${img.ocrResult.char_count}자` : '분석 대기');
        const displayName = img.originalName ? img.originalName.substring(0, 15) + (img.originalName.length > 15 ? '...' : '') : `이미지 ${idx + 1}`;
        return `
            <div class="image-item ${img.analyzing ? 'analyzing' : ''}" data-index="${idx}">
                <img src="../data/sampling_images/${document.getElementById('docid').value || 'temp'}/${img.filename}" 
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📄</text></svg>'">
                <div class="page-label" title="${img.originalName || img.filename}">${displayName}</div>
                <div class="ocr-status ${statusClass}">
                    ${statusText}
                </div>
                <button class="remove-btn" onclick="removeImage(${idx})">×</button>
            </div>
        `;
    }).join('');
}

// 이미지 제거 (인덱스 기반)
function removeImage(index) {
    samplingState.uploadedImages.splice(index, 1);
    updateImageUploadUI();
}

// 모든 이미지 초기화
function clearAllImages() {
    if (samplingState.uploadedImages.length === 0) {
        alert('삭제할 이미지가 없습니다.');
        return;
    }
    if (!confirm(`업로드된 ${samplingState.uploadedImages.length}개의 이미지를 모두 삭제하시겠습니까?`)) {
        return;
    }
    samplingState.uploadedImages = [];
    updateImageUploadUI();
}

// OCR 분석 실행
async function runOcrAnalysis() {
    const images = samplingState.uploadedImages;
    if (images.length === 0) {
        alert('분석할 이미지가 없습니다.');
        return;
    }
    
    const progressEl = document.getElementById('ocrProgress');
    const resultEl = document.getElementById('ocrResults');
    
    // 초기 테이블 표시 (모든 이미지 대기 상태)
    progressEl.innerHTML = `
        <div class="progress-bar"><div class="progress" style="width: 0%"></div></div>
        <div class="progress-text">0 / ${images.length} 분석 중...</div>
        <div class="current-analysis">
            <div class="analyzing-image-container" id="analyzingImageContainer" style="display: none;">
                <h4>🔍 현재 분석 중인 이미지</h4>
                <div class="analyzing-image-wrapper">
                    <img id="currentAnalyzingImage" src="" alt="분석 중인 이미지">
                    <div class="analyzing-overlay"><span class="spinner"></span></div>
                </div>
                <div id="analyzingImageLabel" class="analyzing-label">이미지 1</div>
            </div>
        </div>
    `;
    
    // 실시간 결과 테이블 초기화
    resultEl.innerHTML = `
        <div class="result-box">
            <h4>📊 OCR 분석 진행 상황</h4>
            <table class="sampling-table" id="ocrResultTable">
                <thead>
                    <tr><th>#</th><th>상태</th><th>단어 수</th><th>글자 수</th></tr>
                </thead>
                <tbody>
                    ${images.map((_, i) => `
                        <tr id="ocrRow${i}" class="pending-row">
                            <td>${i + 1}</td>
                            <td><span class="status-badge pending">대기</span></td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    let completed = 0;
    const results = [];
    const analyzingContainer = document.getElementById('analyzingImageContainer');
    const analyzingImage = document.getElementById('currentAnalyzingImage');
    const analyzingLabel = document.getElementById('analyzingImageLabel');
    
    for (let i = 0; i < images.length; i++) {
        const img = images[i];
        const docid = document.getElementById('docid').value || 'temp';
        const row = document.getElementById(`ocrRow${i}`);
        
        // 현재 분석 중인 이미지 표시
        img.analyzing = true;
        analyzingContainer.style.display = 'block';
        analyzingImage.src = `../data/sampling_images/${docid}/${img.filename}`;
        analyzingLabel.textContent = `이미지 ${i + 1} / ${images.length}`;
        
        // 테이블 행 상태 업데이트 (분석 중)
        row.classList.remove('pending-row');
        row.classList.add('analyzing-row');
        row.querySelector('.status-badge').className = 'status-badge analyzing';
        row.querySelector('.status-badge').innerHTML = '<span class="mini-spinner"></span> 분석 중';
        
        // 이미지 그리드 업데이트
        updateImageUploadUI();
        
        try {
            const response = await fetch(API_BASE + '?action=ocr_analyze', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image_path: img.filepath })
            });
            const result = await response.json();
            
            img.analyzing = false;
            
            if (result.success) {
                img.ocrResult = result.data;
                results.push({ index: i + 1, ...result.data });
                
                // 테이블 행 즉시 업데이트 (성공)
                row.classList.remove('analyzing-row');
                row.classList.add('success-row');
                row.innerHTML = `
                    <td>${i + 1}</td>
                    <td><span class="status-badge success">✅ 완료</span></td>
                    <td>${result.data.word_count}</td>
                    <td>${result.data.char_count}</td>
                `;
            } else {
                results.push({ index: i + 1, error: result.message });
                
                // 테이블 행 업데이트 (실패)
                row.classList.remove('analyzing-row');
                row.classList.add('error-row');
                row.innerHTML = `
                    <td>${i + 1}</td>
                    <td><span class="status-badge error">❌ 실패</span></td>
                    <td colspan="2" class="error-message">${result.message || '분석 실패'}</td>
                `;
            }
        } catch (error) {
            img.analyzing = false;
            results.push({ index: i + 1, error: error.message });
            
            // 테이블 행 업데이트 (오류)
            row.classList.remove('analyzing-row');
            row.classList.add('error-row');
            row.innerHTML = `
                <td>${i + 1}</td>
                <td><span class="status-badge error">❌ 오류</span></td>
                <td colspan="2" class="error-message">${error.message}</td>
            `;
        }
        
        // 이미지 그리드 업데이트
        updateImageUploadUI();
        
        completed++;
        const percent = Math.round((completed / images.length) * 100);
        progressEl.querySelector('.progress').style.width = percent + '%';
        progressEl.querySelector('.progress-text').textContent = `${completed} / ${images.length} 분석 완료`;
    }
    
    // 분석 완료 - 이미지 미리보기 숨김
    analyzingContainer.style.display = 'none';
    
    // 결과 계산 (신뢰구간 포함)
    const validResults = results.filter(r => !r.error);
    if (validResults.length > 0) {
        // 신뢰구간 계산
        const charValues = validResults.map(r => r.char_count);
        const wordValues = validResults.map(r => r.word_count);
        
        const charCI = calculateConfidenceInterval(charValues);
        const wordCI = calculateConfidenceInterval(wordValues);
        
        // Step 1에서 입력한 최대 행 숫자 가져오기
        const maxLinesPerPage = parseInt(document.getElementById('maxLinesPerPage').value) || 0;
        
        samplingState.estimatedCharsPerPage = charCI.mean;
        samplingState.estimatedWordsPerPage = wordCI.mean;
        samplingState.maxLinesPerPage = maxLinesPerPage;
        samplingState.ocrResults = results;
        samplingState.confidenceIntervals = { chars: charCI, words: wordCI };
        
        // 통계적 유효성 평가
        const isStatisticallyValid = charCI.relative_error !== 'N/A' && parseFloat(charCI.relative_error) < 15;
        const validityClass = isStatisticallyValid ? 'valid' : 'warning';
        const validityIcon = isStatisticallyValid ? '✅' : '⚠️';
        const validityText = isStatisticallyValid 
            ? '통계적으로 유효한 추정입니다 (오차 < 15%)' 
            : '오차가 다소 큽니다. 더 많은 샘플을 권장합니다.';
        
        resultEl.innerHTML = `
            <div class="result-box success">
                <h4>📊 OCR 분석 결과 (n=${validResults.length})</h4>
                <table class="sampling-table" id="ocrResultTable">
                    <thead>
                        <tr><th>#</th><th>단어 수</th><th>글자 수</th><th>재분석</th></tr>
                    </thead>
                    <tbody>
                        ${images.map((img, i) => {
                            const r = img.ocrResult;
                            if (r) {
                                return `<tr id="ocrRow${i}" class="success-row">
                                    <td>${i + 1}</td>
                                    <td>${r.word_count}</td>
                                    <td>${r.char_count}</td>
                                    <td><button class="small-btn" onclick="reanalyzeImage(${i})">🔄</button></td>
                                </tr>`;
                            } else {
                                return `<tr id="ocrRow${i}" class="error-row">
                                    <td>${i + 1}</td>
                                    <td colspan="2" class="error-message">분석 실패</td>
                                    <td><button class="small-btn" onclick="reanalyzeImage(${i})">🔄</button></td>
                                </tr>`;
                            }
                        }).join('')}
                    </tbody>
                </table>
                
                <!-- 특정 이미지 재분석 섹션 -->
                <div class="reanalyze-section">
                    <h4>🔄 특정 이미지 재분석</h4>
                    <div class="reanalyze-controls">
                        <label for="reanalyzeIndex">이미지 번호:</label>
                        <input type="number" id="reanalyzeIndex" min="1" max="${images.length}" value="1" style="width: 80px;">
                        <button onclick="reanalyzeImage(parseInt(document.getElementById('reanalyzeIndex').value) - 1)">🔍 재분석 실행</button>
                    </div>
                    <div id="reanalyzePreview"></div>
                </div>
                
                <hr>
                <div id="ocrStatsSection">
                    <h4>📈 통계 분석 결과 (95% 신뢰구간)</h4>
                    <table class="stats-table">
                        <tr>
                            <th>항목</th>
                            <th>평균</th>
                            <th>표준편차</th>
                            <th>95% CI</th>
                            <th>상대오차</th>
                        </tr>
                        <tr>
                            <td>페이지당 단어 수</td>
                            <td><strong>${wordCI.mean}개</strong></td>
                            <td>±${wordCI.std}</td>
                            <td>[${wordCI.ci_lower}, ${wordCI.ci_upper}]</td>
                            <td class="${parseFloat(wordCI.relative_error) < 15 ? 'good' : 'warn'}">${wordCI.relative_error}</td>
                        </tr>
                        <tr>
                            <td>페이지당 글자 수</td>
                            <td><strong>${charCI.mean}자</strong></td>
                            <td>±${charCI.std}</td>
                            <td>[${charCI.ci_lower}, ${charCI.ci_upper}]</td>
                            <td class="${parseFloat(charCI.relative_error) < 15 ? 'good' : 'warn'}">${charCI.relative_error}</td>
                        </tr>
                    </table>
                    ${maxLinesPerPage > 0 ? `
                    <div class="manual-lines-info" style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                        <strong>📏 수동 입력 행 수:</strong> ${maxLinesPerPage}행/페이지 (본문 가득 찬 페이지 기준)
                    </div>
                    ` : `
                    <div class="manual-lines-warning" style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404;">
                        ⚠️ Step 1에서 '페이지당 행 수'를 입력하지 않았습니다.
                    </div>
                    `}
                    <div class="validity-indicator ${validityClass}">
                        ${validityIcon} ${validityText}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('goToStep4Btn').disabled = false;
        updateImageUploadUI();
        
        // OCR 분석 결과를 메타데이터에 저장
        saveOcrMetadata(validResults.length);
    } else {
        resultEl.innerHTML = `<div class="result-box warning">OCR 분석에 실패했습니다. 이미지를 다시 확인해주세요.</div>`;
    }
}

// 단일 이미지 재분석
async function reanalyzeImage(index) {
    const images = samplingState.uploadedImages;
    if (index < 0 || index >= images.length) {
        alert('유효하지 않은 이미지 번호입니다.');
        return;
    }
    
    const img = images[index];
    const docid = document.getElementById('docid').value || 'temp';
    const row = document.getElementById(`ocrRow${index}`);
    const previewContainer = document.getElementById('reanalyzePreview');
    
    // 미리보기 표시
    if (previewContainer) {
        previewContainer.innerHTML = `
            <div class="analyzing-image-container active">
                <h4>🔍 재분석 중: 이미지 ${index + 1}</h4>
                <div class="analyzing-image-wrapper">
                    <img src="../data/sampling_images/${docid}/${img.filename}" alt="재분석 중인 이미지">
                    <div class="analyzing-overlay"><span class="spinner"></span></div>
                </div>
            </div>
        `;
    }
    
    // 상태 업데이트
    img.analyzing = true;
    if (row) {
        row.classList.remove('success-row', 'error-row', 'pending-row');
        row.classList.add('analyzing-row');
        row.querySelector('td:nth-child(2)').innerHTML = '<span class="status-badge analyzing"><span class="mini-spinner"></span> 재분석 중</span>';
    }
    updateImageUploadUI();
    
    try {
        const response = await fetch(API_BASE + '?action=ocr_analyze', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image_path: img.filepath })
        });
        const result = await response.json();
        
        img.analyzing = false;
        
        if (result.success) {
            img.ocrResult = result.data;
            
            if (row) {
                row.classList.remove('analyzing-row');
                row.classList.add('success-row');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${result.data.word_count}</td>
                    <td>${result.data.char_count}</td>
                    <td><button class="small-btn" onclick="reanalyzeImage(${index})">🔄</button></td>
                `;
            }
            
            // 통계 재계산
            recalculateOcrStatistics();
            
            if (previewContainer) {
                previewContainer.innerHTML = `
                    <div class="result-box success" style="margin-top: 10px;">
                        ✅ 이미지 ${index + 1} 재분석 완료: ${result.data.word_count}단어 / ${result.data.char_count}자
                    </div>
                `;
            }
        } else {
            if (row) {
                row.classList.remove('analyzing-row');
                row.classList.add('error-row');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td colspan="2" class="error-message">${result.message || '분석 실패'}</td>
                    <td><button class="small-btn" onclick="reanalyzeImage(${index})">🔄</button></td>
                `;
            }
            
            if (previewContainer) {
                previewContainer.innerHTML = `
                    <div class="result-box warning" style="margin-top: 10px;">
                        ❌ 재분석 실패: ${result.message}
                    </div>
                `;
            }
        }
    } catch (error) {
        img.analyzing = false;
        if (row) {
            row.classList.remove('analyzing-row');
            row.classList.add('error-row');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td colspan="2" class="error-message">${error.message}</td>
                <td><button class="small-btn" onclick="reanalyzeImage(${index})">🔄</button></td>
            `;
        }
        
        if (previewContainer) {
            previewContainer.innerHTML = `
                <div class="result-box warning" style="margin-top: 10px;">
                    ❌ 오류: ${error.message}
                </div>
            `;
        }
    }
    
    updateImageUploadUI();
}

// OCR 통계 재계산
function recalculateOcrStatistics() {
    const images = samplingState.uploadedImages;
    const validResults = images.filter(img => img.ocrResult).map((img, i) => ({
        index: i + 1,
        ...img.ocrResult
    }));
    
    if (validResults.length === 0) return;
    
    // 신뢰구간 계산
    const charValues = validResults.map(r => r.char_count);
    const wordValues = validResults.map(r => r.word_count);
    
    const charCI = calculateConfidenceInterval(charValues);
    const wordCI = calculateConfidenceInterval(wordValues);
    
    // Step 1에서 입력한 최대 행 숫자 가져오기
    const maxLinesPerPage = parseInt(document.getElementById('maxLinesPerPage').value) || 0;
    
    samplingState.estimatedCharsPerPage = charCI.mean;
    samplingState.estimatedWordsPerPage = wordCI.mean;
    samplingState.maxLinesPerPage = maxLinesPerPage;
    samplingState.confidenceIntervals = { chars: charCI, words: wordCI };
    
    // 통계 섹션 업데이트
    const statsSection = document.getElementById('ocrStatsSection');
    if (statsSection) {
        const isStatisticallyValid = charCI.relative_error !== 'N/A' && parseFloat(charCI.relative_error) < 15;
        const validityClass = isStatisticallyValid ? 'valid' : 'warning';
        const validityIcon = isStatisticallyValid ? '✅' : '⚠️';
        const validityText = isStatisticallyValid 
            ? '통계적으로 유효한 추정입니다 (오차 < 15%)' 
            : '오차가 다소 큽니다. 더 많은 샘플을 권장합니다.';
        
        statsSection.innerHTML = `
            <h4>📈 통계 분석 결과 (95% 신뢰구간) - 업데이트됨</h4>
            <table class="stats-table">
                <tr>
                    <th>항목</th>
                    <th>평균</th>
                    <th>표준편차</th>
                    <th>95% CI</th>
                    <th>상대오차</th>
                </tr>
                <tr>
                    <td>페이지당 단어 수</td>
                    <td><strong>${wordCI.mean}개</strong></td>
                    <td>±${wordCI.std}</td>
                    <td>[${wordCI.ci_lower}, ${wordCI.ci_upper}]</td>
                    <td class="${parseFloat(wordCI.relative_error) < 15 ? 'good' : 'warn'}">${wordCI.relative_error}</td>
                </tr>
                <tr>
                    <td>페이지당 글자 수</td>
                    <td><strong>${charCI.mean}자</strong></td>
                    <td>±${charCI.std}</td>
                    <td>[${charCI.ci_lower}, ${charCI.ci_upper}]</td>
                    <td class="${parseFloat(charCI.relative_error) < 15 ? 'good' : 'warn'}">${charCI.relative_error}</td>
                </tr>
            </table>
            ${maxLinesPerPage > 0 ? `
            <div class="manual-lines-info" style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                <strong>📏 수동 입력 행 수:</strong> ${maxLinesPerPage}행/페이지 (본문 가득 찬 페이지 기준)
            </div>
            ` : `
            <div class="manual-lines-warning" style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404;">
                ⚠️ Step 1에서 '페이지당 행 수'를 입력하지 않았습니다.
            </div>
            `}
            <div class="validity-indicator ${validityClass}">
                ${validityIcon} ${validityText}
            </div>
        `;
    }
    
    // 다음 단계 버튼 활성화
    if (validResults.length > 0) {
        document.getElementById('goToStep4Btn').disabled = false;
    }
}

// OCR 분석 메타데이터 저장 (OCR 분석 직후 호출)
async function saveOcrMetadata(sampleCount) {
    if (!currentJson) return;
    
    const key = Object.keys(currentJson)[0];
    if (!key) return;
    
    // 샘플 수만 기록 (페이지 번호 불필요)
    currentJson[key].metadata.ocr_sample_count = sampleCount;
    currentJson[key].metadata.ocr_analyzed_date = new Date().toISOString();
    
    // 신뢰구간 정보도 저장
    const ci = samplingState.confidenceIntervals || {};
    if (ci.chars) {
        currentJson[key].metadata.chars_per_page = {
            mean: samplingState.estimatedCharsPerPage,
            std: ci.chars?.std || null,
            ci_95: ci.chars ? [ci.chars.ci_lower, ci.chars.ci_upper] : null,
            relative_error: ci.chars?.relative_error || null
        };
    }
    if (ci.words) {
        currentJson[key].metadata.words_per_page = {
            mean: samplingState.estimatedWordsPerPage,
            std: ci.words?.std || null,
            ci_95: ci.words ? [ci.words.ci_lower, ci.words.ci_upper] : null,
            relative_error: ci.words?.relative_error || null
        };
    }
    
    updateJsonDisplay();
    markAsChanged();
    
    // DB 파일에 Step 3 완료 상태 저장
    await saveCurrentProgressToDb(3);
}

// Step 4: 최종 샘플링 실행 (Underwood 방식 재현)
function generateFinalSampling() {
    const startPage = samplingState.startPage;
    const endPage = samplingState.endPage;
    const charsPerPage = samplingState.estimatedCharsPerPage;
    const linesPerPage = samplingState.maxLinesPerPage || parseInt(document.getElementById('maxLinesPerPage').value) || 0;
    
    if (!charsPerPage) {
        alert('먼저 OCR 분석을 완료해주세요.');
        return;
    }
    
    if (!linesPerPage || linesPerPage <= 0) {
        alert('Step 1에서 "페이지당 행 수"를 입력해주세요.');
        return;
    }
    
    const totalPages = endPage - startPage + 1;
    const totalChars = totalPages * charsPerPage;
    const chunkSize = 500;  // 한국어 500자 = 영어 250 words에 해당
    const totalChunks = Math.floor(totalChars / chunkSize);
    
    if (totalChunks < 16) {
        alert(`전체 청크 수(${totalChunks})가 16개 미만입니다. 더 긴 텍스트가 필요합니다.`);
        return;
    }
    
    // Underwood 방식 재현: 첫 2개 + 마지막 2개 고정, 중간 12개 무작위
    const segIndexes = Array.from({ length: totalChunks }, (_, i) => i);
    const middleIndexes = segIndexes.slice(2, -2);  // 인덱스 2부터 n-3까지
    
    const selectedIndexes = [];
    selectedIndexes.push(0, 1);  // 첫 2개
    selectedIndexes.push(...getRandomSamples(middleIndexes, 12));  // 중간 12개
    selectedIndexes.push(segIndexes[segIndexes.length - 2], segIndexes[segIndexes.length - 1]);  // 마지막 2개
    
    // 정렬
    selectedIndexes.sort((a, b) => a - b);
    
    // 각 청크의 상대적 위치와 페이지/행 계산
    const positions = selectedIndexes.map((idx, i) => {
        const relativePos = idx / (totalChunks - 1);  // 0.0 ~ 1.0
        const charPosition = idx * chunkSize;
        const pageFloat = charPosition / charsPerPage;
        const page = Math.floor(pageFloat) + startPage;
        const pageOffset = pageFloat - Math.floor(pageFloat);
        const line = Math.round(pageOffset * linesPerPage) + 1;
        
        return {
            idx: i,
            chunkIdx: idx,
            relativePos: Math.round(relativePos * 10000) / 10000,
            page: Math.min(page, endPage),
            line: Math.max(1, Math.min(line, linesPerPage)),
            isFrame: idx < 2 || idx >= totalChunks - 2
        };
    });
    
    samplingState.samplingPositions = positions;
    
    // 결과 표시
    displaySamplingResults(positions, totalChunks, linesPerPage);
    
    // 메타데이터에 저장
    saveSamplingMetadata(totalChunks);
}

// 샘플링 결과 표시
function displaySamplingResults(positions, totalChunks, linesPerPage) {
    const resultEl = document.getElementById('samplingResults');
    
    resultEl.innerHTML = `
        <div class="result-box success">
            <h4>🎯 샘플링 결과 (Underwood 방식)</h4>
            <p class="note">
                총 ${totalChunks}개 청크 중 16개 선택 | 
                프레임 청크(첫 2 + 끝 2): 고정 선택 | 
                중간 청크: 무작위 비복원 추출 |>
                📏 행 수: ${linesPerPage}행/페이지 (본문 기준)
            </p>
            <table class="sampling-table">
                <tr>
                    <th>순서</th>
                    <th>청크 idx</th>
                    <th>상대 위치</th>
                    <th>페이지</th>
                    <th>시작 행</th>
                    <th>구분</th>
                </tr>
                ${positions.map(p => `
                    <tr class="${p.isFrame ? 'frame' : 'sampled'}">
                        <td>${p.idx}</td>
                        <td>${p.chunkIdx}</td>
                        <td>${(p.relativePos * 100).toFixed(1)}%</td>
                        <td><strong>${p.page}</strong></td>
                        <td><strong>${p.line}행</strong></td>
                        <td>${p.isFrame ? '🔒 프레임' : '🎲 무작위'}</td>
                    </tr>
                `).join('')}
            </table>
        </div>
        <div class="result-box highlight">
            <h4>📋 샘플링 작업 가이드</h4>
            <ol>
                ${positions.map(p => `
                    <li><strong>세그먼트 ${p.idx}</strong>: 
                        📖 ${p.page}페이지 ${p.line}행부터 500자 입력
                        <span class="note">(상대위치: ${(p.relativePos * 100).toFixed(1)}%)</span>
                    </li>
                `).join('')}
            </ol>
        </div>
    `;
}

// 샘플링 메타데이터 저장 (새 형식 - 평면 구조)
async function saveSamplingMetadata(totalChunks) {
    if (!currentJson) return;
    
    const key = Object.keys(currentJson)[0];
    const totalPages = samplingState.endPage - samplingState.startPage + 1;
    const ci = samplingState.confidenceIntervals || {};
    const meta = currentJson[key].metadata;
    
    // 샘플링 방법
    meta.sampling_method = 'underwood_proportional';
    
    // OCR 분석 결과 (신뢰구간 포함)
    meta.chars_per_page = {
        mean: samplingState.estimatedCharsPerPage,
        std: ci.chars?.std || null,
        ci_95: ci.chars ? [ci.chars.ci_lower, ci.chars.ci_upper] : null,
        relative_error: ci.chars?.relative_error || null
    };
    meta.words_per_page = {
        mean: samplingState.estimatedWordsPerPage,
        std: ci.words?.std || null,
        ci_95: ci.words ? [ci.words.ci_lower, ci.words.ci_upper] : null,
        relative_error: ci.words?.relative_error || null
    };
    meta.lines_per_page = samplingState.maxLinesPerPage || parseInt(document.getElementById('maxLinesPerPage').value) || null;
    
    // 청크 정보
    meta.total_chunks = totalChunks;
    meta.chunk_size = 500;
    
    // 샘플링 위치
    meta.sampling_positions = samplingState.samplingPositions;
    meta.sampling_date = new Date().toISOString();
    
    // numwords, numchars 자동 업데이트
    meta.numchars = totalPages * samplingState.estimatedCharsPerPage;
    meta.numwords = totalPages * (samplingState.estimatedWordsPerPage || Math.round(samplingState.estimatedCharsPerPage / 1.5));
    
    document.getElementById('numchars').value = currentJson[key].metadata.numchars;
    document.getElementById('numwords').value = currentJson[key].metadata.numwords;
    
    updateJsonDisplay();
    markAsChanged();
    
    // DB 파일에 Step 4 완료 상태 저장
    await saveCurrentProgressToDb(4);
}

// 스텝 이동
async function goToStep(step) {
    // Step 2로 이동할 때 maxLinesPerPage 필수 입력 검증
    if (step === 2) {
        const maxLinesPerPage = parseInt(document.getElementById('maxLinesPerPage').value);
        if (!maxLinesPerPage || maxLinesPerPage <= 0) {
            alert('"페이지당 행 수"를 입력해주세요.\n(본문이 가득 찬 페이지 기준)');
            document.getElementById('maxLinesPerPage').focus();
            return;
        }
        // 페이지당 행 수를 상태와 메타데이터에 저장
        samplingState.maxLinesPerPage = maxLinesPerPage;
        saveLinesPerPageMetadata(maxLinesPerPage);
        
        // Step 2로 이동 시 기존 이미지 확인 (요청사항 2)
        setTimeout(() => checkAndShowExistingImages(), 100);
    }
    
    samplingState.step = step;
    
    // 모든 스텝 패널 숨기기
    document.querySelectorAll('.step-panel').forEach(el => el.style.display = 'none');
    
    // 현재 스텝 패널 표시
    const panel = document.getElementById(`step${step}Panel`);
    if (panel) panel.style.display = 'block';
    
    // 스텝 인디케이터 업데이트
    document.querySelectorAll('.step').forEach((el, idx) => {
        el.classList.remove('active', 'completed');
        if (idx + 1 < step) el.classList.add('completed');
        if (idx + 1 === step) el.classList.add('active');
    });
}

// 페이지당 행 수 메타데이터 저장
function saveLinesPerPageMetadata(linesPerPage) {
    if (!currentJson) return;
    
    const key = Object.keys(currentJson)[0];
    if (!key) return;
    
    currentJson[key].metadata.lines_per_page = linesPerPage;
    
    updateJsonDisplay();
    markAsChanged();
}

// ==================== 다중 이미지 업로드 기능 ====================

// 다중 파일 업로드 트리거
function triggerMultiImageUpload() {
    const input = document.getElementById('multiImageFileInput');
    input.click();
}

// 드래그 앤 드롭 초기화
function initDragAndDrop() {
    const dropZone = document.getElementById('dropZone');
    if (!dropZone) return;
    
    // 드래그 이벤트
    dropZone.addEventListener('dragenter', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleMultiImageUpload(files);
        }
    });
    
    // 다중 파일 선택 이벤트
    const multiInput = document.getElementById('multiImageFileInput');
    if (multiInput) {
        multiInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleMultiImageUpload(e.target.files);
            }
            e.target.value = ''; // 초기화
        });
    }
}

// 다중 이미지 업로드 처리 (단순화 - 페이지 매핑 없음)
async function handleMultiImageUpload(files) {
    const docid = document.getElementById('docid').value || 'temp';
    
    // 기존 이미지가 있는지 확인 (요청사항 2)
    const existingImages = window._existingImages || [];
    if (existingImages.length > 0 || samplingState.uploadedImages.length > 0) {
        const confirmMsg = existingImages.length > 0 
            ? `서버에 기존 이미지 ${existingImages.length}개가 있습니다.\n새 이미지 업로드 시 기존 이미지가 모두 삭제됩니다.\n\n계속하시겠습니까?`
            : `이미 업로드된 이미지 ${samplingState.uploadedImages.length}개가 있습니다.\n기존 이미지를 삭제하고 새로 업로드하시겠습니까?`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // 기존 이미지 삭제
        if (existingImages.length > 0) {
            await deleteServerImages(docid);
            window._existingImages = [];
            document.getElementById('existingImagesAlert').style.display = 'none';
        }
        
        // 상태 초기화
        samplingState.uploadedImages = [];
    }
    
    const statusEl = document.getElementById('multiUploadStatus');
    statusEl.style.display = 'block';
    
    // 업로드 진행
    statusEl.innerHTML = `
        <div class="progress-bar"><div class="progress" style="width: 0%"></div></div>
        <div class="upload-status-text">0 / ${files.length} 업로드 중...</div>
    `;
    
    let uploaded = 0;
    let failed = 0;
    
    // 파일들을 이름순으로 정렬 (일관된 순서 보장)
    const sortedFiles = Array.from(files).sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));
    
    // 항상 0부터 시작 (기존 이미지는 삭제됨)
    let pageIndex = 0;
    
    for (const file of sortedFiles) {
        try {
            const formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('image', file);
            formData.append('docid', docid);
            formData.append('page_num', pageIndex);  // 고유 인덱스 추가
            
            const response = await fetch(API_BASE, { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                const imageData = {
                    filepath: result.data.filepath,
                    filename: result.data.filename,
                    originalName: file.name,
                    ocrResult: null
                };
                samplingState.uploadedImages.push(imageData);
                uploaded++;
                pageIndex++;  // 다음 파일을 위해 인덱스 증가
            } else {
                console.error(`업로드 실패 (${file.name}):`, result.message);
                failed++;
            }
        } catch (error) {
            console.error(`업로드 오류 (${file.name}):`, error);
            failed++;
        }
        
        // 진행률 업데이트
        const percent = Math.round(((uploaded + failed) / sortedFiles.length) * 100);
        statusEl.querySelector('.progress').style.width = percent + '%';
        statusEl.querySelector('.upload-status-text').textContent = 
            `${uploaded + failed} / ${sortedFiles.length} 처리 중... (성공: ${uploaded}, 실패: ${failed})`;
    }
    
    // 완료 메시지
    const successClass = failed === 0 ? 'success' : 'warning';
    statusEl.innerHTML = `
        <div class="upload-complete ${successClass}">
            ✅ ${uploaded}개 파일 업로드 완료${failed > 0 ? `, ⚠️ ${failed}개 실패` : ''}
        </div>
    `;
    
    // UI 업데이트
    updateImageUploadUI();
    
    // DB 파일에 Step 2 진행 상태 저장 (이미지 업로드 정보 포함)
    if (uploaded > 0) {
        await saveCurrentProgressToDb(2);
    }
    
    // 3초 후 상태 메시지 숨김
    setTimeout(() => {
        statusEl.style.display = 'none';
    }, 3000);
}

// 페이지 로드 시 드래그앤드롭 초기화
document.addEventListener('DOMContentLoaded', initDragAndDrop);
