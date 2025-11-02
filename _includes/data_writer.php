<?php
/**
 * 데이터 저장 함수들
 */

/**
 * CSV로 저장
 */
function saveCSV($filepath, $headers, $data, $delimiter = ',') {
    if (($handle = fopen($filepath, 'w')) !== false) {
        fputcsv($handle, $headers, $delimiter);
        foreach ($data as $row) {
            $rowData = [];
            foreach ($headers as $header) {
                $rowData[] = isset($row[$header]) ? $row[$header] : '';
            }
            fputcsv($handle, $rowData, $delimiter);
        }
        fclose($handle);
        return true;
    }
    return false;
}

/**
 * TSV로 저장
 */
function saveTSV($filepath, $headers, $data) {
    return saveCSV($filepath, $headers, $data, "\t");
}

/**
 * JSON으로 저장
 */
function saveJSON($filepath, $data) {
    $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filepath, $content) !== false;
}

/**
 * 출력 파일명 생성
 */
function generateOutputFilename($filename, $format, $customName = '') {
    if (!empty($customName)) {
        // 사용자 지정 파일명 사용
        return $customName . '.' . $format;
    }
    // 기본: 원본 파일명 + _labeled
    return pathinfo($filename, PATHINFO_FILENAME) . '_labeled.' . $format;
}

/**
 * 데이터 저장 (형식에 따라 적절한 함수 호출)
 */
function saveData($filename, $format, $headers, $data, $customOutputName = '') {
    $outputPath = OUTPUT_DIR . '/' . generateOutputFilename($filename, $format, $customOutputName);
    
    switch ($format) {
        case 'csv':
            return saveCSV($outputPath, $headers, $data);
        case 'tsv':
            return saveTSV($outputPath, $headers, $data);
        case 'json':
            return saveJSON($outputPath, $data);
        default:
            return false;
    }
}
