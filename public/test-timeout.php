<?php
// 타임아웃 테스트 파일
ini_set('max_execution_time', 0);
set_time_limit(0);

echo "PHP 설정 확인:<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";

echo "<br>타임아웃 테스트 시작...<br>";
flush();

for ($i = 1; $i <= 10; $i++) {
    echo "테스트 진행: {$i}/10<br>";
    flush();
    sleep(1); // 1초 대기
}

echo "<br>✅ 타임아웃 테스트 완료! 문제가 해결되었습니다.";
?>
