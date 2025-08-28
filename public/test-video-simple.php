<!DOCTYPE html>
<html>
<head>
    <title>영상 재생 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .video-container { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        video { max-width: 600px; width: 100%; }
    </style>
</head>
<body>
    <h1>영상 재생 테스트</h1>
    
    <div class="video-container">
        <h3>테스트 영상 1</h3>
        <video controls>
            <source src="/storage/videos/1753858648_k9zJAUo1u6.mp4" type="video/mp4">
            브라우저가 영상을 지원하지 않습니다.
        </video>
        <p>파일: 1753858648_k9zJAUo1u6.mp4</p>
    </div>
    
    <div class="video-container">
        <h3>테스트 영상 2</h3>
        <video controls>
            <source src="/storage/videos/1753858730_UfKiSDl1IC.mp4" type="video/mp4">
            브라우저가 영상을 지원하지 않습니다.
        </video>
        <p>파일: 1753858730_UfKiSDl1IC.mp4</p>
    </div>
    
    <div class="video-container">
        <h3>직접 링크 테스트</h3>
        <a href="/storage/videos/1753858648_k9zJAUo1u6.mp4" target="_blank">영상 직접 링크</a>
    </div>
    
    <script>
        // 영상 로드 에러 처리
        document.querySelectorAll('video').forEach(video => {
            video.addEventListener('error', function() {
                console.error('영상 로드 오류:', this.currentSrc);
                this.nextElementSibling.innerHTML += '<br><span style="color: red;">❌ 영상 로드 실패</span>';
            });
            
            video.addEventListener('loadeddata', function() {
                console.log('영상 로드 성공:', this.currentSrc);
                this.nextElementSibling.innerHTML += '<br><span style="color: green;">✅ 영상 로드 성공</span>';
            });
        });
    </script>
</body>
</html>
