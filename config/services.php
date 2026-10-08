<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'solapi' => [
        'api_key' => env('SOLAPI_API_KEY'),
        'api_secret' => env('SOLAPI_API_SECRET'),
        'from_number' => env('SOLAPI_FROM_NUMBER'),
        // 컨트롤러에서 env()를 직접 부르지 않도록 발송 방식도 여기서 읽습니다.
        'sync' => env('SMS_SYNC_MODE', true),
        // 알림톡 템플릿이 승인된 뒤에 채널 ID와 템플릿 ID를 넣습니다.
        'kakao_pf_id' => env('SOLAPI_KAKAO_PF_ID'),
        // 예전 키입니다. 신청 완료 템플릿 ID가 비어 있으면 이 값을 사용합니다.
        'kakao_template_id' => env('SOLAPI_KAKAO_TEMPLATE_ID'),
        'kakao_template_completed' => env('SOLAPI_KAKAO_TEMPLATE_COMPLETED'),
        'kakao_template_morning' => env('SOLAPI_KAKAO_TEMPLATE_MORNING'),
        'kakao_template_entry' => env('SOLAPI_KAKAO_TEMPLATE_ENTRY'),
        'kakao_template_otp' => env('SOLAPI_KAKAO_TEMPLATE_OTP'),
        // 템플릿 치환 문구와 글자가 같아야 합니다. 예: #{신청자명}
        'kakao_var_applicant_name' => env('SOLAPI_KAKAO_VAR_APPLICANT', '#{신청자명}'),
        'kakao_var_receipt_number' => env('SOLAPI_KAKAO_VAR_RECEIPT', '#{접수번호}'),
        'kakao_var_entry_link' => env('SOLAPI_KAKAO_VAR_ENTRY_LINK', '#{입장링크}'),
        'kakao_var_otp' => env('SOLAPI_KAKAO_VAR_OTP', '#{인증번호}'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

];
