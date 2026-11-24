-- AmeriMapa — esquema MySQL 8 gerado a partir das migrations Laravel (database/migrations).
-- Prefira: php artisan migrate --seed. Use este arquivo só para importar no MySQL Workbench/phpMyAdmin.
CREATE DATABASE IF NOT EXISTS amerimapa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE amerimapa;
SET FOREIGN_KEY_CHECKS = 0;

-- 0001_01_01_000000_create_users_table.php
create table `players` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(120) not null, `nickname` varchar(60) null, `email` varchar(255) not null, `password_hash` varchar(255) not null, `role` enum('STUDENT', 'TEACHER', 'ADMIN') not null default 'STUDENT', `avatar` varchar(40) not null default 'sol', `remember_token` varchar(100) null, `last_login_at` timestamp null, `created_at` timestamp not null default CURRENT_TIMESTAMP, `updated_at` timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `players` add unique `players_email_unique`(`email`);
create table `password_reset_tokens` (`email` varchar(255) not null, `token` varchar(255) not null, `created_at` timestamp null, primary key (`email`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
create table `sessions` (`id` varchar(255) not null, `user_id` bigint unsigned null, `ip_address` varchar(45) null, `user_agent` text null, `payload` longtext not null, `last_activity` int not null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `sessions` add index `sessions_user_id_index`(`user_id`);
alter table `sessions` add index `sessions_last_activity_index`(`last_activity`);

-- 0001_01_01_000001_create_cache_table.php
create table `cache` (`key` varchar(255) not null, `value` mediumtext not null, `expiration` bigint not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `cache` add index `cache_expiration_index`(`expiration`);
create table `cache_locks` (`key` varchar(255) not null, `owner` varchar(255) not null, `expiration` bigint not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `cache_locks` add index `cache_locks_expiration_index`(`expiration`);

-- 0001_01_01_000002_create_jobs_table.php
create table `jobs` (`id` bigint unsigned not null auto_increment primary key, `queue` varchar(255) not null, `payload` longtext not null, `attempts` smallint unsigned not null, `reserved_at` int unsigned null, `available_at` int unsigned not null, `created_at` int unsigned not null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `jobs` add index `jobs_queue_index`(`queue`);
create table `job_batches` (`id` varchar(255) not null, `name` varchar(255) not null, `total_jobs` int not null, `pending_jobs` int not null, `failed_jobs` int not null, `failed_job_ids` longtext not null, `options` mediumtext null, `cancelled_at` int null, `created_at` int not null, `finished_at` int null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
create table `failed_jobs` (`id` bigint unsigned not null auto_increment primary key, `uuid` varchar(255) not null, `connection` varchar(255) not null, `queue` varchar(255) not null, `payload` longtext not null, `exception` longtext not null, `failed_at` timestamp not null default CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `failed_jobs` add index `failed_jobs_connection_queue_failed_at_index`(`connection`, `queue`, `failed_at`);
alter table `failed_jobs` add unique `failed_jobs_uuid_unique`(`uuid`);

-- 2026_11_24_000001_create_school_classes_table.php
create table `school_classes` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(80) not null, `grade` varchar(40) null, `color` varchar(20) not null default 'blue', `teacher_id` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `school_classes` add constraint `school_classes_teacher_id_foreign` foreign key (`teacher_id`) references `players` (`id`) on delete set null;
alter table `players` add `school_class_id` bigint unsigned null after `avatar`;
alter table `players` add constraint `players_school_class_id_foreign` foreign key (`school_class_id`) references `school_classes` (`id`) on delete set null;

-- 2026_11_24_000002_create_map_regions_table.php
create table `map_regions` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(120) not null, `hardware_pin` varchar(20) null, `type` enum('REGION', 'COUNTRY', 'STATE') not null, `parent_id` bigint unsigned null, `iso_code` varchar(3) null, `capital` varchar(120) null, `language` varchar(120) null, `short_description` varchar(255) null, `info` text null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `map_regions` add constraint `map_regions_parent_id_foreign` foreign key (`parent_id`) references `map_regions` (`id`) on delete restrict;
alter table `map_regions` add unique `map_regions_name_parent_id_unique`(`name`, `parent_id`);
alter table `map_regions` add index `map_regions_type_index`(`type`);
alter table `map_regions` add unique `map_regions_hardware_pin_unique`(`hardware_pin`);
alter table `map_regions` add unique `map_regions_iso_code_unique`(`iso_code`);

-- 2026_11_24_000003_create_categories_and_phases_tables.php
create table `categories` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(80) not null, `description` varchar(255) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `categories` add unique `categories_name_unique`(`name`);
create table `phases` (`id` bigint unsigned not null auto_increment primary key, `number` int unsigned not null, `title` varchar(120) not null, `description` varchar(255) null, `min_unlock_score` int unsigned not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `phases` add unique `phases_number_unique`(`number`);

-- 2026_11_24_000004_create_questions_table.php
create table `questions` (`id` bigint unsigned not null auto_increment primary key, `prompt` varchar(255) not null, `difficulty` enum('EASY', 'MEDIUM', 'HARD') not null, `answer_region_id` bigint unsigned not null, `phase_id` bigint unsigned not null, `category_id` bigint unsigned not null, `audio_path` varchar(255) null, `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `questions` add constraint `questions_answer_region_id_foreign` foreign key (`answer_region_id`) references `map_regions` (`id`) on delete restrict;
alter table `questions` add constraint `questions_phase_id_foreign` foreign key (`phase_id`) references `phases` (`id`) on delete restrict;
alter table `questions` add constraint `questions_category_id_foreign` foreign key (`category_id`) references `categories` (`id`) on delete restrict;

-- 2026_11_24_000005_create_game_modes_table.php
create table `game_modes` (`id` bigint unsigned not null auto_increment primary key, `code` enum('EXPLORATION', 'TRAINING', 'QUIZ', 'CHALLENGE', 'CLASSROOM') not null, `name` varchar(80) not null, `description` varchar(255) null, `is_timed` tinyint(1) not null default '0', `time_limit_seconds` int unsigned null, `max_attempts` int unsigned null, `questions_per_match` int unsigned null, `is_scored` tinyint(1) not null default '1', `speed_bonus` tinyint(1) not null default '0', `streak_bonus` tinyint(1) not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `game_modes` add unique `game_modes_code_unique`(`code`);

-- 2026_11_24_000006_create_activities_table.php
create table `activities` (`id` bigint unsigned not null auto_increment primary key, `title` varchar(120) not null, `description` varchar(255) null, `phase_id` bigint unsigned not null, `mode_id` bigint unsigned not null, `focus_region_id` bigint unsigned null, `school_class_id` bigint unsigned null, `created_by` bigint unsigned null, `estimated_minutes` int unsigned not null default '5', `reward_points` int unsigned not null default '100', `icon` varchar(30) not null default 'map', `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `activities` add constraint `activities_phase_id_foreign` foreign key (`phase_id`) references `phases` (`id`) on delete restrict;
alter table `activities` add constraint `activities_mode_id_foreign` foreign key (`mode_id`) references `game_modes` (`id`) on delete restrict;
alter table `activities` add constraint `activities_focus_region_id_foreign` foreign key (`focus_region_id`) references `map_regions` (`id`) on delete set null;
alter table `activities` add constraint `activities_school_class_id_foreign` foreign key (`school_class_id`) references `school_classes` (`id`) on delete cascade;
alter table `activities` add constraint `activities_created_by_foreign` foreign key (`created_by`) references `players` (`id`) on delete set null;
create table `activity_question` (`activity_id` bigint unsigned not null, `question_id` bigint unsigned not null, primary key (`activity_id`, `question_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `activity_question` add constraint `activity_question_activity_id_foreign` foreign key (`activity_id`) references `activities` (`id`) on delete cascade;
alter table `activity_question` add constraint `activity_question_question_id_foreign` foreign key (`question_id`) references `questions` (`id`) on delete cascade;

-- 2026_11_24_000007_create_matches_tables.php
create table `matches` (`id` bigint unsigned not null auto_increment primary key, `player_id` bigint unsigned null, `guest_id` char(36) null, `mode_id` bigint unsigned not null, `phase_id` bigint unsigned not null, `activity_id` bigint unsigned null, `status` enum('IN_PROGRESS', 'COMPLETED', 'ABANDONED') not null default 'IN_PROGRESS', `total_score` int not null default '0', `correct_count` int unsigned not null default '0', `wrong_count` int unsigned not null default '0', `streak` int unsigned not null default '0', `best_streak` int unsigned not null default '0', `answered_questions` int unsigned not null default '0', `active_question_id` bigint unsigned null, `question_started_at` timestamp(3) null, `attempts_on_question` int unsigned not null default '0', `started_at` timestamp(3) not null default CURRENT_TIMESTAMP(3), `ended_at` timestamp(3) null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `matches` add constraint `matches_player_id_foreign` foreign key (`player_id`) references `players` (`id`) on delete cascade;
alter table `matches` add constraint `matches_mode_id_foreign` foreign key (`mode_id`) references `game_modes` (`id`) on delete restrict;
alter table `matches` add constraint `matches_phase_id_foreign` foreign key (`phase_id`) references `phases` (`id`) on delete restrict;
alter table `matches` add constraint `matches_activity_id_foreign` foreign key (`activity_id`) references `activities` (`id`) on delete set null;
alter table `matches` add constraint `matches_active_question_id_foreign` foreign key (`active_question_id`) references `questions` (`id`) on delete set null;
alter table `matches` add index `matches_guest_id_index`(`guest_id`);
alter table `matches` add index `matches_status_index`(`status`);
create table `match_answers` (`id` bigint unsigned not null auto_increment primary key, `match_id` bigint unsigned not null, `question_id` bigint unsigned not null, `touched_region_id` bigint unsigned null, `result` enum('CORRECT', 'WRONG', 'TIMEOUT', 'SKIPPED') not null, `attempt_number` int unsigned not null, `response_time_ms` int unsigned null, `points_earned` int not null default '0', `source` enum('MAP', 'SCREEN') not null default 'SCREEN', `answered_at` timestamp(3) not null default CURRENT_TIMESTAMP(3)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `match_answers` add constraint `match_answers_match_id_foreign` foreign key (`match_id`) references `matches` (`id`) on delete cascade;
alter table `match_answers` add constraint `match_answers_question_id_foreign` foreign key (`question_id`) references `questions` (`id`) on delete restrict;
alter table `match_answers` add constraint `match_answers_touched_region_id_foreign` foreign key (`touched_region_id`) references `map_regions` (`id`) on delete set null;

-- 2026_11_24_000008_create_achievements_tables.php
create table `achievements` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(40) not null, `name` varchar(80) not null, `description` varchar(255) not null, `icon` varchar(30) not null, `color` varchar(20) not null, `rule` varchar(40) not null, `target` int unsigned not null, `sort_order` int unsigned not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `achievements` add unique `achievements_code_unique`(`code`);
create table `player_achievements` (`player_id` bigint unsigned not null, `achievement_id` bigint unsigned not null, `unlocked_at` timestamp not null default CURRENT_TIMESTAMP, primary key (`player_id`, `achievement_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `player_achievements` add constraint `player_achievements_player_id_foreign` foreign key (`player_id`) references `players` (`id`) on delete cascade;
alter table `player_achievements` add constraint `player_achievements_achievement_id_foreign` foreign key (`achievement_id`) references `achievements` (`id`) on delete cascade;
create table `explored_regions` (`player_id` bigint unsigned not null, `region_id` bigint unsigned not null, `first_explored_at` timestamp not null default CURRENT_TIMESTAMP, primary key (`player_id`, `region_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `explored_regions` add constraint `explored_regions_player_id_foreign` foreign key (`player_id`) references `players` (`id`) on delete cascade;
alter table `explored_regions` add constraint `explored_regions_region_id_foreign` foreign key (`region_id`) references `map_regions` (`id`) on delete cascade;

-- 2026_11_24_000009_create_player_preferences_table.php
create table `player_preferences` (`player_id` bigint unsigned not null, `large_font` tinyint(1) not null default '0', `high_contrast` tinyint(1) not null default '0', `audio_instructions` tinyint(1) not null default '1', `reduced_motion` tinyint(1) not null default '0', `created_at` timestamp null, `updated_at` timestamp null, primary key (`player_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `player_preferences` add constraint `player_preferences_player_id_foreign` foreign key (`player_id`) references `players` (`id`) on delete cascade;

-- 2026_11_24_000010_create_hardware_tables.php
create table `devices` (`device_id` varchar(64) not null, `name` varchar(80) null, `firmware_version` varchar(40) null, `sensors_ok` tinyint(1) not null default '1', `battery_level` tinyint unsigned null, `last_seen_at` timestamp(3) null, `bound_match_id` bigint unsigned null, `announced_online` tinyint(1) not null default '0', `created_at` timestamp null, `updated_at` timestamp null, primary key (`device_id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `devices` add constraint `devices_bound_match_id_foreign` foreign key (`bound_match_id`) references `matches` (`id`) on delete set null;
create table `game_events` (`id` bigint unsigned not null auto_increment primary key, `type` varchar(40) not null, `device_id` varchar(64) null, `match_id` bigint unsigned null, `payload` json null, `created_at` timestamp(3) not null default CURRENT_TIMESTAMP(3)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `game_events` add index `game_events_device_id_index`(`device_id`);
alter table `game_events` add index `game_events_match_id_index`(`match_id`);

-- 2026_11_24_000011_create_access_tokens_table.php
create table `access_tokens` (`id` bigint unsigned not null auto_increment primary key, `token_hash` varchar(64) not null, `player_id` bigint unsigned null, `guest_id` char(36) null, `last_used_at` timestamp null, `expires_at` timestamp null, `created_at` timestamp not null default CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `access_tokens` add constraint `access_tokens_player_id_foreign` foreign key (`player_id`) references `players` (`id`) on delete cascade;
alter table `access_tokens` add unique `access_tokens_token_hash_unique`(`token_hash`);

SET FOREIGN_KEY_CHECKS = 1;
