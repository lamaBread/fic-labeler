/**
 * API 관련 JavaScript 함수들
 */

/**
 * 메시지 표시
 */
function showMessage(text, isError = false) {
    const msg = document.getElementById('message');
    msg.textContent = text;
    msg.className = 'message show ' + (isError ? 'error' : 'success');
    setTimeout(() => {
        msg.classList.remove('show');
    }, 3000);
}

/**
 * 파일 목록 로드
 */
function loadFileList() {
    fetch('?action=list_files', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('fileSelect');
            select.innerHTML = '<option value="">-- 파일 선택 --</option>';
            data.files.forEach(file => {
                const option = document.createElement('option');
                option.value = file;
                option.textContent = file;
                select.appendChild(option);
            });
        }
    });
}

/**
 * 파일 로드 (1단계 → 2단계)
 */
function loadFile() {
    const filename = document.getElementById('fileSelect').value;
    if (!filename) {
        showMessage('파일을 선택해주세요.', true);
        return;
    }
    
    selectedFileName = filename;
    customOutputName = document.getElementById('customOutputName').value.trim();
    
    const formData = new FormData();
    formData.append('action', 'load');
    formData.append('filename', filename);
    formData.append('custom_output_name', customOutputName);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showMessage(data.error, true);
            return;
        }
        
        // 새 파일 로드 시 상태 초기화
        headers = data.headers;
        totalRows = data.total;
        currentIndex = 0;
        currentStep = 2; // 명시적으로 2단계로 설정
        outputFormat = 'csv'; // 출력 형식 초기화
        
        showMessage('파일이 성공적으로 로드되었습니다! 2단계로 진행합니다.');
        
        // 모든 단계 섹션 숨기기
        document.getElementById('step1Section').style.display = 'none';
        document.getElementById('step3Section').style.display = 'none';
        
        // 2단계 표시
        document.getElementById('step2Section').style.display = 'block';
        document.getElementById('loadedFileName').textContent = filename;
        
        // 출력 형식 선택 초기화
        document.getElementById('outputFormat').value = 'csv';
        
        // 현재 열 표시
        updateColumnList();
    })
    .catch(err => {
        showMessage('파일 로드 중 오류가 발생했습니다.', true);
        console.error(err);
    });
}

/**
 * 열 추가 (2단계에서만 가능)
 */
function addColumn() {
    if (currentStep !== 2) {
        showMessage('열 추가는 2단계에서만 가능합니다.', true);
        return;
    }
    
    const columnName = document.getElementById('newColumnName').value.trim();
    if (!columnName) {
        showMessage('열 이름을 입력해주세요.', true);
        return;
    }
    
    if (headers.includes(columnName)) {
        showMessage('이미 존재하는 열 이름입니다.', true);
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_column');
    formData.append('column_name', columnName);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showMessage(data.error, true);
            return;
        }
        
        headers = data.headers;
        showMessage('열이 추가되었습니다!');
        document.getElementById('newColumnName').value = '';
        updateColumnList();
    })
    .catch(err => {
        showMessage('열 추가 중 오류가 발생했습니다.', true);
        console.error(err);
    });
}

/**
 * 출력 파일 초기화 (2단계 → 3단계 전환 시)
 */
function initializeOutputFile(callback) {
    const formData = new FormData();
    formData.append('action', 'initialize_output');
    formData.append('format', outputFormat);
    formData.append('custom_output_name', customOutputName);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showMessage(data.error, true);
            if (callback) callback(false);
            return;
        }
        
        showMessage('출력 파일이 생성되었습니다!');
        if (callback) callback(true);
    })
    .catch(err => {
        showMessage('파일 생성 중 오류가 발생했습니다.', true);
        console.error(err);
        if (callback) callback(false);
    });
}

/**
 * 행 데이터 로드
 */
function loadRow(index) {
    const formData = new FormData();
    formData.append('action', 'get_row');
    formData.append('index', index);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showMessage(data.message || '데이터를 불러올 수 없습니다.', true);
            return;
        }
        
        currentIndex = data.index;
        totalRows = data.total;
        
        // 행 정보 업데이트
        document.getElementById('rowInfo').textContent = 
            `행: ${currentIndex + 1} / ${totalRows}`;
        
        // 필드 렌더링
        renderFields(data.row);
    })
    .catch(err => {
        showMessage('데이터 로드 중 오류가 발생했습니다.', true);
        console.error(err);
    });
}

/**
 * 현재 행 저장 (3단계에서만)
 */
function saveCurrentRow(callback) {
    if (currentStep !== 3) {
        showMessage('3단계에서만 저장이 가능합니다.', true);
        return;
    }
    
    const rowData = collectRowData();
    
    const formData = new FormData();
    formData.append('action', 'save_row');
    formData.append('index', currentIndex);
    formData.append('row_data', JSON.stringify(rowData));
    formData.append('format', outputFormat);
    formData.append('custom_output_name', customOutputName);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showMessage(data.error, true);
            return;
        }
        
        if (data.saved) {
            showMessage('자동 저장되었습니다!');
        }
        
        if (callback) callback(data);
    })
    .catch(err => {
        showMessage('저장 중 오류가 발생했습니다.', true);
        console.error(err);
    });
}
