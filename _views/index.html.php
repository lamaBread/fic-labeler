<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 라벨링 도구</title>
    <link rel="stylesheet" href="_assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>📝 데이터 라벨링 도구</h1>
        
        <div id="message" class="message"></div>
        
        <!-- 1단계: 파일 로드 섹션 -->
        <div id="step1Section" class="section">
            <h2>1단계: 데이터 파일 선택</h2>
            <div class="form-group">
                <label>data 폴더에서 파일 선택:</label>
                <select id="fileSelect">
                    <option value="">-- 파일 선택 --</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>출력 파일명 (선택사항):</label>
                <input type="text" id="customOutputName" placeholder="예: result (비어있으면 원본명_labeled 사용)">
                <small style="display: block; margin-top: 5px; color: #666;">
                    💡 확장자를 제외한 파일명만 입력하세요. 비워두면 원본 파일명에 _labeled가 자동으로 붙습니다.
                </small>
            </div>
            <button onclick="loadFile()">파일 로드</button>
            <p style="margin-top: 15px; color: #666; font-size: 13px;">
                💡 CSV, TSV, JSON 파일을 data 폴더에 넣어주세요.
            </p>
        </div>
        
        <!-- 2단계: 열 추가 섹션 -->
        <div id="step2Section" class="section" style="display: none;">
            <h2>2단계: 라벨링 열 설정</h2>
            <p style="color: #666; margin-bottom: 15px;">
                📋 현재 로드된 파일: <strong id="loadedFileName">-</strong>
            </p>
            
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 10px; color: #555;">현재 열 목록</h3>
                <p style="font-size: 13px; color: #888; margin-bottom: 10px;">
                    💡 드래그하여 순서를 변경하거나 버튼을 사용하여 조정할 수 있습니다.
                </p>
                <div id="columnManagementList" class="column-management-list"></div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>새 열 추가:</label>
                <input type="text" id="newColumnName" placeholder="예: 감정, 카테고리, 메모 등">
            </div>
            <button onclick="addColumn()">+ 열 추가</button>
            
            <div class="form-group" style="margin-top: 25px;">
                <label>저장 형식:</label>
                <select id="outputFormat">
                    <option value="csv">CSV</option>
                    <option value="tsv">TSV</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            
            <div style="margin-top: 25px;">
                <button class="secondary" onclick="backToStep1()">← 1단계로 돌아가기</button>
                <button class="success" onclick="proceedToStep3()" style="float: right;">다음: 라벨링 시작 →</button>
                <div style="clear: both;"></div>
            </div>
        </div>
        
        <!-- 3단계: 라벨링 섹션 -->
        <div id="step3Section" class="labeling-area" style="display: none;">
            <div class="section">
                <h2>3단계: 데이터 라벨링</h2>
                <p style="color: #666; margin-bottom: 15px;">
                    💾 작업 중인 파일: <strong id="workingFileName">-</strong> 
                    <span style="margin-left: 15px;">형식: <strong id="workingFormat">-</strong></span>
                </p>
                <div class="row-info">
                    <span id="rowInfo">행: 0 / 0</span>
                </div>
                
                <div id="fieldsContainer"></div>
                
                <div class="navigation">
                    <button class="secondary" onclick="previousRow()">← 이전 행</button>
                    <button onclick="saveAndFinish()">저장 후 종료</button>
                    <button class="success" onclick="nextRow()"> 다음 행 →</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 마지막 행 경고 메시지 -->
    <div id="lastRowWarning" class="last-row-warning" style="display: none;">
        ⚠️ 마지막 행입니다! 작성이 완료되면 꼭 저장 및 종료를 눌러주세요.
    </div>
    
    <script src="_assets/js/api.js"></script>
    <script src="_assets/js/ui.js"></script>
</body>
</html>
