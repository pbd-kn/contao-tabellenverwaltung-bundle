-- Nur die 16 Schema-Ergänzungen des Tabellenverwaltung-Bundles.
-- Einmalig in der Datenbank der Contao-Website ausführen.
CREATE TABLE `tl_directory` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `tstamp` INT UNSIGNED DEFAULT 0 NOT NULL,
  `title` VARCHAR(255) DEFAULT '' NOT NULL,
  `columns` BLOB DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

CREATE TABLE `tl_directory_entry` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `pid` INT UNSIGNED DEFAULT 0 NOT NULL,
  `tstamp` INT UNSIGNED DEFAULT 0 NOT NULL,
  `sorting` INT UNSIGNED DEFAULT 0 NOT NULL,
  `legacyId` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT '' NOT NULL,
  `published` CHAR(1) DEFAULT '' NOT NULL,
  `data` BLOB DEFAULT NULL,
  INDEX `pid` (`pid`),
  UNIQUE INDEX `pid_legacyid` (`pid`, `legacyId`),
  PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

ALTER TABLE `tl_content` ADD `directoryId` INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE `tl_content` ADD `directoryDisplayColumns` TEXT NULL;
ALTER TABLE `tl_content` ADD `directorySearchColumns` TEXT NULL;
ALTER TABLE `tl_content` ADD `directoryOrder` TEXT NULL;
ALTER TABLE `tl_content` ADD `directoryDetailColumns` TEXT NULL;
ALTER TABLE `tl_content` ADD `directoryPresentation` VARCHAR(16) NOT NULL DEFAULT 'table';
ALTER TABLE `tl_content` ADD `directoryDetailPage` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tl_module` ADD `directoryId` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tl_module` ADD `directoryPresentation` VARCHAR(16) NOT NULL DEFAULT 'table';
ALTER TABLE `tl_module` ADD `directoryDetailPage` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tl_module` ADD `directoryDisplayColumns` TEXT NULL;
ALTER TABLE `tl_module` ADD `directorySearchColumns` TEXT NULL;
ALTER TABLE `tl_module` ADD `directoryOrder` TEXT NULL;
ALTER TABLE `tl_module` ADD `directoryDetailColumns` TEXT NULL;
