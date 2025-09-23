<?php
// 영상 URL 테스트 파일
echo "<h2>영상 파일 URL 테스트</h2>";

$videoFiles = [
    'videos/1753858648_k9zJAUo1u6.mp4',
    'videos/1753858730_UfKiSDl1IC.mp4',
];

foreach ($videoFiles as $file) {
    $url = 'http://localhost:8080/storage/' . $file;
    echo "<div style='margin: 20px; padding: 15px; border: 1px solid #ccc;'>";
    echo "<h3>파일: {$file}</h3>";
    echo "<p><strong>URL:</strong> <a href='{$url}' target='_blank'>{$url}</a></p>";
    
    // 파일 존재 확인
    $filePath = '../storage/app/public/' . $file;
    if (file_exists($filePath)) {
        echo "<p style='color: green;'>✅ 파일 존재함</p>";
        echo "<p>파일 크기: " . round(filesize($filePath) / 1024 / 1024, 2) . " MB</p>";
        
        // 비디오 플레이어
        echo "<video controls style='max-width: 400px; width: 100%; aspect-ratio: 16/9;'>";
        echo "<source src='{$url}' type='video/mp4'>";
        echo "브라우저가 비디오를 지원하지 않습니다.";
        echo "</video>";
    } else {
        echo "<p style='color: red;'>❌ 파일 없음</p>";
    }
    echo "</div>";
}
?>
