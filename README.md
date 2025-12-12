# 소설 시간흐름 라벨링 시스템 - Docker 배포

Korean Fiction Temporal Annotation System (KFTAS) 배포용 패키지입니다.

## 📋 시스템 요구사항

- Docker & Docker Compose
- 리버스 프록시 (Nginx Proxy Manager, Traefik 등)

## 🚀 빠른 시작

### 1. 환경 변수 설정

`.env.example` 파일을 복사하여 `.env` 파일을 생성하고 관리자 키를 설정하세요:

```bash
cp .env.example .env
nano .env  # 또는 원하는 에디터로 편집
```

`.env` 파일 내용:
```env
ADMIN_KEY=your_secure_admin_key_here
TZ=Asia/Seoul
```

⚠️ **중요**: `.env` 파일은 Git에 커밋되지 않으며, 서버에서만 관리됩니다.

### 2. Docker Compose로 실행

```bash
cd Deploy
docker-compose up -d
```

기본적으로 포트 8080에서 서비스가 실행됩니다.

### 3. 초기 설정

브라우저에서 `http://localhost:8080/setup.php`에 접속하여 초기 설정을 완료합니다.

### 4. 리버스 프록시 설정

서브도메인으로 접속하려면 리버스 프록시를 설정하세요.

#### Nginx Proxy Manager 예시

- **Domain**: `labeler.yourdomain.com`
- **Forward Hostname/IP**: `labeler-web` (또는 컨테이너 IP)
- **Forward Port**: `80`

#### Traefik 사용 시

`docker-compose.yml`의 labels 섹션 주석을 해제하고 도메인을 수정하세요:

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.labeler.rule=Host(`labeler.yourdomain.com`)"
  - "traefik.http.routers.labeler.entrypoints=websecure"
  - "traefik.http.routers.labeler.tls.certresolver=letsencrypt"
```

## 📁 디렉토리 구조

```
Deploy/
├── docker-compose.yml      # Docker Compose 설정
├── .env.example            # 환경 변수 예제 파일
├── .env                    # 환경 변수 (직접 생성, Git 제외)
├── html/                   # 웹 애플리케이션 파일
│   ├── config.php          # 설정 파일 (환경변수에서 키 로드)
│   ├── api.php             # API 엔드포인트
│   ├── index.html          # 로그인 페이지
│   ├── dashboard.html      # 대시보드
│   ├── labeling.html       # 라벨링 작업 페이지
│   ├── admin.html          # 관리자 페이지
│   ├── setup.php           # 초기 설정 스크립트
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── common.js
├── data/                   # 데이터 디렉토리 (볼륨 마운트)
│   ├── master_passages.json    # 마스터 데이터
│   ├── users.json              # 사용자 정보
│   └── labelers/               # 라벨러별 작업 데이터
└── README.md
```

## 🔧 관리자 기능

1. **관리자 페이지 접속**: `https://labeler.yourdomain.com/admin.html`
2. **라벨러 추가**: 별명 입력 후 "추가" 버튼 클릭
3. **작품 추가**: JSON 파일 업로드 (sampled_passages.json 형식)
4. **진행 현황 확인**: 전체 라벨러의 작업 진행률 확인
5. **결과 내보내기**: ZIP 파일로 모든 라벨링 결과 다운로드

## 📊 데이터 형식

### master_passages.json

```json
{
  "R_001_작품명.txt": {
    "metadata": {
      "docid": "R_001",
      "title": "작품명",
      "author": "작가명"
    },
    "segments": [
      {
        "idx": 0,
        "text": "문장 내용...",
        "char_count": 100,
        "word_count": 20,
        "narratedtime": null,
        "ellipsistime": null,
        "subjectivetime": null
      }
    ]
  }
}
```

## 🔒 보안 권장사항

1. **ADMIN_KEY 설정**: `.env` 파일에서 안전한 키를 설정하세요 (코드에 하드코딩 금지!)
2. **.env 파일 보호**: 파일 권한을 600으로 설정 (`chmod 600 .env`)
3. **HTTPS 사용**: 리버스 프록시에서 SSL/TLS 인증서를 설정하세요
4. **방화벽 설정**: 필요한 포트만 외부에 노출하세요
5. **정기 백업**: data 디렉토리를 정기적으로 백업하세요

## 🛠 유지보수

### 로그 확인

```bash
docker-compose logs -f labeler-web
```

### 컨테이너 재시작

```bash
docker-compose restart
```

### 데이터 백업

```bash
cp -r data/ backup_$(date +%Y%m%d)/
```

### 업데이트

```bash
docker-compose pull
docker-compose up -d
```

## 📞 문제 해결

### 세션이 유지되지 않는 경우

- PHP 세션 디렉토리 권한 확인
- 리버스 프록시의 쿠키 전달 설정 확인

### 파일 업로드가 안 되는 경우

- PHP upload_max_filesize 설정 확인
- data 디렉토리 쓰기 권한 확인

### ZIP 다운로드가 안 되는 경우

- PHP zip 확장 모듈 설치 여부 확인 (docker-compose.yml에서 자동 설치됨)

---

Based on Ted Underwood's "Why Literary Time is Measured in Minutes"
