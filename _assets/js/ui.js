/**
 * UI 관련 JavaScript 함수들
 */

let currentIndex = 0;
let totalRows = 0;
let headers = [];
let currentStep = 1; // 현재 단계 추적
let selectedFileName = ''; // 선택된 파일명
let customOutputName = ''; // 사용자 지정 출력 파일명
let outputFormat = 'csv'; // 출력 형식

/**
 * 페이지 로드 시 초기화
 */
window.onload = function() {
    loadFileList();
};

/**
 * 현재 열 목록 업데이트 (드래그 앤 드롭 지원)
 */
function updateColumnList() {
    const container = document.getElementById('columnManagementList');
    container.innerHTML = '';
    
    if (headers.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">아직 추가된 열이 없습니다.</p>';
        return;
    }
    
    headers.forEach((columnName, index) => {
        const item = document.createElement('div');
        item.className = 'column-item';
        item.draggable = true;
        item.dataset.index = index;
        item.dataset.columnName = columnName;
        
        // 드래그 핸들
        const dragHandle = document.createElement('div');
        dragHandle.className = 'drag-handle';
        dragHandle.innerHTML = '☰';
        dragHandle.title = '드래그하여 순서 변경';
        
        // 열 이름
        const nameSpan = document.createElement('span');
        nameSpan.className = 'column-name';
        nameSpan.textContent = columnName;
        
        // 컨트롤 버튼들
        const controls = document.createElement('div');
        controls.className = 'column-controls';
        
        // 위로 버튼
        const btnUp = document.createElement('button');
        btnUp.className = 'btn-icon btn-up';
        btnUp.innerHTML = '↑';
        btnUp.title = '위로 이동';
        btnUp.onclick = (e) => {
            e.stopPropagation();
            moveColumn(index, -1);
        };
        if (index === 0) btnUp.disabled = true;
        
        // 아래로 버튼
        const btnDown = document.createElement('button');
        btnDown.className = 'btn-icon btn-down';
        btnDown.innerHTML = '↓';
        btnDown.title = '아래로 이동';
        btnDown.onclick = (e) => {
            e.stopPropagation();
            moveColumn(index, 1);
        };
        if (index === headers.length - 1) btnDown.disabled = true;
        
        // 삭제 버튼
        const btnDelete = document.createElement('button');
        btnDelete.className = 'btn-icon btn-delete';
        btnDelete.innerHTML = '✕';
        btnDelete.title = '열 삭제';
        btnDelete.onclick = (e) => {
            e.stopPropagation();
            deleteColumn(columnName);
        };
        
        controls.appendChild(btnUp);
        controls.appendChild(btnDown);
        controls.appendChild(btnDelete);
        
        item.appendChild(dragHandle);
        item.appendChild(nameSpan);
        item.appendChild(controls);
        
        // 드래그 이벤트
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
        
        container.appendChild(item);
    });
}

/**
 * 드래그 시작
 */
let draggedElement = null;

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

/**
 * 드래그 오버
 */
function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

/**
 * 드래그 엔터
 */
function handleDragEnter(e) {
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
}

/**
 * 드래그 리브
 */
function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

/**
 * 드롭
 */
function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    if (draggedElement !== this) {
        const draggedIndex = parseInt(draggedElement.dataset.index);
        const targetIndex = parseInt(this.dataset.index);
        
        // 배열 요소 재배치
        const [removed] = headers.splice(draggedIndex, 1);
        headers.splice(targetIndex, 0, removed);
        
        // 서버에 순서 업데이트
        updateColumnOrder();
    }
    
    return false;
}

/**
 * 드래그 종료
 */
function handleDragEnd(e) {
    this.classList.remove('dragging');
    
    // 모든 drag-over 클래스 제거
    document.querySelectorAll('.column-item').forEach(item => {
        item.classList.remove('drag-over');
    });
}

/**
 * 열 이동 (위/아래 버튼)
 */
function moveColumn(index, direction) {
    const targetIndex = index + direction;
    
    if (targetIndex < 0 || targetIndex >= headers.length) {
        return;
    }
    
    // 배열 요소 교환
    [headers[index], headers[targetIndex]] = [headers[targetIndex], headers[index]];
    
    // 서버에 순서 업데이트
    updateColumnOrder();
}

/**
 * 열 삭제
 */
function deleteColumn(columnName) {
    if (!confirm(`'${columnName}' 열을 삭제하시겠습니까?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_column');
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
        showMessage(`'${columnName}' 열이 삭제되었습니다.`);
        updateColumnList();
    })
    .catch(err => {
        showMessage('열 삭제 중 오류가 발생했습니다.', true);
        console.error(err);
    });
}

/**
 * 열 순서 업데이트 (서버)
 */
function updateColumnOrder() {
    const formData = new FormData();
    formData.append('action', 'reorder_columns');
    formData.append('columns', JSON.stringify(headers));
    
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
        showMessage('열 순서가 업데이트되었습니다.');
        updateColumnList();
    })
    .catch(err => {
        showMessage('순서 업데이트 중 오류가 발생했습니다.', true);
        console.error(err);
    });
}

/**
 * 필드 렌더링
 */
function renderFields(rowData) {
    const container = document.getElementById('fieldsContainer');
    container.innerHTML = '';
    
    headers.forEach(header => {
        const fieldGroup = document.createElement('div');
        fieldGroup.className = 'field-group';
        
        const label = document.createElement('label');
        label.textContent = header;
        
        const value = rowData[header] || '';
        const isLongText = value.length > 50;
        
        let input;
        if (isLongText) {
            input = document.createElement('textarea');
        } else {
            input = document.createElement('input');
            input.type = 'text';
        }
        
        input.value = value;
        input.dataset.field = header;
        
        fieldGroup.appendChild(label);
        fieldGroup.appendChild(input);
        container.appendChild(fieldGroup);
    });
    
    // 마지막 행 경고 표시 업데이트
    updateLastRowWarning();
}

/**
 * 마지막 행 경고 표시 업데이트
 */
function updateLastRowWarning() {
    const warning = document.getElementById('lastRowWarning');
    if (warning && currentStep === 3 && currentIndex === totalRows - 1) {
        warning.style.display = 'block';
        document.body.classList.add('show-last-row-warning');
    } else if (warning) {
        warning.style.display = 'none';
        document.body.classList.remove('show-last-row-warning');
    }
}

/**
 * 행 데이터 수집
 */
function collectRowData() {
    const inputs = document.querySelectorAll('#fieldsContainer input, #fieldsContainer textarea');
    const rowData = {};
    inputs.forEach(input => {
        rowData[input.dataset.field] = input.value;
    });
    return rowData;
}

/**
 * 다음 행으로 이동
 */
function nextRow() {
    saveCurrentRow((data) => {
        if (currentIndex + 1 < totalRows) {
            loadRow(currentIndex + 1);
        } else {
            // 마지막 행일 때는 하단 경고만 표시 (상단 메시지는 표시하지 않음)
            updateLastRowWarning();
        }
    });
}

/**
 * 이전 행으로 이동
 */
function previousRow() {
    saveCurrentRow((data) => {
        if (currentIndex > 0) {
            loadRow(currentIndex - 1);
        } else {
            showMessage('첫 번째 행입니다!', true);
        }
    });
}

/**
 * 1단계로 돌아가기
 */
function backToStep1() {
    if (confirm('1단계로 돌아가면 현재 설정이 초기화됩니다. 계속하시겠습니까?')) {
        resetToStep1();
    }
}

/**
 * 1단계로 UI 리셋
 */
function resetToStep1() {
    // 전역 변수 초기화
    currentIndex = 0;
    totalRows = 0;
    headers = [];
    currentStep = 1;
    selectedFileName = '';
    customOutputName = '';
    outputFormat = 'csv';
    
    // UI 초기화
    document.getElementById('step1Section').style.display = 'block';
    document.getElementById('step2Section').style.display = 'none';
    document.getElementById('step3Section').style.display = 'none';
    document.getElementById('fileSelect').value = '';
    document.getElementById('customOutputName').value = '';
    document.getElementById('outputFormat').value = 'csv';
    
    // 마지막 행 경고 숨기기
    updateLastRowWarning();
    
    // 파일 목록 재로드
    loadFileList();
}

/**
 * 3단계로 진행 (열 구조 확정 및 파일 생성)
 */
function proceedToStep3() {
    if (headers.length === 0) {
        showMessage('라벨링할 열이 없습니다. 최소 1개 이상의 열을 추가해주세요.', true);
        return;
    }
    
    outputFormat = document.getElementById('outputFormat').value;
    
    if (confirm('다음 단계로 진행하면 열 구조가 확정되며 더 이상 변경할 수 없습니다. 계속하시겠습니까?')) {
        initializeOutputFile((success) => {
            if (success) {
                currentStep = 3;
                document.getElementById('step2Section').style.display = 'none';
                document.getElementById('step3Section').style.display = 'block';
                document.getElementById('workingFileName').textContent = selectedFileName;
                document.getElementById('workingFormat').textContent = outputFormat.toUpperCase();
                loadRow(0);
                showMessage('3단계로 진입했습니다. 라벨링을 시작하세요!');
            }
        });
    }
}

/**
 * 저장 후 종료
 */
function saveAndFinish() {
    saveCurrentRow((data) => {
        if (confirm('모든 작업이 저장되었습니다. 작업을 종료하고 처음으로 돌아가시겠습니까?')) {
            showMessage('작업이 완료되었습니다! 처음 화면으로 이동합니다.');
            setTimeout(() => {
                resetToStep1();
            }, 1500);
        }
    });
}
