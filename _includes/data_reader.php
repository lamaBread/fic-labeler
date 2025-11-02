<?php
/**
 * 데이터 읽기 함수들
 */

/**
 * CSV 파일 읽기
 */
function readCSV($filepath, $delimiter = ',') {
    $data = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        $headers = fgetcsv($handle, 0, $delimiter);
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
        return ['headers' => $headers, 'data' => $data];
    }
    return null;
}

/**
 * TSV 파일 읽기
 */
function readTSV($filepath) {
    return readCSV($filepath, "\t");
}

/**
 * JSON 파일 읽기
 */
function readJSON($filepath) {
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    if (!is_array($data) || empty($data)) return null;
    
    // JSON이 객체 배열인 경우
    if (isset($data[0]) && is_array($data[0])) {
        $headers = array_keys($data[0]);
        return ['headers' => $headers, 'data' => $data];
    }
    return null;
}

/**
 * 데이터 파일 로드 (확장자에 따라 적절한 함수 호출)
 */
function loadDataFile($filename) {
    $filepath = DATA_DIR . '/' . $filename;
    if (!file_exists($filepath)) return null;
    
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'csv':
            return readCSV($filepath);
        case 'tsv':
            return readTSV($filepath);
        case 'json':
            return readJSON($filepath);
        default:
            return null;
    }
}
