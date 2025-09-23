<?php
// 영상 파일 디버그 도구
require_once '../vendor/autoload.php';

$app = require_once '../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>영상 파일 디버그 정보</h2>";

// 데이터베이스의 첫 번째 영상 제출 정보
$submission = App\Models\VideoSubmission::first();

if ($submission) {
    echo "<div style='padding: 20px; border: 1px solid #ccc; margin: 20px;'>";
    echo "<h3>데이터베이스 정보:</h3>";
    echo "<p><strong>ID:</strong> {$submission->id}</p>";
    echo "<p><strong>파일 경로:</strong> {$submission->video_file_path}</p>";
    echo "<p><strong>파일명:</strong> {$submission->video_file_name}</p>";
    
    // 파일 존재 확인
    $fullPath = storage_path('app/public/' . $submission->video_file_path);
    echo "<p><strong>전체 경로:</strong> {$fullPath}</p>";
    echo "<p><strong>파일 존재:</strong> " . (file_exists($fullPath) ? '✅ 예' : '❌ 아니오') . "</p>";
    
    if (file_exists($fullPath)) {
        echo "<p><strong>파일 크기:</strong> " . round(filesize($fullPath) / 1024 / 1024, 2) . " MB</p>";
    }
    
    // URL 생성 테스트
    $url = "http://localhost:8080/storage/{$submission->video_file_path}";
    echo "<p><strong>생성된 URL:</strong> <a href='{$url}' target='_blank'>{$url}</a></p>";
    
    // 실제 스토리지 타입 확인
    echo "<p><strong>S3 저장:</strong> " . ($submission->isStoredOnS3() ? '예' : '아니오') . "</p>";
    echo "<p><strong>로컬 저장:</strong> " . ($submission->isStoredLocally() ? '예' : '아니오') . "</p>";
    
    if ($submission->isStoredLocally()) {
        $localUrl = $submission->getLocalVideoUrl();
        echo "<p><strong>로컬 URL:</strong> <a href='{$localUrl}' target='_blank'>{$localUrl}</a></p>";
    }
    
    echo "</div>";
    
    // 실제 비디오 플레이어 테스트
    echo "<div style='padding: 20px; border: 1px solid #ccc; margin: 20px;'>";
    echo "<h3>비디오 플레이어 테스트:</h3>";
    echo "<video controls style='max-width: 600px; width: 100%; aspect-ratio: 16/9;'>";
    echo "<source src='{$url}' type='video/mp4'>";
    echo "브라우저가 비디오를 지원하지 않습니다.";
    echo "</video>";
    echo "</div>";
    
} else {
    echo "<p>영상 제출 데이터가 없습니다.</p>";
}

// 실제 파일 목록
echo "<div style='padding: 20px; border: 1px solid #ccc; margin: 20px;'>";
echo "<h3>실제 파일 목록:</h3>";
$files = glob(storage_path('app/public/videos/*.{mp4,mov}'), GLOB_BRACE);
foreach ($files as $file) {
    $filename = basename($file);
    $size = round(filesize($file) / 1024 / 1024, 2);
    echo "<p>{$filename} ({$size} MB)</p>";
}
echo "</div>";
?>
