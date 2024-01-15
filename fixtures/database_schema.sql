CREATE TABLE IF NOT EXISTS `archived_playlist`
(
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `year` INT UNSIGNED NOT NULL,
    `week` INT UNSIGNED NOT NULL,
    `archived_playlist_id` VARCHAR(255) NOT NULL,
    `archived_playlist_name_prefix` VARCHAR(255) NOT NULL,
    `archived_playlist_name_suffix` VARCHAR(255) NOT NULL,
    `archived_playlist_sortorder` VARCHAR(255),
    `archived_playlist_tracks` MEDIUMBLOB NOT NULL,
    `orig_playlist_id` VARCHAR(255) NOT NULL,
    `orig_playlist_owner` VARCHAR(255),
    `orig_playlist_snapshot_id` VARCHAR(255) NOT NULL,
    `orig_playlist_cover` MEDIUMBLOB NOT NULL,
    `datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4;