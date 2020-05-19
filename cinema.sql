-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Mag 18, 2020 alle 10:43
-- Versione del server: 10.4.11-MariaDB
-- Versione PHP: 7.4.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cinema`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `Attori`
--

CREATE TABLE `Attori` (
  `id_attore` int(10) UNSIGNED NOT NULL,
  `nominativo` varchar(255) NOT NULL,
  `nazionalita` varchar(255) NOT NULL,
  `data_nascita` date NOT NULL,
  `sesso` enum('M','F','NS') NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Colonne_Sonore`
--

CREATE TABLE `Colonne_Sonore` (
  `id_musicista` int(10) UNSIGNED NOT NULL,
  `id_film` int(10) UNSIGNED NOT NULL,
  `brano` varchar(255) NOT NULL,
  `valutazione` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Film`
--

CREATE TABLE `Film` (
  `id_film` int(10) UNSIGNED NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `anno` year(4) NOT NULL,
  `regista` varchar(255) NOT NULL,
  `nazionalita` varchar(255) NOT NULL,
  `produzione` varchar(255) NOT NULL,
  `distribuzione` varchar(255) NOT NULL,
  `durata` int(10) UNSIGNED NOT NULL,
  `colore` enum('A_COLORI','BIANCO_E_NERO') NOT NULL,
  `trama` text NOT NULL,
  `valutazione` float UNSIGNED NOT NULL,
  `id_genere` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Genere`
--

CREATE TABLE `Genere` (
  `id_genere` int(10) UNSIGNED NOT NULL,
  `descrizione` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Ha_Vinto`
--

CREATE TABLE `Ha_Vinto` (
  `id_premio` int(10) UNSIGNED NOT NULL,
  `id_film` int(10) UNSIGNED NOT NULL,
  `anno` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Musicisti`
--

CREATE TABLE `Musicisti` (
  `id_musicista` int(10) UNSIGNED NOT NULL,
  `nominativo` varchar(255) NOT NULL,
  `nazionalita` varchar(255) NOT NULL,
  `data_nascita` date NOT NULL,
  `sesso` enum('M','F','NS') NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Premi`
--

CREATE TABLE `Premi` (
  `id_premio` int(10) UNSIGNED NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `manifestazione` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `Recita_In`
--

CREATE TABLE `Recita_In` (
  `id_attore` int(10) UNSIGNED NOT NULL,
  `id_film` int(10) UNSIGNED NOT NULL,
  `personaggio` varchar(255) NOT NULL,
  `valutazione` float UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `Attori`
--
ALTER TABLE `Attori`
  ADD PRIMARY KEY (`id_attore`);

--
-- Indici per le tabelle `Colonne_Sonore`
--
ALTER TABLE `Colonne_Sonore`
  ADD PRIMARY KEY (`id_musicista`,`id_film`),
  ADD KEY `fkColonneSonoreFilm` (`id_film`);

--
-- Indici per le tabelle `Film`
--
ALTER TABLE `Film`
  ADD PRIMARY KEY (`id_film`),
  ADD KEY `fkFilmGenere` (`id_genere`);

--
-- Indici per le tabelle `Genere`
--
ALTER TABLE `Genere`
  ADD PRIMARY KEY (`id_genere`);

--
-- Indici per le tabelle `Ha_Vinto`
--
ALTER TABLE `Ha_Vinto`
  ADD PRIMARY KEY (`id_premio`,`id_film`),
  ADD KEY `fkHaVintoFilm` (`id_film`);

--
-- Indici per le tabelle `Musicisti`
--
ALTER TABLE `Musicisti`
  ADD PRIMARY KEY (`id_musicista`);

--
-- Indici per le tabelle `Premi`
--
ALTER TABLE `Premi`
  ADD PRIMARY KEY (`id_premio`);

--
-- Indici per le tabelle `Recita_In`
--
ALTER TABLE `Recita_In`
  ADD PRIMARY KEY (`id_attore`,`id_film`),
  ADD KEY `fkRecitaInFilm` (`id_film`);

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `Colonne_Sonore`
--
ALTER TABLE `Colonne_Sonore`
  ADD CONSTRAINT `fkColonneSonoreFilm` FOREIGN KEY (`id_film`) REFERENCES `Film` (`id_film`),
  ADD CONSTRAINT `fkColonneSonoreMusicist` FOREIGN KEY (`id_musicista`) REFERENCES `Musicisti` (`id_musicista`);

--
-- Limiti per la tabella `Film`
--
ALTER TABLE `Film`
  ADD CONSTRAINT `fkFilmGenere` FOREIGN KEY (`id_genere`) REFERENCES `Genere` (`id_genere`);

--
-- Limiti per la tabella `Ha_Vinto`
--
ALTER TABLE `Ha_Vinto`
  ADD CONSTRAINT `fkHaVintoFilm` FOREIGN KEY (`id_film`) REFERENCES `Film` (`id_film`),
  ADD CONSTRAINT `fkHaVintoPremi` FOREIGN KEY (`id_premio`) REFERENCES `Premi` (`id_premio`);

--
-- Limiti per la tabella `Recita_In`
--
ALTER TABLE `Recita_In`
  ADD CONSTRAINT `fkRecitaInAttori` FOREIGN KEY (`id_attore`) REFERENCES `Attori` (`id_attore`),
  ADD CONSTRAINT `fkRecitaInFilm` FOREIGN KEY (`id_film`) REFERENCES `Film` (`id_film`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
