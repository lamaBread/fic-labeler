<?php
/**
 * 초기 설정 스크립트
 * 데이터 디렉토리를 생성하고 초기 파일들을 설정합니다.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>라벨링 시스템 초기 설정</h1>";

// 데이터 디렉토리 생성
$directories = [
    DATA_PATH,
    LABELERS_PATH
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p>✅ 디렉토리 생성: {$dir}</p>";
        } else {
            echo "<p>❌ 디렉토리 생성 실패: {$dir}</p>";
        }
    } else {
        echo "<p>📁 디렉토리 존재: {$dir}</p>";
    }
}

// 마스터 JSON 파일 확인
if (!file_exists(MASTER_JSON)) {
    echo "<p>⚠️ 마스터 JSON 파일이 없습니다: " . MASTER_JSON . "</p>";
    echo "<p>관리자 페이지에서 JSON 파일을 업로드하여 작품을 추가하세요.</p>";
} else {
    echo "<p>📄 마스터 JSON 파일 존재: " . MASTER_JSON . "</p>";
}

// 사용자 파일 확인
if (!file_exists(USERS_JSON)) {
    file_put_contents(USERS_JSON, json_encode([], JSON_PRETTY_PRINT));
    echo "<p>✅ 사용자 파일 생성: " . USERS_JSON . "</p>";
} else {
    echo "<p>👥 사용자 파일 존재: " . USERS_JSON . "</p>";
}

// 마스터 JSON 통계
if (file_exists(MASTER_JSON)) {
    $masterData = json_decode(file_get_contents(MASTER_JSON), true);
    if ($masterData) {
        $docCount = count($masterData);
        $totalSegments = 0;
        
        foreach ($masterData as $docData) {
            if (isset($docData['segments'])) {
                $totalSegments += count($docData['segments']);
            }
        }
        
        echo "<hr>";
        echo "<h2>마스터 데이터 통계</h2>";
        echo "<ul>";
        echo "<li>작품 수: {$docCount}</li>";
        echo "<li>총 세그먼트 수: {$totalSegments}</li>";
        echo "</ul>";
    }
}

echo "<hr>";
echo "<h2>시스템 정보</h2>";
echo "<ul>";
echo "<li><strong>관리자 키:</strong> config.php 파일에서 ADMIN_KEY를 확인하세요.</li>";
echo "<li><strong>관리자 키 변경:</strong> config.php 파일에서 ADMIN_KEY 상수를 수정하세요.</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.html'>→ 라벨링 시스템으로 이동</a></p>";
echo "<p><a href='admin.html'>→ 관리자 페이지로 이동</a></p>";
?>
