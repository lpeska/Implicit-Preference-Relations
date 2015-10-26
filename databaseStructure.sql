-- phpMyAdmin SQL Dump
-- version 4.1.4
-- http://www.phpmyadmin.net
--
-- Počítač: 127.0.0.1
-- Vytvořeno: Pon 26. říj 2015, 10:57
-- Verze serveru: 5.6.15-log
-- Verze PHP: 5.5.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `slantour`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `category_similarity`
--

CREATE TABLE IF NOT EXISTS `category_similarity` (
  `cat_id1` text CHARACTER SET utf8 NOT NULL,
  `cat_id2` text CHARACTER SET utf8 NOT NULL,
  `similarity` double NOT NULL,
  PRIMARY KEY (`cat_id1`(100),`cat_id2`(100))
) ENGINE=MyISAM DEFAULT CHARSET=cp1250;

-- --------------------------------------------------------

--
-- Struktura tabulky `new_implicit_events`
--

CREATE TABLE IF NOT EXISTS `new_implicit_events` (
  `visitID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `objectID` int(11) NOT NULL,
  `sessionID` int(11) NOT NULL,
  `pageID` text NOT NULL,
  `pageType` text NOT NULL,
  `imagesCount` int(11) NOT NULL,
  `textSizeCount` int(11) NOT NULL,
  `linksCount` int(11) NOT NULL,
  `windowSizeX` int(11) NOT NULL,
  `windowSizeY` int(11) NOT NULL,
  `pageSizeX` int(11) NOT NULL,
  `pageSizeY` int(11) NOT NULL,
  `objectsListed` text NOT NULL,
  `startDatetime` datetime NOT NULL,
  `endDatetime` datetime NOT NULL,
  `timeOnPage` int(11) DEFAULT NULL,
  `mouseClicksCount` int(11) DEFAULT NULL,
  `pageViewsCount` int(11) DEFAULT NULL,
  `mouseMovingTime` int(11) DEFAULT NULL,
  `mouseMovingDistance` int(11) DEFAULT NULL,
  `scrollingCount` int(11) NOT NULL,
  `scrollingTime` int(11) NOT NULL,
  `scrollingDistance` int(11) DEFAULT NULL,
  `printPageCount` int(11) DEFAULT NULL,
  `selectCount` int(11) DEFAULT NULL,
  `selectedText` text NOT NULL,
  `searchedText` text NOT NULL,
  `copyCount` int(11) DEFAULT NULL,
  `copyText` text NOT NULL,
  `clickOnPurchaseCount` int(11) DEFAULT NULL,
  `purchaseCount` int(11) DEFAULT NULL,
  `forwardingToLinkCount` int(11) DEFAULT NULL,
  `forwardedToLink` text,
  `logFile` text
) ENGINE=MyISAM  DEFAULT CHARSET=cp1250;

-- --------------------------------------------------------

--
-- Struktura tabulky `objects_binary_attributes`
--

CREATE TABLE IF NOT EXISTS `objects_binary_attributes` (
  `oid` int(11) NOT NULL,
  `feature` text CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
  `value` double NOT NULL,
  PRIMARY KEY (`oid`,`feature`(50)),
  KEY `oid` (`oid`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1250;

-- --------------------------------------------------------

--
-- Struktura tabulky `objects_binary_attributes_tf`
--

CREATE TABLE IF NOT EXISTS `objects_binary_attributes_tf` (
  `oid` int(11) NOT NULL,
  `feature` text CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
  `value` double NOT NULL,
  PRIMARY KEY (`oid`,`feature`(50)),
  KEY `oid` (`oid`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1250;

-- --------------------------------------------------------

--
-- Struktura tabulky `objects_table`
--

CREATE TABLE IF NOT EXISTS `objects_table` (
  `id_record` int(11) NOT NULL AUTO_INCREMENT,
  `oid` int(11) NOT NULL,
  `category` text NOT NULL,
  `id_zajezd` int(11) NOT NULL,
  `od` date NOT NULL,
  `do` date NOT NULL,
  `nazev` text NOT NULL,
  `popisek` text NOT NULL,
  `id_typ` int(11) NOT NULL,
  `strava` int(11) NOT NULL,
  `doprava` int(11) NOT NULL,
  `ubytovani` int(11) NOT NULL,
  `ubytovani_kategorie` decimal(9,2) NOT NULL,
  `zeme` text NOT NULL COMMENT 'seznam zemi oddeleny ":"',
  `destinace` text NOT NULL COMMENT 'seznam destinaci oddeleny ":"',
  `prumerna_cena` int(11) NOT NULL,
  `prumerna_cena_noc` int(11) NOT NULL,
  `min_cena` int(11) NOT NULL,
  `min_cena_noc` int(11) NOT NULL,
  `sleva` int(11) NOT NULL COMMENT 'sleva v procentech (prepocet z prumerne castky',
  `delka` int(11) NOT NULL,
  `informace_list` text NOT NULL COMMENT 'seznam informaci oddeleny ":"',
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  PRIMARY KEY (`id_record`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30020 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `object_visibility`
--

CREATE TABLE IF NOT EXISTS `object_visibility` (
  `visitID` int(11) NOT NULL,
  `objectID` int(11) NOT NULL,
  `posX` int(11) NOT NULL,
  `posY` int(11) NOT NULL,
  `selected` int(11) NOT NULL,
  `visible` int(11) NOT NULL,
  `visible_time` double NOT NULL,
  `visible_percentage` double NOT NULL,
  PRIMARY KEY (`visitID`,`objectID`),
  KEY `visitID` (`visitID`),
  KEY `objectID` (`objectID`),
  KEY `selected` (`selected`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1250;

-- --------------------------------------------------------

--
-- Struktura tabulky `test_set`
--

CREATE TABLE IF NOT EXISTS `test_set` (
  `userID` int(11) NOT NULL,
  `objectID` int(11) NOT NULL COMMENT 'Pokud je oid=0, je to kategorie. Beru tak vsechny objekty viditelne uzivatelem jako souhrn vektoru kategorie',
  `visitID` int(11) NOT NULL,
  `is_recommendable` int(11) NOT NULL,
  PRIMARY KEY (`visitID`),
  KEY `userID` (`userID`),
  KEY `objectID` (`objectID`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1250;

-- --------------------------------------------------------

--
-- Struktura tabulky `train_set`
--

CREATE TABLE IF NOT EXISTS `train_set` (
  `userID` int(11) NOT NULL,
  `objectID` int(11) NOT NULL COMMENT 'Pokud je oid=0, je to kategorie. Beru tak vsechny objekty viditelne uzivatelem jako souhrn vektoru kategorie',
  `visitID` int(11) NOT NULL,
  `is_recommendable` int(11) NOT NULL,
  PRIMARY KEY (`visitID`),
  KEY `userID` (`userID`),
  KEY `objectID` (`objectID`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1250;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
