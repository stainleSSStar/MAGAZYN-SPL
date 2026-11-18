-- =====================================================================
--  STRUKTURA BAZY - Panel Magazynu
-- =====================================================================
--  Plik zawiera TYLKO strukturę tabel (bez danych).
--  Użycie na serwerze produkcyjnym:
--      mysql -u root -p -e "CREATE DATABASE magazyn CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
--      mysql -u root -p magazyn < sql/schema.sql
--
--  Baza jest PUSTA - repozytorium nie zawiera żadnych danych.
--  Tabele startują od ID = 1, więc możesz zacząć wypełniać je od zera.
--
--  Dane wprowadzasz na trzy sposoby:
--    1. przez panel (menu 'Tabele' -> dodawanie rekordów),
--    2. przez własny dump SQL:
--         mysql -u root -p --default-character-set=utf8mb4 magazyn < twoj-dump.sql
--    3. importem przez panel hostingu.
--
--  Panel NIE wymaga żadnych zmian w tej strukturze - odczytuje ją
--  automatycznie z INFORMATION_SCHEMA.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- Tabela: model
-- -----------------------------------------------------------
CREATE TABLE `model` (
  `IDmodel` int(11) NOT NULL AUTO_INCREMENT,
  `Nazwa` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`IDmodel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Tabela: pracownik
-- -----------------------------------------------------------
CREATE TABLE `pracownik` (
  `IDpracownik` int(11) NOT NULL,
  `Imie` varchar(25) DEFAULT NULL,
  `Nazwisko` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`IDpracownik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Tabela: asortyment
-- -----------------------------------------------------------
CREATE TABLE `asortyment` (
  `IDasortyment` int(11) NOT NULL,
  `Nazwa` varchar(50) DEFAULT NULL,
  `Symbol` varchar(25) DEFAULT NULL,
  `Ilosc` int(11) DEFAULT NULL,
  `IDmodel` int(11) DEFAULT NULL,
  PRIMARY KEY (`IDasortyment`),
  KEY `fk_model` (`IDmodel`),
  CONSTRAINT `fk_model` FOREIGN KEY (`IDmodel`) REFERENCES `model` (`IDmodel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Tabela: drukarki
-- -----------------------------------------------------------
CREATE TABLE `drukarki` (
  `IDdrukarki` int(11) NOT NULL,
  `Lokalizacja` varchar(50) DEFAULT NULL,
  `Adres_IP` varchar(16) DEFAULT NULL,
  `INFO` varchar(50) DEFAULT NULL,
  `IDmodel` int(11) DEFAULT NULL,
  PRIMARY KEY (`IDdrukarki`),
  KEY `fk_model_drukarki` (`IDmodel`),
  CONSTRAINT `fk_model_drukarki` FOREIGN KEY (`IDmodel`) REFERENCES `model` (`IDmodel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Tabela: wymiany
-- -----------------------------------------------------------
CREATE TABLE `wymiany` (
  `IDwymiany` int(11) NOT NULL AUTO_INCREMENT,
  `Data` date DEFAULT NULL,
  `Licznik` int(11) DEFAULT NULL,
  `IDmodel` int(11) NOT NULL,
  `IDdrukarki` int(11) DEFAULT NULL,
  `IDasortyment` int(11) DEFAULT NULL,
  `IDpracownik` int(11) DEFAULT NULL,
  `Info` varchar(999) DEFAULT NULL,
  PRIMARY KEY (`IDwymiany`),
  KEY `fk_drukarki` (`IDdrukarki`),
  KEY `fk_asortyment` (`IDasortyment`),
  KEY `fk_pracownik` (`IDpracownik`),
  CONSTRAINT `asortyment_fkey` FOREIGN KEY (`IDasortyment`) REFERENCES `asortyment` (`IDasortyment`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `drukarki_fkey` FOREIGN KEY (`IDdrukarki`) REFERENCES `drukarki` (`IDdrukarki`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `pracownik_fkey` FOREIGN KEY (`IDpracownik`) REFERENCES `pracownik` (`IDpracownik`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
