/**
 * 샘플링 도구 JavaScript - Part 4: 세그먼트 입력 및 JSON 관리
 */

// ==================== 세그먼트 입력 ====================

function updateCharCount() {
    const text = document.getElementById('textInput').value;
    const charCount = text.length;
    const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
    
    const display = document.getElementById('charCountDisplay');
    display.textContent = `글자 수: ${charCount}자 | 단어 수: ${wordCount}개`;
    
    display.className = 'char-count';
    if (charCount < 400) display.classList.add('warning');
    else if (charCount > 600) display.classList.add('danger');
}

function generateFilename() {
    const docid = document.getElementById('docid').value.trim();
    const author = document.getElementById('author').value.trim();
    const title = document.getElementById('title').value.trim();
    const source = document.getElementById('source').value.trim();

    if (!docid || !author || !title || !source) {
        alert('docid, 작가, 제목, 출처를 모두 입력해주세요.');
        return;
    }

    const docidClean = docid.replace(/\s+/g, '_');
    const authorClean = author.replace(/\s+/g, '_');
    const titleClean = title.replace(/\s+/g, '_');
    const sourceClean = source.replace(/\s+/g, '_');

    const docNum = docid.replace(/\D/g, '');
    const filename = `${docidClean}-${authorClean}-${titleClean}-${sourceClean}`;
    document.getElementById('filename').value = filename;
    document.getElementById('originalid').value = `${docNum}_${authorClean}_${titleClean}_${sourceClean}`;
}

async function initializeJson() {
    const filename = document.getElementById('filename').value.trim();
    if (!filename) {
        alert('파일명을 먼저 입력하거나 자동 생성해주세요.');
        return;
    }

    const jsonFilename = filename.replace(/\.txt$/i, '').replace(/\.json$/i, '') + '.json';
    
    try {
        const checkResponse = await fetch(`${API_BASE}?action=check_exists&filename=${encodeURIComponent(jsonFilename)}`);
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
                processed_date: now,
                sampling: null
            },
            chunkct: 0,
            segments: []
        }
    };

    document.getElementById('segmentIdx').value = '0';
    
    try {
        const response = await fetch(API_BASE + '?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename: jsonFilename, content: currentJson })
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
        ? null : narratedtimeVal;

    const segmentIdx = parseInt(document.getElementById('segmentIdx').value) || 0;

    // 샘플링 위치 정보 가져오기
    const positionInfo = samplingState.samplingPositions.find(p => p.idx === segmentIdx);
    
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
        subjectivephrase: document.getElementById('subjectivephrase').value,
        // 샘플링 위치 메타데이터
        sampling_position: positionInfo ? {
            relative_pos: positionInfo.relativePos,
            page: positionInfo.page,
            line: positionInfo.line,
            is_frame: positionInfo.isFrame
        } : null
    };

    const filename = Object.keys(currentJson)[0];
    
    // 기존 세그먼트 덮어쓰기 또는 추가
    const existingIdx = currentJson[filename].segments.findIndex(s => s.idx === segmentIdx);
    if (existingIdx >= 0) {
        if (!confirm(`세그먼트 ${segmentIdx}이(가) 이미 존재합니다. 덮어쓰시겠습니까?`)) {
            return;
        }
        currentJson[filename].segments[existingIdx] = segment;
    } else {
        currentJson[filename].segments.push(segment);
    }
    
    currentJson[filename].chunkct = currentJson[filename].segments.length;

    document.getElementById('segmentIdx').value = segmentIdx + 1;
    clearTextInput();
    
    updateJsonDisplay();
    updateSegmentInfo();
    saveToLocalStorage();
    markAsChanged();
    updateSegmentGuide();

    alert(`세그먼트 ${segment.idx} 저장 완료!`);
}

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

    if (!confirm('마지막 세그먼트를 삭제하시겠습니까?')) return;

    const removedSegment = currentJson[filename].segments.pop();
    currentJson[filename].chunkct = currentJson[filename].segments.length;
    document.getElementById('segmentIdx').value = removedSegment.idx;

    updateJsonDisplay();
    updateSegmentInfo();
    saveToLocalStorage();
    markAsChanged();
}

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
    document.getElementById('segmentIdx').value = targetIdx;

    updateJsonDisplay();
    updateSegmentInfo();
    saveToLocalStorage();
    markAsChanged();
    
    alert(`idx ${targetIdx} 세그먼트가 삭제되었습니다.`);
}

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
    samplingState = {
        step: 1,
        startPage: 1,
        endPage: 100,
        uploadedImages: [],
        ocrResults: [],
        estimatedCharsPerPage: 0,
        estimatedLinesPerPage: 0,
        samplingPositions: [],
    };

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
    
    goToStep(1);
}

// ==================== UI 업데이트 ====================

function updateJsonDisplay() {
    const el = document.getElementById('jsonOutput');
    if (!currentJson) {
        el.textContent = 'JSON이 초기화되지 않았습니다.';
        return;
    }
    el.textContent = JSON.stringify(currentJson, null, 2);
}

function updateSegmentInfo() {
    const el = document.getElementById('segmentCount');
    if (currentJson) {
        const filename = Object.keys(currentJson)[0];
        el.textContent = currentJson[filename].segments.length;
    } else {
        el.textContent = '0';
    }
}

function updateSegmentGuide() {
    const guideEl = document.getElementById('currentSegmentGuide');
    if (!guideEl) return;
    
    const segmentIdx = parseInt(document.getElementById('segmentIdx').value) || 0;
    const positionInfo = samplingState.samplingPositions.find(p => p.idx === segmentIdx);
    
    if (positionInfo) {
        guideEl.innerHTML = `
            <div class="result-box highlight">
                <strong>📍 현재 입력할 세그먼트 ${segmentIdx}:</strong>
                <p>📖 <strong>${positionInfo.page}페이지 ${positionInfo.line}행</strong>부터 500자 입력</p>
                <p class="note">상대위치: ${(positionInfo.relativePos * 100).toFixed(1)}% | ${positionInfo.isFrame ? '🔒 프레임 청크' : '🎲 무작위 청크'}</p>
            </div>
        `;
    } else {
        guideEl.innerHTML = '<div class="result-box">샘플링을 먼저 실행해주세요.</div>';
    }
}

function saveToLocalStorage() {
    if (currentJson) {
        localStorage.setItem('samplingData', JSON.stringify(currentJson));
    }
}

function copyJson() {
    if (!currentJson) {
        alert('복사할 JSON이 없습니다.');
        return;
    }

    const jsonText = JSON.stringify(currentJson, null, 2);
    navigator.clipboard.writeText(jsonText).then(() => {
        alert('JSON이 클립보드에 복사되었습니다!');
    }).catch(() => {
        const textarea = document.createElement('textarea');
        textarea.value = jsonText;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('JSON이 클립보드에 복사되었습니다!');
    });
}

// ==================== 초기화 ====================

document.addEventListener('DOMContentLoaded', function() {
    // 텍스트 입력 실시간 글자 수 카운트
    const textInput = document.getElementById('textInput');
    if (textInput) textInput.addEventListener('input', updateCharCount);
    
    // 세그먼트 idx 변경 시 가이드 업데이트
    const segmentIdx = document.getElementById('segmentIdx');
    if (segmentIdx) segmentIdx.addEventListener('change', updateSegmentGuide);
    
    // 이미지 파일 입력 핸들러
    const imageInput = document.getElementById('imageFileInput');
    if (imageInput) imageInput.addEventListener('change', handleImageUpload);
    
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
    
    // Ollama 상태 확인
    checkOllamaStatus();
});
