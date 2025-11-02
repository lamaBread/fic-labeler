# 데이터 라벨링 도구

PHP 내장 웹서버로 동작하는 인문학 연구자를 위한 데이터 라벨링 애플리케이션입니다.

## ✨ 주요 기능

- 📁 **다양한 파일 형식 지원**: CSV, TSV, JSON 읽기/쓰기
- 🎨 **직관적인 웹 인터페이스**: 복잡한 설치 없이 바로 사용
- ➕ **자유로운 열 추가**: 라벨링에 필요한 컬럼을 언제든 추가
- 📝 **행 단위 라벨링**: 이전/다음 버튼으로 편리한 탐색
- 💾 **자동 저장**: 다음 버튼 클릭 시마다 자동 저장
- 🔒 **원본 데이터 보존**: 원본 파일을 손상시키지 않고 안전하게 작업
- 🔄 **유연한 출력**: CSV, TSV, JSON 형식으로 결과 저장

## 📂 프로젝트 구조

```
fic-labeler/
├── index.php                 # 메인 컨트롤러
├── README.md                 # 프로젝트 문서
│
├── _includes/                # PHP 백엔드 로직
│   ├── utils.php            # 유틸리티 함수 (에러 처리, JSON 응답 등)
│   ├── data_reader.php      # 데이터 읽기 함수 (CSV, TSV, JSON)
│   ├── data_writer.php      # 데이터 저장 함수 (CSV, TSV, JSON)
│   └── api_handlers.php     # API 엔드포인트 핸들러
│
├── _assets/                  # 프론트엔드 리소스
│   ├── css/
│   │   └── style.css        # 스타일시트
│   └── js/
│       ├── api.js           # API 통신 함수
│       └── ui.js            # UI 제어 및 이벤트 핸들러
│
├── _views/                   # HTML 템플릿
│   └── index.html.php       # 메인 뷰 템플릿
│
├── data/                     # 원본 데이터 파일 저장소 (자동 생성)
│   ├── sample.csv           # 샘플 CSV 파일
│   └── sample.tsv           # 샘플 TSV 파일
│
└── output/                   # 라벨링 결과 파일 저장소 (자동 생성)
```

## 🚀 설치 및 실행

### 필수 요구사항

- PHP 7.0 이상
- 웹 브라우저 (Chrome, Firefox, Safari, Edge 등)

### 실행 방법

1. **프로젝트 디렉토리로 이동**
```bash
cd fic-labeler
```

2. **PHP 내장 웹서버 실행**
```bash
php -S localhost:8000
```

3. **브라우저에서 접속**
```
http://localhost:8000
```

## 📖 사용 가이드

### 1단계: 데이터 파일 준비

`data` 폴더에 라벨링할 파일을 넣어주세요. 세 가지 형식을 지원합니다:

**CSV 파일 예시** (`data/novel.csv`):
```csv
id,text,author
1,구름이 개면 별이 나옵니다.,김유정
2,산골짜기에 물이 흐릅니다.,이효석
```

**TSV 파일 예시** (`data/novel.tsv`):
```tsv
id	text	author	year
1	메밀꽃 필 무렵 산길을 걷습니다.	이효석	1936
2	소나기가 내린 뒤 햇살이 비춥니다.	황순원	1953
```

**JSON 파일 예시** (`data/novel.json`):
```json
[
  {"id": "1", "text": "첫 번째 문장입니다", "author": "홍길동"},
  {"id": "2", "text": "두 번째 문장입니다", "author": "김철수"}
]
```

### 2단계: 파일 로드

1. 웹 인터페이스에서 드롭다운 메뉴에서 파일 선택
2. "파일 로드" 버튼 클릭
3. 파일의 구조가 자동으로 분석됨

### 3단계: 라벨링 열 추가 (선택사항)

- 필요한 라벨링 컬럼을 추가합니다
  - 예: `감정`, `카테고리`, `시간표현`, `장소`, `메모` 등
- 여러 개의 열을 원하는 만큼 추가 가능
- 기존 데이터의 모든 행에 새 열이 자동으로 추가됨

### 4단계: 데이터 라벨링

1. 각 필드를 검토하고 수정/추가
2. 라벨링 컬럼에 값을 입력
3. **"다음 →" 버튼 클릭**
   - 현재 행이 자동 저장됨
   - 다음 행으로 이동
4. "← 이전" 버튼으로 이전 행 재검토 가능
5. 모든 라벨링 완료 후 "처음으로 돌아가기" 클릭

### 5단계: 결과 확인

`output` 폴더에서 라벨링된 파일을 확인하세요:
- 파일명 형식: `원본파일명_labeled.확장자`
- 예시:
  - `sample.csv` → `sample_labeled.csv`
  - `novel.tsv` → `novel_labeled.tsv`
  - `data.json` → `data_labeled.json`

## 🎯 핵심 특징

### 💾 자동 저장 시스템
- 매 "다음" 버튼 클릭 시 현재 행이 자동으로 저장됩니다
- 브라우저를 닫아도 작업한 내용이 보존됩니다
- 데이터 손실 걱정 없이 안전하게 작업할 수 있습니다

### 🔒 원본 데이터 보호
- 원본 파일은 `data` 폴더에 그대로 유지됩니다
- 라벨링 결과는 `output` 폴더에 별도로 저장됩니다
- 실수해도 원본은 안전합니다

### 🎨 스마트 UI
- **행 번호 표시**: 현재 위치를 명확하게 표시 (예: 5 / 100)
- **자동 필드 타입**: 긴 텍스트는 자동으로 textarea로 표시
- **실시간 피드백**: 저장 성공/실패를 즉시 알림
- **열 태그**: 현재 추가된 열을 한눈에 확인

### 🔄 유연한 형식 지원
- **입력**: CSV, TSV, JSON
- **출력**: 원하는 형식 선택 가능 (CSV, TSV, JSON)
- 입력과 다른 형식으로 출력 가능

## 🏗️ 아키텍처

### 백엔드 (PHP)

#### `_includes/utils.php`
공통 유틸리티 함수
- `sendError($message)` - JSON 에러 응답
- `sendJSON($data)` - JSON 성공 응답
- `ensureDirectories()` - 필요한 디렉토리 생성

#### `_includes/data_reader.php`
데이터 읽기 전담
- `readCSV($filepath, $delimiter)` - CSV 파일 파싱
- `readTSV($filepath)` - TSV 파일 파싱
- `readJSON($filepath)` - JSON 파일 파싱
- `loadDataFile($filename)` - 확장자 기반 자동 로딩

#### `_includes/data_writer.php`
데이터 저장 전담
- `saveCSV($filepath, $headers, $data, $delimiter)` - CSV 저장
- `saveTSV($filepath, $headers, $data)` - TSV 저장
- `saveJSON($filepath, $data)` - JSON 저장
- `saveData($filename, $format, $headers, $data)` - 통합 저장

#### `_includes/api_handlers.php`
API 엔드포인트 핸들러
- `handleLoadFile()` - 파일 로드 처리
- `handleAddColumn()` - 열 추가 처리
- `handleSaveRow()` - 행 저장 처리
- `handleGetRow()` - 행 가져오기 처리
- `handleListFiles()` - 파일 목록 처리

### 프론트엔드 (JavaScript)

#### `_assets/js/api.js`
서버 API 통신
- `loadFileList()` - 파일 목록 가져오기
- `loadFile()` - 파일 로드
- `addColumn()` - 열 추가
- `loadRow(index)` - 특정 행 로드
- `saveCurrentRow(callback)` - 현재 행 저장

#### `_assets/js/ui.js`
사용자 인터페이스 제어
- `updateColumnList()` - 열 목록 UI 업데이트
- `renderFields(rowData)` - 입력 필드 렌더링
- `collectRowData()` - 폼 데이터 수집
- `nextRow()` - 다음 행으로 이동
- `previousRow()` - 이전 행으로 이동

## 🔧 기술 스택

| 레이어 | 기술 |
|--------|------|
| **Backend** | PHP 7.0+ (내장 웹서버) |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript |
| **데이터 저장** | 파일 시스템 (CSV, TSV, JSON) |
| **세션 관리** | PHP Session |
| **아키텍처** | MVC 패턴 (Model-View-Controller) |

## 🐛 문제 해결

### 파일이 목록에 표시되지 않아요
**원인**: 파일이 올바른 위치에 없거나 형식이 맞지 않습니다.

**해결책**:
- `data` 폴더에 파일이 있는지 확인
- 파일 확장자가 `.csv`, `.tsv`, `.json` 인지 확인
- 파일명에 특수문자가 없는지 확인

### 저장이 안 돼요
**원인**: 디렉토리 권한 문제이거나 서버 에러가 발생했습니다.

**해결책**:
- `output` 폴더의 쓰기 권한 확인
- 터미널에서 PHP 에러 로그 확인
- 브라우저 개발자 도구의 Console 탭에서 에러 확인

### 한글이 깨져요
**원인**: 파일 인코딩이 UTF-8이 아닙니다.

**해결책**:
- 파일을 UTF-8 인코딩으로 저장
- CSV의 경우 BOM 없는 UTF-8 권장
- 텍스트 에디터에서 인코딩 변경 후 재저장

### 파일 로드 시 에러가 발생해요
**원인**: 파일 형식이 올바르지 않습니다.

**해결책**:
- CSV: 헤더 행이 있는지 확인
- TSV: 탭으로 구분되어 있는지 확인
- JSON: 객체 배열 형식인지 확인 `[{}, {}]`
- 파일에 빈 행이나 잘못된 구분자가 없는지 확인

## 💡 사용 팁

### 효율적인 라벨링
1. **키보드 단축키**: 마우스 없이 Tab 키로 필드 간 이동
2. **일관성 유지**: 라벨 값을 미리 정의하고 일관되게 사용
3. **중간 저장**: 주기적으로 "처음으로 돌아가기"로 진행 상황 확인

### 대용량 데이터
- 한 번에 너무 많은 행(10,000개 이상)을 로드하지 마세요
- 파일을 나누어서 작업하는 것을 권장합니다
- 브라우저의 메모리 사용량을 주기적으로 확인하세요

### 협업
- `output` 폴더의 결과 파일을 공유
- Git을 사용하는 경우 `data`와 `output` 폴더를 .gitignore에 추가 (언더스코어로 시작하는 시스템 폴더는 자동으로 제외됨)
- 라벨링 가이드라인을 문서화하여 팀과 공유

### 디렉토리 네이밍
- **사용자 디렉토리** (`data`, `output`): 사용자가 직접 접근하는 폴더
- **시스템 디렉토리** (`_includes`, `_assets`, `_views`): 언더스코어로 시작하여 시스템 파일임을 명시

## 📝 라이선스

MIT License

## 👨‍💻 개발자

인문학 연구를 위한 데이터 라벨링 도구

## 🙏 기여

이슈 리포트나 기능 제안은 언제든 환영합니다!
