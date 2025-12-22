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
        input[type="number"] {
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
    <h1>📚 책 샘플링 도구</h1>

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
                    <input type="text" id="originalid" placeholder="예: 004-나혜석-경희-여자지계.txt">
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
                <input type="text" id="filename" placeholder="예: R-004-나혜석-경희-여자지계.txt">
            </div>

            <button class="success" onclick="initializeJson()">새 JSON 초기화</button>
            <button class="danger" onclick="resetAll()">전체 초기화</button>
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
            <button class="secondary" onclick="downloadJson()">💾 JSON 다운로드</button>
            <div id="jsonOutput">JSON이 초기화되지 않았습니다. "새 JSON 초기화" 버튼을 눌러주세요.</div>
        </div>
    </div>

    <script>
        // 전역 상태
        let currentJson = null;

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

        // 무작위 샘플 추출
        function getRandomSamples(arr, count) {
            if (arr.length <= count) {
                return [...arr];
            }
            
            const shuffled = [...arr].sort(() => Math.random() - 0.5);
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
            // 각 요소를 하이픈으로 연결
            const filename = `${docidClean}-${authorClean}-${titleClean}-${sourceClean}.txt`;
            document.getElementById('filename').value = filename;
            document.getElementById('originalid').value = `${docNum}_${authorClean}_${titleClean}_${sourceClean}.txt`;
        }

        // JSON 초기화
        function initializeJson() {
            const filename = document.getElementById('filename').value.trim();
            if (!filename) {
                alert('파일명을 먼저 입력하거나 자동 생성해주세요.');
                return;
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
            updateJsonDisplay();
            updateSegmentInfo();
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
            
            alert(`idx ${targetIdx} 세그먼트가 삭제되었습니다.`);
        }

        // 전체 초기화
        function resetAll() {
            if (!confirm('모든 데이터를 초기화하시겠습니까? 저장되지 않은 데이터는 사라집니다.')) {
                return;
            }

            currentJson = null;

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

        // JSON 다운로드
        function downloadJson() {
            if (!currentJson) {
                alert('다운로드할 JSON이 없습니다.');
                return;
            }

            const filename = Object.keys(currentJson)[0].replace('.txt', '.json');
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
    </script>
</body>
</html>
