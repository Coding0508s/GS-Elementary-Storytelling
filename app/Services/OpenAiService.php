<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAiService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Whisper API를 사용하여 음성 또는 영상을 텍스트로 변환 (대용량 파일 분할 지원)
     */
    public function transcribeAudio($filePath)
    {
        try {
            Log::info('Whisper API 음성 전사 시작', ['file_path' => $filePath]);

            // 파일이 로컬 임시 파일인지 확인
            if (file_exists($filePath)) {
                $fileName = basename($filePath);
                $fileSize = filesize($filePath);
            } else {
                // 스토리지에서 파일 다운로드
                $fileContent = null;
                
                if (Storage::disk('s3')->exists($filePath)) {
                    $fileContent = Storage::disk('s3')->get($filePath);
                } elseif (Storage::disk('public')->exists($filePath)) {
                    $fileContent = Storage::disk('public')->get($filePath);
                } elseif (Storage::exists($filePath)) {
                    $fileContent = Storage::get($filePath);
                }
                
                if (!$fileContent) {
                    throw new \Exception('파일을 가져올 수 없습니다: ' . $filePath);
                }
                
                // 임시 파일 생성
                $filePath = tempnam(sys_get_temp_dir(), 'audio_temp_') . '.wav';
                file_put_contents($filePath, $fileContent);
                $fileName = basename($filePath);
                $fileSize = strlen($fileContent);
            }

            Log::info('오디오 파일 정보', [
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'file_size_mb' => round($fileSize / (1024 * 1024), 2)
            ]);

            // 파일 크기 확인 및 분할 처리
            $maxSize = 20 * 1024 * 1024; // 20MB (안전 여유분)
            
            if ($fileSize > $maxSize) {
                Log::info('대용량 파일 감지. 분할 처리 시작', ['file_size_mb' => round($fileSize / (1024 * 1024), 2)]);
                return $this->transcribeLargeAudio($filePath);
            } else {
                Log::info('일반 크기 파일. 단일 처리');
                return $this->transcribeSingleAudio($filePath);
            }

        } catch (\Exception $e) {
            Log::error('Whisper transcription 오류: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 단일 오디오 파일 전사 (25MB 이하)
     */
    private function transcribeSingleAudio($filePath)
    {
        $fileContent = file_get_contents($filePath);
        $fileName = basename($filePath);

        Log::info('Whisper API 요청', [
            'file_name' => $fileName,
            'file_size' => strlen($fileContent),
            'api_key_set' => !empty($this->apiKey)
        ]);

        $response = Http::timeout(300)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->attach(
            'file', $fileContent, $fileName
        )->post($this->baseUrl . '/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'en',
            'response_format' => 'json',
            'temperature' => 0.2
        ]);

        if ($response->successful()) {
            $result = $response->json();
            $transcription = $result['text'] ?? '';
            
            Log::info('Whisper API 성공', [
                'transcription_length' => strlen($transcription),
                'transcription_preview' => substr($transcription, 0, 100) . '...'
            ]);
            
            return $transcription;
        } else {
            $errorBody = $response->body();
            Log::error('Whisper API 오류', [
                'status' => $response->status(),
                'body' => $errorBody
            ]);
            throw new \Exception('음성 변환에 실패했습니다: ' . $errorBody);
        }
    }

    /**
     * 대용량 오디오 파일 분할 전사 (25MB 초과)
     */
    private function transcribeLargeAudio($filePath)
    {
        try {
            Log::info('대용량 오디오 분할 전사 시작');
            
            // FFmpeg를 사용하여 오디오를 작은 청크로 분할
            $chunks = $this->splitAudioIntoChunks($filePath);
            
            Log::info('오디오 분할 완료', ['chunk_count' => count($chunks)]);
            
            $allTranscriptions = [];
            $chunkIndex = 0;
            
            foreach ($chunks as $chunkPath) {
                $chunkIndex++;
                
                try {
                    Log::info('청크 전사 시작', [
                        'chunk' => $chunkIndex,
                        'total_chunks' => count($chunks),
                        'chunk_size' => filesize($chunkPath)
                    ]);
                    
                    $transcription = $this->transcribeSingleAudio($chunkPath);
                    
                    if (!empty(trim($transcription))) {
                        $allTranscriptions[] = $transcription;
                    }
                    
                    Log::info('청크 전사 완료', [
                        'chunk' => $chunkIndex,
                        'transcription_length' => strlen($transcription)
                    ]);
                    
                } catch (\Exception $e) {
                    Log::warning('청크 전사 실패', [
                        'chunk' => $chunkIndex,
                        'error' => $e->getMessage()
                    ]);
                    // 일부 청크 실패해도 계속 진행
                }
                
                // 청크 파일 삭제
                if (file_exists($chunkPath)) {
                    unlink($chunkPath);
                }
                
                // API 레이트 제한 방지를 위한 대기
                if ($chunkIndex < count($chunks)) {
                    sleep(1);
                }
            }
            
            // 모든 전사 결과를 하나로 합치기
            $finalTranscription = implode(' ', $allTranscriptions);
            
            Log::info('대용량 오디오 전사 완료', [
                'chunk_count' => count($chunks),
                'successful_chunks' => count($allTranscriptions),
                'final_transcription_length' => strlen($finalTranscription)
            ]);
            
            return $finalTranscription;
            
        } catch (\Exception $e) {
            Log::error('대용량 오디오 전사 오류: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 오디오를 작은 청크로 분할
     */
    private function splitAudioIntoChunks($audioFilePath)
    {
        $ffmpegPath = $this->findFFmpegPath();
        
        if (!$ffmpegPath) {
            throw new \Exception('FFmpeg가 설치되지 않아 대용량 파일을 처리할 수 없습니다.');
        }
        
        $chunks = [];
        $chunkDuration = 300; // 5분 청크 (압축 효율성 향상)
        $chunkIndex = 0;
        
        // 오디오 길이 확인
        $durationCommand = sprintf(
            '%s -i %s -f null - 2>&1 | grep "Duration"',
            escapeshellarg($ffmpegPath),
            escapeshellarg($audioFilePath)
        );
        
        $durationOutput = shell_exec($durationCommand);
        Log::info('오디오 길이 확인', ['output' => $durationOutput]);
        
        // Duration: 03:05:21.36 형식에서 초 단위로 변환
        if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $durationOutput, $matches)) {
            $totalSeconds = ($matches[1] * 3600) + ($matches[2] * 60) + floatval($matches[3]);
            $totalChunks = ceil($totalSeconds / $chunkDuration);
            
            Log::info('오디오 분할 정보', [
                'total_seconds' => $totalSeconds,
                'chunk_duration' => $chunkDuration,
                'estimated_chunks' => $totalChunks
            ]);
            
            // 각 청크 생성
            for ($startTime = 0; $startTime < $totalSeconds; $startTime += $chunkDuration) {
                $chunkIndex++;
                $chunkPath = tempnam(sys_get_temp_dir(), "audio_chunk_{$chunkIndex}_") . '.wav';
                
                // MP3 청크로 시도 (파일 크기 최소화)
                $mp3ChunkPath = str_replace('.wav', '.mp3', $chunkPath);
                $command = sprintf(
                    '%s -i %s -ss %d -t %d -vn -acodec libmp3lame -b:a 64k -ar 16000 -ac 1 %s -y 2>&1',
                    escapeshellarg($ffmpegPath),
                    escapeshellarg($audioFilePath),
                    $startTime,
                    $chunkDuration,
                    escapeshellarg($mp3ChunkPath)
                );
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($mp3ChunkPath) && filesize($mp3ChunkPath) > 1000) {
                    $chunkPath = $mp3ChunkPath;
                } else {
                    // MP3 실패 시 WAV로 폴백
                    $command = sprintf(
                        '%s -i %s -ss %d -t %d -vn -acodec pcm_s16le -ar 8000 -ac 1 %s -y 2>&1',
                        escapeshellarg($ffmpegPath),
                        escapeshellarg($audioFilePath),
                        $startTime,
                        $chunkDuration,
                        escapeshellarg($chunkPath)
                    );
                    
                    exec($command, $output, $returnCode);
                }
                
                if (file_exists($chunkPath) && filesize($chunkPath) > 1000) {
                    $chunks[] = $chunkPath;
                    Log::info('청크 생성 성공', [
                        'chunk' => $chunkIndex,
                        'start_time' => $startTime,
                        'chunk_size' => filesize($chunkPath),
                        'chunk_path' => $chunkPath
                    ]);
                } else {
                    Log::warning('청크 생성 실패', [
                        'chunk' => $chunkIndex,
                        'command' => $command,
                        'return_code' => $returnCode,
                        'output' => implode("\n", $output)
                    ]);
                }
            }
        } else {
            throw new \Exception('오디오 파일의 길이를 확인할 수 없습니다.');
        }
        
        if (empty($chunks)) {
            throw new \Exception('오디오 청크를 생성할 수 없습니다.');
        }
        
        return $chunks;
    }

    /**
     * ChatGPT API를 사용하여 영어 발표 평가
     */
    public function evaluateEnglishPresentation($transcription)
    {
        try {
            $prompt = $this->buildEvaluationPrompt($transcription);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert English language teacher evaluating student presentations. Please provide detailed, constructive feedback.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $this->parseEvaluationResponse($result['choices'][0]['message']['content']);
            } else {
                Log::error('ChatGPT API 오류: ' . $response->body());
                throw new \Exception('AI 평가에 실패했습니다: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('ChatGPT evaluation 오류: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 평가 프롬프트 생성
     */
    private function buildEvaluationPrompt($transcription)
    {
        return "Please evaluate this English presentation transcript and provide scores and feedback:

TRANSCRIPT:
{$transcription}

EVALUATION CRITERIA (each out of 10 points):
1. Pronunciation and Natural Intonation & Delivery (10 points)
2. Proper Vocabulary and Expression Usage (10 points)
3. Fluency Level (10 points)

Please provide your evaluation in the following JSON format:
{
    \"pronunciation_score\": [0-10],
    \"vocabulary_score\": [0-10],
    \"fluency_score\": [0-10],
    \"detailed_feedback\": \"Detailed English feedback explaining strengths and areas for improvement for each criterion\"
}

Important notes:
- Base your evaluation on the text quality, grammar, vocabulary usage, and apparent fluency
- Provide constructive feedback in English
- Be fair but thorough in your assessment
- Consider this is a student presentation, so provide encouraging yet honest feedback";
    }

    /**
     * AI 응답 파싱
     */
    private function parseEvaluationResponse($response)
    {
        try {
            Log::info('AI 응답 파싱 시작', ['response_length' => strlen($response)]);
            
            // 방법 1: 전체 응답이 JSON인 경우
            $evaluation = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($evaluation['pronunciation_score'])) {
                Log::info('전체 JSON 파싱 성공');
                return [
                    'pronunciation_score' => max(0, min(10, (int)$evaluation['pronunciation_score'])),
                    'vocabulary_score' => max(0, min(10, (int)$evaluation['vocabulary_score'])),
                    'fluency_score' => max(0, min(10, (int)$evaluation['fluency_score'])),
                    'ai_feedback' => $evaluation['detailed_feedback'] ?? 'No detailed feedback provided.'
                ];
            }
            
            // 방법 2: JSON이 텍스트에 포함된 경우 (마크다운 코드 블록 등)
            // ```json ... ``` 형식 제거
            $cleanedResponse = preg_replace('/```json\s*|\s*```/', '', $response);
            $evaluation = json_decode($cleanedResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($evaluation['pronunciation_score'])) {
                Log::info('마크다운 제거 후 JSON 파싱 성공');
                return [
                    'pronunciation_score' => max(0, min(10, (int)$evaluation['pronunciation_score'])),
                    'vocabulary_score' => max(0, min(10, (int)$evaluation['vocabulary_score'])),
                    'fluency_score' => max(0, min(10, (int)$evaluation['fluency_score'])),
                    'ai_feedback' => $evaluation['detailed_feedback'] ?? 'No detailed feedback provided.'
                ];
            }
            
            // 방법 3: JSON 부분만 추출
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $evaluation = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($evaluation['pronunciation_score'])) {
                    Log::info('부분 JSON 추출 후 파싱 성공');
                    return [
                        'pronunciation_score' => max(0, min(10, (int)$evaluation['pronunciation_score'])),
                        'vocabulary_score' => max(0, min(10, (int)$evaluation['vocabulary_score'])),
                        'fluency_score' => max(0, min(10, (int)$evaluation['fluency_score'])),
                        'ai_feedback' => $evaluation['detailed_feedback'] ?? 'No detailed feedback provided.'
                    ];
                }
            }
            
            // 방법 4: 정규식으로 점수와 피드백 추출 시도
            $scores = [];
            if (preg_match('/"pronunciation_score"\s*:\s*(\d+)/', $response, $matches)) {
                $scores['pronunciation_score'] = (int)$matches[1];
            }
            if (preg_match('/"vocabulary_score"\s*:\s*(\d+)/', $response, $matches)) {
                $scores['vocabulary_score'] = (int)$matches[1];
            }
            if (preg_match('/"fluency_score"\s*:\s*(\d+)/', $response, $matches)) {
                $scores['fluency_score'] = (int)$matches[1];
            }
            if (preg_match('/"detailed_feedback"\s*:\s*"([^"]+(?:\\.[^"]*)*)"/', $response, $matches)) {
                $scores['ai_feedback'] = str_replace('\\"', '"', $matches[1]);
            }
            
            if (count($scores) >= 3) {
                Log::info('정규식으로 점수 추출 성공', ['scores' => $scores]);
                return [
                    'pronunciation_score' => max(0, min(10, $scores['pronunciation_score'] ?? 5)),
                    'vocabulary_score' => max(0, min(10, $scores['vocabulary_score'] ?? 5)),
                    'fluency_score' => max(0, min(10, $scores['fluency_score'] ?? 5)),
                    'ai_feedback' => $scores['ai_feedback'] ?? 'Feedback extraction failed.'
                ];
            }
            
            // 모든 방법 실패 시
            Log::error('AI 응답 파싱 실패', [
                'response_preview' => substr($response, 0, 500),
                'json_error' => json_last_error_msg()
            ]);
            
            return [
                'pronunciation_score' => 5,
                'vocabulary_score' => 5,
                'fluency_score' => 5,
                'ai_feedback' => 'Unable to parse AI evaluation. Please contact support with this error.'
            ];

        } catch (\Exception $e) {
            Log::error('AI 응답 파싱 예외 발생', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'pronunciation_score' => 5,
                'vocabulary_score' => 5,
                'fluency_score' => 5,
                'ai_feedback' => 'Error parsing AI response. Please try again.'
            ];
        }
    }

    /**
     * 전체 AI 평가 프로세스
     */
    public function evaluateVideo($videoFilePath)
    {
        try {
            Log::info('AI 영상 평가 시작', ['video_path' => $videoFilePath]);
            $totalStartTime = microtime(true);

            // 1단계: 영상에서 오디오 추출
            $extractStartTime = microtime(true);
            $audioFilePath = $this->extractAudioFromVideo($videoFilePath);
            $extractEndTime = microtime(true);
            Log::info('오디오 추출 완료', [
                'audio_path' => $audioFilePath,
                'extraction_time' => round($extractEndTime - $extractStartTime, 2) . ' seconds'
            ]);
            
            // 2단계: 음성을 텍스트로 변환
            $transcribeStartTime = microtime(true);
            $transcription = $this->transcribeAudio($audioFilePath);
            $transcribeEndTime = microtime(true);
            Log::info('음성 전사 완료', [
                'transcription_length' => strlen($transcription),
                'transcription_time' => round($transcribeEndTime - $transcribeStartTime, 2) . ' seconds'
            ]);
            
            // 임시 오디오 파일 삭제
            if (file_exists($audioFilePath)) {
                unlink($audioFilePath);
            }
            
            if (empty($transcription)) {
                throw new \Exception('음성 변환 결과가 비어있습니다.');
            }

            // 3단계: 텍스트를 바탕으로 평가
            $evaluateStartTime = microtime(true);
            $evaluation = $this->evaluateEnglishPresentation($transcription);
            $evaluateEndTime = microtime(true);
            Log::info('AI 평가 완료', array_merge($evaluation, [
                'evaluation_time' => round($evaluateEndTime - $evaluateStartTime, 2) . ' seconds'
            ]));
            
            $totalEndTime = microtime(true);
            Log::info('전체 AI 평가 완료', [
                'total_time' => round($totalEndTime - $totalStartTime, 2) . ' seconds'
            ]);
            
            // 4단계: 결과 합치기
            return array_merge($evaluation, [
                'transcription' => $transcription
            ]);

        } catch (\Exception $e) {
            Log::error('AI  영상 평가 오류: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 영상에서 오디오 추출
     */
    private function extractAudioFromVideo($videoFilePath)
    {
        try {
            Log::info('영상에서 오디오 추출 시작', ['video_path' => $videoFilePath]);

            // S3에서 영상 파일 다운로드
            $videoContent = null;
            
            if (Storage::disk('s3')->exists($videoFilePath)) {
                $videoContent = Storage::disk('s3')->get($videoFilePath);
            } elseif (Storage::disk('public')->exists($videoFilePath)) {
                $videoContent = Storage::disk('public')->get($videoFilePath);
            } elseif (Storage::exists($videoFilePath)) {
                $videoContent = Storage::get($videoFilePath);
            } else {
                throw new \Exception('영상 파일을 찾을 수 없습니다: ' . $videoFilePath);
            }

            if (!$videoContent) {
                throw new \Exception('영상 파일 내용을 가져올 수 없습니다.');
            }

            // 임시 영상 파일 생성
            $tempVideoFile = tempnam(sys_get_temp_dir(), 'video_') . '.mp4';
            file_put_contents($tempVideoFile, $videoContent);

            // 임시 오디오 파일 경로
            $tempAudioFile = tempnam(sys_get_temp_dir(), 'audio_') . '.wav';

            // FFmpeg를 사용한 오디오 추출 (최적화된 설정으로 파일 크기 감소)
            $ffmpegPath = $this->findFFmpegPath();
            
            if ($ffmpegPath) {
                // 먼저 MP3로 시도 (크기 효율성)
                $mp3AudioFile = str_replace('.wav', '.mp3', $tempAudioFile);
                $command = sprintf(
                    '%s -i %s -vn -acodec libmp3lame -b:a 64k -ar 16000 -ac 1 %s 2>&1',
                    escapeshellarg($ffmpegPath),
                    escapeshellarg($tempVideoFile),
                    escapeshellarg($mp3AudioFile)
                );
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($mp3AudioFile)) {
                    $tempAudioFile = $mp3AudioFile;
                    Log::info('MP3 오디오 추출 성공', ['audio_file' => $tempAudioFile]);
                } else {
                    // MP3 실패 시 WAV로 폴백 (더 높은 압축률 적용)
                    $command = sprintf(
                        '%s -i %s -vn -acodec pcm_s16le -ar 8000 -ac 1 %s 2>&1',
                        escapeshellarg($ffmpegPath),
                        escapeshellarg($tempVideoFile),
                        escapeshellarg($tempAudioFile)
                    );
                    
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0 && file_exists($tempAudioFile)) {
                        Log::info('WAV 오디오 추출 성공 (낮은 품질)', ['audio_file' => $tempAudioFile]);
                    }
                }

                // 임시 영상 파일 삭제
                unlink($tempVideoFile);

                if (file_exists($tempAudioFile)) {
                    Log::info('오디오 추출 성공', ['audio_file' => $tempAudioFile]);
                    return $tempAudioFile;
                }
            }

            // FFmpeg 실패 시 대체 방법: 영상 파일을 직접 Whisper에 전송
            Log::warning('FFmpeg를 사용한 오디오 추출 실패. 영상 파일을 직접 사용합니다.');
            return $tempVideoFile;

        } catch (\Exception $e) {
            Log::error('오디오 추출 오류: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * FFmpeg 경로 찾기
     */
    private function findFFmpegPath()
    {
        $possiblePaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            'ffmpeg', // PATH에 있는 경우
        ];

        foreach ($possiblePaths as $path) {
            if (shell_exec("which $path 2>/dev/null")) {
                return $path;
            }
        }

        return null;
    }
}
