-- ========================================
-- Speak and Shine 2025 데이터베이스 설정
-- ========================================

-- 프로덕션 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS storytelling_contest_prod 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- 애플리케이션 전용 사용자 생성
CREATE USER IF NOT EXISTS 'storytelling_user'@'localhost' 
    IDENTIFIED BY 'CHANGE_THIS_TO_SECURE_PASSWORD';

-- 권한 부여
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, LOCK TABLES 
    ON storytelling_contest_prod.* 
    TO 'storytelling_user'@'localhost';

-- 백업용 읽기 전용 사용자 생성
CREATE USER IF NOT EXISTS 'storytelling_backup'@'localhost' 
    IDENTIFIED BY 'CHANGE_THIS_TO_BACKUP_PASSWORD';

-- 백업용 사용자에게 읽기 권한만 부여
GRANT SELECT, LOCK TABLES 
    ON storytelling_contest_prod.* 
    TO 'storytelling_backup'@'localhost';

-- 변경사항 적용
FLUSH PRIVILEGES;

-- 데이터베이스 선택
USE storytelling_contest_prod;

-- 인덱스 최적화를 위한 설정
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_log_file_size = 268435456;     -- 256MB

-- 성능 최적화 설정
SET GLOBAL query_cache_type = 1;
SET GLOBAL query_cache_size = 67108864; -- 64MB

-- 연결 설정 최적화
SET GLOBAL max_connections = 200;
SET GLOBAL connect_timeout = 60;
SET GLOBAL wait_timeout = 300;
SET GLOBAL interactive_timeout = 300;

-- 로그 설정
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 2;
SET GLOBAL log_queries_not_using_indexes = 1;

SHOW DATABASES;
SHOW GRANTS FOR 'storytelling_user'@'localhost';
SHOW GRANTS FOR 'storytelling_backup'@'localhost';

-- ========================================
-- 실행 후 확인 명령어들:
-- ========================================
-- 
-- 1. 데이터베이스 연결 테스트:
--    mysql -u storytelling_user -p storytelling_contest_prod
-- 
-- 2. Laravel 마이그레이션 실행:
--    php artisan migrate --force
-- 
-- 3. 시더 실행:
--    php artisan db:seed --class=AdminSeeder
--    php artisan db:seed --class=InstitutionSeeder
-- 
-- 4. 데이터베이스 상태 확인:
--    php artisan migrate:status
--
