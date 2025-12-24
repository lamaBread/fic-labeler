/**
 * 샘플링 도구 JavaScript - Part 2: 파일 관리
 */

// ==================== 파일 관리 함수 ====================

async function loadFileList() {
    try {
        const response = await fetch(API_BASE + '?action=list');
        const result = await response.json();
        return result.success ? result.data : [];
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
        const response = await fetch(`${API_BASE}?action=load&filename=${encodeURIComponent(filename)}`);
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
                
                // 페이지당 행 수 복원 (요청사항 1)
                if (meta.lines_per_page) {
                    const linesPerPage = typeof meta.lines_per_page === 'object' 
                        ? meta.lines_per_page.mean || meta.lines_per_page 
                        : meta.lines_per_page;
                    document.getElementById('maxLinesPerPage').value = linesPerPage;
                    samplingState.maxLinesPerPage = linesPerPage;
                }
                
                // DB 파일에서 진행 상태 로드 시도
                const docid = meta.docid;
                let dbData = null;
                if (docid && typeof loadDbFile === 'function') {
                    dbData = await loadDbFile(docid);
                }
                
                // 샘플링 관련 메타데이터 복원 (새 형식 - 평면 구조)
                if (meta.page_range) {
                    const startPage = meta.page_range.start || 1;
                    const endPage = meta.page_range.end || 100;
                    document.getElementById('startPage').value = startPage;
                    document.getElementById('endPage').value = endPage;
                    samplingState.startPage = startPage;
                    samplingState.endPage = endPage;
                    
                    // OCR 샘플 페이지 복원
                    if (meta.ocr_pages && Array.isArray(meta.ocr_pages)) {
                        samplingState.samplePagesForOcr = meta.ocr_pages;
                        samplingState.samplingMethod = meta.ocr_sampling_method || 'manual';
                        samplingState.numStrata = meta.ocr_num_strata || null;
                        
                        // Step 1 UI 복원 - 샘플 페이지 표시
                        restoreSamplePagesUI(meta);
                    } else {
                        // page_range만 있고 ocr_pages가 없는 경우에도 버튼 활성화
                        document.getElementById('goToStep2Btn').disabled = false;
                    }
                    
                    // OCR 분석 결과 복원
                    if (meta.chars_per_page) {
                        samplingState.estimatedCharsPerPage = meta.chars_per_page.mean || meta.chars_per_page;
                        samplingState.estimatedLinesPerPage = meta.lines_per_page?.mean || meta.lines_per_page || 0;
                        samplingState.estimatedCharsPerLine = meta.chars_per_line?.mean || meta.chars_per_line || 0;
                        
                        // 신뢰구간 복원
                        if (meta.chars_per_page.ci_95) {
                            samplingState.confidenceIntervals = {
                                chars: {
                                    mean: meta.chars_per_page.mean,
                                    std: meta.chars_per_page.std,
                                    ci_lower: meta.chars_per_page.ci_95[0],
                                    ci_upper: meta.chars_per_page.ci_95[1],
                                    relative_error: meta.chars_per_page.relative_error
                                },
                                lines: meta.lines_per_page ? {
                                    mean: meta.lines_per_page.mean,
                                    std: meta.lines_per_page.std,
                                    ci_lower: meta.lines_per_page.ci_95?.[0],
                                    ci_upper: meta.lines_per_page.ci_95?.[1],
                                    relative_error: meta.lines_per_page.relative_error
                                } : null,
                                charsPerLine: meta.chars_per_line ? {
                                    mean: meta.chars_per_line.mean,
                                    std: meta.chars_per_line.std,
                                    ci_lower: meta.chars_per_line.ci_95?.[0],
                                    ci_upper: meta.chars_per_line.ci_95?.[1],
                                    relative_error: meta.chars_per_line.relative_error
                                } : null
                            };
                        }
                        
                        // words_per_page 복원
                        if (meta.words_per_page) {
                            samplingState.estimatedWordsPerPage = meta.words_per_page.mean || meta.words_per_page;
                            if (meta.words_per_page.ci_95 && samplingState.confidenceIntervals) {
                                samplingState.confidenceIntervals.words = {
                                    mean: meta.words_per_page.mean,
                                    std: meta.words_per_page.std,
                                    ci_lower: meta.words_per_page.ci_95[0],
                                    ci_upper: meta.words_per_page.ci_95[1],
                                    relative_error: meta.words_per_page.relative_error
                                };
                            }
                        }
                        
                        // OCR 분석이 완료된 경우 Step 4 버튼 활성화
                        document.getElementById('goToStep4Btn').disabled = false;
                    }
                    
                    // 샘플링 위치 복원
                    if (meta.sampling_positions && Array.isArray(meta.sampling_positions)) {
                        samplingState.samplingPositions = meta.sampling_positions;
                    }
                }
                
                // DB 파일에서 업로드된 이미지 정보 복원
                if (dbData && dbData.state && dbData.state.uploadedImages) {
                    samplingState.uploadedImages = dbData.state.uploadedImages;
                }
                
                // 서버에 이미지가 있는지 확인하고 복원 (요청사항 2, 4)
                if (docid && typeof checkServerImages === 'function') {
                    const imageResult = await checkServerImages(docid);
                    if (imageResult.exists && imageResult.count > 0) {
                        // 서버에 이미지가 있으면 이미지 정보 복원 (DB의 ocrResult 정보와 병합)
                        const dbImages = (dbData && dbData.state && dbData.state.uploadedImages) || [];
                        
                        samplingState.uploadedImages = imageResult.images.map((img, idx) => {
                            // DB에서 해당 이미지의 OCR 결과 찾기
                            const dbImg = dbImages.find(d => d.filename === img.filename);
                            return {
                                filepath: img.filepath,
                                filename: img.filename,
                                originalName: dbImg?.originalName || img.filename,
                                ocrResult: dbImg?.ocrResult || null
                            };
                        });
                    }
                }
                
                // 진행 상태에 따라 적절한 단계로 이동 (요청사항 3, 4)
                await restoreToAppropriateStep(meta, dbData);
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

// 진행 상태에 따라 적절한 단계로 이동하는 함수
async function restoreToAppropriateStep(meta, dbData) {
    // DB 파일의 current_step 확인
    const dbStep = dbData?.current_step || 0;
    
    // 메타데이터 기반으로 완료된 단계 판단
    let completedStep = 0;
    
    // Step 1 완료: page_range와 ocr_pages가 있음
    if (meta.page_range && meta.ocr_pages && meta.ocr_pages.length > 0) {
        completedStep = 1;
    }
    
    // Step 2 완료: 이미지가 업로드됨 (서버 또는 DB 확인)
    if (completedStep >= 1 && samplingState.uploadedImages && samplingState.uploadedImages.length > 0) {
        completedStep = 2;
    }
    
    // Step 3 완료: OCR 분석 완료 (chars_per_page가 있음)
    if (completedStep >= 2 && meta.chars_per_page && meta.chars_per_page.mean) {
        completedStep = 3;
    }
    
    // Step 4 완료: 샘플링 위치가 계산됨
    if (completedStep >= 3 && meta.sampling_positions && meta.sampling_positions.length > 0) {
        completedStep = 4;
    }
    
    // 최종 단계 결정 (DB 저장값과 메타데이터 기반 중 더 높은 값)
    const targetStep = Math.max(dbStep, completedStep);
    
    console.log(`진행 상태 복원: DB Step=${dbStep}, Metadata Step=${completedStep}, Target Step=${targetStep}`);
    
    // 완료된 단계에 따라 UI 상태 및 단계 이동
    if (targetStep >= 4) {
        // Step 4 완료 상태: 결과 화면 바로 표시 (요청사항 3)
        document.getElementById('goToStep2Btn').disabled = false;
        document.getElementById('goToStep4Btn').disabled = false;
        
        // OCR 분석 결과 UI 복원
        if (typeof restoreOcrResultsUI === 'function') {
            restoreOcrResultsUI();
        }
        
        // Step 4로 이동하고 결과 표시
        goToStep(4);
        
        // 샘플링 결과 표시
        if (samplingState.samplingPositions && samplingState.samplingPositions.length > 0) {
            const linesPerPage = samplingState.maxLinesPerPage || meta.lines_per_page || 24;
            const totalChunks = meta.total_chunks || 0;
            displaySamplingResults(samplingState.samplingPositions, totalChunks, linesPerPage);
        }
    } else if (targetStep >= 3) {
        // Step 3 완료 상태
        document.getElementById('goToStep2Btn').disabled = false;
        document.getElementById('goToStep4Btn').disabled = false;
        
        // OCR 분석 결과 UI 복원
        if (typeof restoreOcrResultsUI === 'function') {
            restoreOcrResultsUI();
        }
        
        goToStep(4);
    } else if (targetStep >= 2) {
        // Step 2 완료 상태 (이미지 업로드 완료)
        document.getElementById('goToStep2Btn').disabled = false;
        goToStep(3);
        updateImageUploadUI();
    } else if (targetStep >= 1) {
        // Step 1 완료 상태
        document.getElementById('goToStep2Btn').disabled = false;
        
        // Step 2로 이동하되, maxLinesPerPage 검증 스킵을 위해 직접 패널 전환
        samplingState.step = 2;
        document.querySelectorAll('.step-panel').forEach(el => el.style.display = 'none');
        document.getElementById('step2Panel').style.display = 'block';
        document.querySelectorAll('.step').forEach((el, idx) => {
            el.classList.remove('active', 'completed');
            if (idx + 1 < 2) el.classList.add('completed');
            if (idx + 1 === 2) el.classList.add('active');
        });
        
        updateImageUploadUI();
        
        // 기존 이미지 확인
        if (typeof checkAndShowExistingImages === 'function') {
            setTimeout(() => checkAndShowExistingImages(), 100);
        }
    }
    // targetStep == 0이면 Step 1에 머무름 (기본)
}

// OCR 분석 결과 UI 복원 함수
function restoreOcrResultsUI() {
    const images = samplingState.uploadedImages || [];
    const ci = samplingState.confidenceIntervals || {};
    const maxLinesPerPage = samplingState.maxLinesPerPage || 0;
    
    // OCR 결과가 있는 이미지 필터링
    const validResults = images.filter(img => img.ocrResult);
    
    if (validResults.length === 0) return;
    
    const resultEl = document.getElementById('ocrResults');
    if (!resultEl) return;
    
    const charCI = ci.chars || { mean: samplingState.estimatedCharsPerPage, std: 0, ci_lower: 0, ci_upper: 0, relative_error: 'N/A' };
    const wordCI = ci.words || { mean: samplingState.estimatedWordsPerPage, std: 0, ci_lower: 0, ci_upper: 0, relative_error: 'N/A' };
    
    const isStatisticallyValid = charCI.relative_error !== 'N/A' && parseFloat(charCI.relative_error) < 15;
    const validityClass = isStatisticallyValid ? 'valid' : 'warning';
    const validityIcon = isStatisticallyValid ? '✅' : '⚠️';
    const validityText = isStatisticallyValid 
        ? '통계적으로 유효한 추정입니다 (오차 < 15%)' 
        : '오차가 다소 큽니다. 더 많은 샘플을 권장합니다.';
    
    resultEl.innerHTML = `
        <div class="result-box success">
            <h4>📊 OCR 분석 결과 (n=${validResults.length}) - 저장된 데이터</h4>
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
                            return `<tr id="ocrRow${i}" class="pending-row">
                                <td>${i + 1}</td>
                                <td colspan="2">분석 필요</td>
                                <td><button class="small-btn" onclick="reanalyzeImage(${i})">🔄</button></td>
                            </tr>`;
                        }
                    }).join('')}
                </tbody>
            </table>
            
            <hr>
            <div id="ocrStatsSection">
                <h4>📈 통계 분석 결과 (95% 신뢰구간) - 저장된 데이터</h4>
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
                        <td>±${wordCI.std || 0}</td>
                        <td>[${wordCI.ci_lower || 0}, ${wordCI.ci_upper || 0}]</td>
                        <td class="${parseFloat(wordCI.relative_error) < 15 ? 'good' : 'warn'}">${wordCI.relative_error || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td>페이지당 글자 수</td>
                        <td><strong>${charCI.mean}자</strong></td>
                        <td>±${charCI.std || 0}</td>
                        <td>[${charCI.ci_lower || 0}, ${charCI.ci_upper || 0}]</td>
                        <td class="${parseFloat(charCI.relative_error) < 15 ? 'good' : 'warn'}">${charCI.relative_error || 'N/A'}</td>
                    </tr>
                </table>
                ${maxLinesPerPage > 0 ? `
                <div class="manual-lines-info" style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                    <strong>📏 수동 입력 행 수:</strong> ${maxLinesPerPage}행/페이지 (본문 가득 찬 페이지 기준)
                </div>
                ` : ''}
                <div class="validity-indicator ${validityClass}">
                    ${validityIcon} ${validityText}
                </div>
            </div>
        </div>
    `;
    
    // 이미지 그리드 업데이트
    updateImageUploadUI();
}

// 샘플 페이지 UI 복원 함수
function restoreSamplePagesUI(meta) {
    const samplePages = meta.ocr_pages;
    const startPage = meta.page_range.start;
    const endPage = meta.page_range.end;
    const totalPages = meta.page_range.total;
    const method = meta.ocr_sampling_method || 'manual';
    const numStrata = meta.ocr_num_strata;
    
    const resultEl = document.getElementById('samplePagesForOcr');
    
    if (method === 'manual') {
        resultEl.innerHTML = `
            <strong>📸 OCR 분석용 페이지 (${samplePages.length}개 - 직접 지정):</strong>
            <div class="page-list">${samplePages.join(', ')}</div>
            <div class="sampling-info">
                <p class="note">📝 <strong>직접 지정</strong> 방식 (저장된 데이터)</p>
                <ul class="sampling-details">
                    <li>책 본문 범위: ${startPage} ~ ${endPage}쪽 (총 ${totalPages}쪽)</li>
                    <li>지정된 샘플 페이지: ${samplePages.length}개</li>
                </ul>
            </div>
        `;
    } else {
        resultEl.innerHTML = `
            <strong>📸 OCR 분석용 페이지 (${samplePages.length}개):</strong>
            <div class="page-list">${samplePages.join(', ')}</div>
            <div class="sampling-info">
                <p class="note">📊 <strong>층화 무작위 추출</strong> (저장된 데이터)</p>
                <ul class="sampling-details">
                    <li>총 페이지: ${totalPages}쪽</li>
                    ${numStrata ? `<li>층(Strata) 수: ${numStrata}개</li>` : ''}
                    <li>샘플 수: ${samplePages.length}개</li>
                </ul>
            </div>
        `;
    }
    
    // Step 2로 이동 가능하도록 버튼 활성화
    document.getElementById('goToStep2Btn').disabled = false;
    
    // 이미지 업로드 UI도 업데이트 (함수가 있으면)
    if (typeof updateImageUploadUI === 'function') {
        updateImageUploadUI();
    }
}

async function saveToServer(forceOverwrite = false) {
    if (!currentJson) {
        alert('저장할 JSON이 없습니다. 먼저 JSON을 초기화해주세요.');
        return;
    }
    
    const key = Object.keys(currentJson)[0];
    const filename = key.replace(/\.txt$/i, '') + '.json';
    
    if (!forceOverwrite && currentServerFilename !== filename) {
        try {
            const checkResponse = await fetch(`${API_BASE}?action=check_exists&filename=${encodeURIComponent(filename)}`);
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
        const response = await fetch(API_BASE + '?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename: filename, content: currentJson })
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
    if (pendingOverwriteCallback) pendingOverwriteCallback();
}

async function downloadFile(filename) {
    window.location.href = `${API_BASE}?action=download&filename=${encodeURIComponent(filename)}`;
}

function downloadCurrentFile() {
    if (!currentJson) {
        alert('다운로드할 JSON이 없습니다.');
        return;
    }
    
    if (currentServerFilename) {
        downloadFile(currentServerFilename);
    } else {
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
    if (!confirm(`"${filename}" 파일을 삭제하시겠습니까?`)) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=delete&filename=${encodeURIComponent(filename)}`);
        const result = await response.json();
        
        if (result.success) {
            if (currentServerFilename === filename) {
                currentServerFilename = null;
                updateCurrentFileDisplay();
            }
            showFileListModal();
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
