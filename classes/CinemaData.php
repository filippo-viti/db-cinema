<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/MovieList.php';
require_once __DIR__ . '/PersonList.php';
require_once __DIR__ . '/../vendor/autoload.php';

class CinemaData
{
    private $connection;
    private $movieList;
    private $peopleList;
    private $movieRepository;
    private $genreRepository;
    private $peopleRepository;
    private $MAX_HA_VINTO = 100;

    public function __construct()
    {
        $this->connection = null;
        $this->movieList = new MovieList();
        $this->peopleList = new PersonList();

        $key = file_get_contents("api_key.txt");
        if (!$key) {
            $message = "No API key provided in api_key.txt";
            Log::writeError($message);
            throw new Exception($message);
        }
        $token = new \Tmdb\ApiToken($key);
        $client = new \Tmdb\Client($token);
        $this->movieRepository = new \Tmdb\Repository\MovieRepository($client);
        $this->genreRepository = new \Tmdb\Repository\GenreRepository($client);
        $this->peopleRepository = new \Tmdb\Repository\PeopleRepository(
            $client
        );
    }

    public function getMovieList()
    {
        return $this->movieList;
    }

    public function getRepository()
    {
        return $this->movieRepository;
    }

    public function getConnection()
    {
        if ($this->connection == null) {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASSWORD,
                DB_NAME
            );
        }
        return $this->connection;
    }

    public function closeConnection()
    {
        $this->connection->close();
    }

    public function emptyTable($tableName)
    {
        $connection = $this->getConnection();
        $connection->begin_transaction();
        $connection->query("SET FOREIGN_KEY_CHECKS = 0;");
        if ($connection->query("TRUNCATE TABLE $tableName;") == false) {
            $connection->rollback();
            throw new Exception("truncate failed: " . $connection->error);
        }
        // $connection->query("SET FOREIGN_KEY_CHECKS = 1;");
        if (
            $connection->query("ALTER TABLE $tableName AUTO_INCREMENT = 1") ==
            false
        ) {
            $connection->rollback();
            throw new Exception(
                "autoincrement reset failed: " . $connection->error
            );
        }
        $connection->commit();
    }

    public function getTables()
    {
        $rv = [];
        $connection = $this->getConnection();
        $query = "show tables;";
        if ($result = $connection->query($query)) {
            while ($row = $result->fetch_row()) {
                $rv[] = $row[0];
            }
            $result->free();
        }
        return $rv;
    }

    public function insertDataFilm()
    {
        $connection = $this->getConnection();
        $connection->begin_transaction();
        $commandFilm = $connection->prepare(
            "INSERT INTO `Film`(`id_film`, `titolo`, `anno`, `regista`, `nazionalita`, `produzione`, `distribuzione`, `durata`, `colore`, `trama`, `valutazione`, `id_genere`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $commandFilm->bind_param(
            "isissssissdi",
            $paramIDFilm,
            $paramTitolo,
            $paramAnno,
            $paramRegista,
            $paramNazionalita,
            $paramProduzione,
            $paramDistribuzione,
            $paramDurata,
            $paramColore,
            $paramTrama,
            $paramValutazione,
            $paramIDGenere
        );
        // take data from api
        foreach ($this->movieList->getList() as $movieIDTitle) {
            $paramIDFilm = $movieIDTitle["id"];
            $paramTitolo = $movieIDTitle["original_title"];
            $movie = $this->movieRepository->load($paramIDFilm);
            $paramAnno = $this->getYear($movie);
            $paramRegista = $this->getDirector($movie);
            $paramNazionalita = $this->getCountry($movie);
            $paramProduzione = $this->getProduction($movie);
            $paramDistribuzione = $this->generateDistribution();
            $paramDurata = $movie->getRuntime();
            $paramColore = $this->generateColor();
            $paramTrama = $movie->getOverview();
            $paramValutazione = $movie->getVoteAverage();
            $paramIDGenere = $this->getGenre($movie);
            $commandFilm->execute();
            if ($commandFilm->affected_rows <= 0) {
                throw new Exception(
                    "!!!!!->Insert error: " . $commandFilm->error
                );
            }
            $this->insertDataRecitaIn($movie, $paramIDFilm);
            $this->insertDataColonneSonore($movie, $paramIDFilm);
        }
        $connection->commit();
        $commandFilm->close();
    }

    public function insertDataRecitaIn($movie, $paramIDFilm)
    {
        $connection = $this->getConnection();
        $connection->begin_transaction();
        $commandRecitaIn = $connection->prepare(
            "INSERT INTO `Recita_In`(`id_attore`, `id_film`, `personaggio`, `valutazione`) VALUES (?,?,?,?)"
        );
        $commandRecitaIn->bind_param(
            "iisd",
            $paramIDAttore,
            $paramIDFilm,
            $paramPersonaggio,
            $paramValutazione
        );

        $creditsCollection = $movie->getCredits();
        $cast = $creditsCollection->getCast()->getCast();

        foreach ($cast as $castMember) {
            $paramIDAttore = $castMember->getId();
            $paramPersonaggio = $castMember->getCharacter();
            $paramValutazione = $this->generateRating();
            try {
                $commandRecitaIn->execute();
                if ($commandRecitaIn->affected_rows <= 0) {
                    throw new Exception(
                        "!!!!!->Insert error: " . $commandRecitaIn->error
                    );
                }
            } catch (Exception $e) {
            }
        }
        $connection->commit();
        $commandRecitaIn->close();
    }

    public function insertDataGeneri()
    {
        $connection = $this->getConnection();
        $connection->begin_transaction();
        $commandGeneri = $connection->prepare(
            "INSERT INTO `Genere`(`id_genere`, `descrizione`) VALUES (?,?)"
        );
        $commandGeneri->bind_param("is", $paramIDGenere, $paramDescrizione);

        $genres = $this->genreRepository->loadCollection();
        foreach ($genres as $genre) {
            $paramIDGenere = $genre->getId();
            $paramDescrizione = $genre->getName();
            try {
                $commandGeneri->execute();
                if ($commandGeneri->affected_rows <= 0) {
                    throw new Exception(
                        "!!!!!->Insert error: " . $commandGeneri->error
                    );
                }
            } catch (Exception $e) {
            }
        }
        $connection->commit();
        $commandGeneri->close();
    }

    public function insertDataAttori()
    {
        $connection = $this->getConnection();
        $connection->begin_transaction();
        $commandAttori = $connection->prepare(
            "INSERT INTO `Attori`(`id_attore`, `nominativo`, `nazionalita`, `data_nascita`, `sesso`, `note`) VALUES (?,?,?,?,?,?)"
        );
        $commandAttori->bind_param(
            "isssss",
            $paramIDAttore,
            $paramNominativo,
            $paramNazionalita,
            $paramDataNascita,
            $paramSesso,
            $paramNote
        );
        foreach ($this->peopleList->getList() as $personIDName) {
            $paramIDAttore = $personIDName['id'];
            $person = $this->peopleRepository->load($paramIDAttore);
            if (!$this->isActor($person)) {
                continue;
            }
            $paramNominativo = $personIDName['name'];
            $paramNazionalita = $person->getPlaceOfBirth(); //inaccurate but it is the only information available
            $paramDataNascita = $person->getBirthDay()->format("Y-m-d");
            $paramSesso = $this->getGender($person);
            $paramNote = $person->getBiography();
            try {
                $commandAttori->execute();
                if ($commandAttori->affected_rows <= 0) {
                    throw new Exception(
                        "!!!!!->Insert error: " . $commandAttori->error
                    );
                }
            } catch (Exception $e) {
            }
        }
        $connection->commit();
        $commandAttori->close();
    }

    public function insertDataHaVinto()
    {
        $connection = $this->getConnection();
        $result = $connection->query("SELECT `id_film`, `anno` FROM `Film`");
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $resultArray[] = $row;
        }
        $IDCount = count($resultArray);
        $connection->begin_transaction();
        $commandHaVinto = $connection->prepare(
            "INSERT INTO `Ha_Vinto`(`id_premio`, `id_film`, `anno`) VALUES(?,?,?)"
        );
        $commandHaVinto->bind_param(
            "iis",
            $paramIDPremio,
            $paramIDFilm,
            $paramAnno
        );
        for ($i = 0; $i < $this->MAX_HA_VINTO; $i++) {
            $paramIDPremio = rand(1, 200);
            $randomIndex = rand(0, $IDCount);
            $paramIDFilm = $resultArray[$randomIndex][0];
            $minYear = $resultArray[$randomIndex][1];
            $paramAnno = rand($minYear, $minYear + 3);
            try {
                $commandHaVinto->execute();
                if ($commandHaVinto->affected_rows <= 0) {
                    throw new Exception(
                        "!!!!!->Insert error: " . $commandHaVinto->error
                    );
                }
            } catch (Exception $e) {
            }
        }
        $connection->commit();
        $commandHaVinto->close();
    }

    public function insertDataPremi()
    {
        $connection = $this->getConnection();
        $result = $connection->query(
            "SELECT DISTINCT `id_premio` FROM `Ha_Vinto`"
        );
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $resultArray[] = $row;
        }
        $IDCount = count($resultArray);
        $connection->begin_transaction();
        $commandPremi = $connection->prepare(
            "INSERT INTO `Premi`(`id_premio`, `descrizione`, `manifestazione`) VALUES(?,?,?)"
        );
        $commandPremi->bind_param(
            "iss",
            $paramIDPremio,
            $paramDescrizione,
            $paramManifestazione
        );
        foreach ($resultArray as $premio) {
            $paramIDPremio = $premio[0];
            $paramDescrizione = "Descrizione premio $paramIDPremio";
            $paramManifestazione = $this->generateEvent();
            $commandPremi->execute();
            if ($commandPremi->affected_rows <= 0) {
                throw new Exception(
                    "!!!!!->Insert error: " . $commandPremi->error
                );
            }
        }
        $connection->commit();
        $commandPremi->close();
    }

    public function insertDataColonneSonore($movie, $paramIDFilm)
    {
        $connection = $this->getConnection();
        $connection->begin_transaction();
        $commandColonneSonore = $connection->prepare(
            "INSERT INTO `Colonne_Sonore`(`id_musicista`, `id_film`, `brano`, `valutazione`) VALUES (?,?,?,?)"
        );
        $commandColonneSonore->bind_param(
            "iisd",
            $paramIDMusicista,
            $paramIDFilm,
            $paramBrano,
            $paramValutazione
        );
        $paramIDMusicista = $this->getMusicComposerID($movie);
        if ($paramIDMusicista == null) {
            $commandColonneSonore->close();
            return;
        }
        $paramBrano = $this->generateTrack();
        $paramValutazione = $this->generateRating();
        $commandColonneSonore->execute();
        if ($commandColonneSonore->affected_rows <= 0) {
            throw new Exception(
                "!!!!!->Insert error: " . $commandColonneSonore->error
            );
        }
        $connection->commit();
        $commandColonneSonore->close();
    }

    public function insertDataMusicisti()
    {
        $connection = $this->getConnection();
        $result = $connection->query(
            "SELECT DISTINCT `id_musicista` FROM `Colonne_Sonore`"
        );
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $musicianIDS[] = $row;
        }
        $connection->begin_transaction();
        $commandMusicisti = $connection->prepare(
            "INSERT INTO `Musicisti`(`id_musicista`, `nominativo`, `nazionalita`, `data_nascita`, `sesso`, `note`) VALUES (?,?,?,?,?,?)"
        );
        $commandMusicisti->bind_param(
            "isssss",
            $paramIDMusicista,
            $paramNominativo,
            $paramNazionalita,
            $paramDataNascita,
            $paramSesso,
            $paramNote
        );
        foreach ($musicianIDS as $musicianID) {
            $paramIDMusicista = $musicianID[0];
            $person = $this->peopleRepository->load($paramIDMusicista);
            $paramNominativo = $person->getName();
            $paramNazionalita = $person->getPlaceOfBirth(); //inaccurate but it is the only information available
            $paramDataNascita = $person->getBirthDay()->format("Y-m-d");
            $paramSesso = $this->getGender($person);
            $paramNote = $person->getBiography();
            $commandMusicisti->execute();
            if ($commandMusicisti->affected_rows <= 0) {
                throw new Exception(
                    "!!!!!->Insert error: " . $commandMusicisti->error
                );
            }
        }
        $connection->commit();
        $commandMusicisti->close();
    }

    public function getDirector($movie)
    {
        $crew = $movie
            ->getCredits()
            ->getCrew()
            ->getCrew();
        foreach ($crew as $member) {
            if ($member->getJob() == "Director") {
                return $member->getName();
            }
        }
        return null;
    }

    public function getYear($movie)
    {
        $date = $movie->getReleaseDate();
        $year = (int) $date->format("Y");
        return $year;
    }

    public function getGenre($movie)
    {
        $genres = $movie->getGenres()->getGenres();
        $genre = reset($genres);
        if (!$genre) {
            return null;
        }
        $id = $genre->getId();
        return $id;
    }

    public function getCountry($movie)
    {
        $productionCountries = $movie->getProductionCountries();
        $countryArray = $productionCountries->toArray();
        $country = reset($countryArray);
        if (!$country) {
            return null;
        }
        $countryName = $country->getName();
        return $countryName;
    }

    public function getProduction($movie)
    {
        $productionCompanies = $movie->getProductionCompanies();
        $companyArray = $productionCompanies->toArray();
        $company = reset($companyArray);
        if (!$company) {
            return null;
        }
        $companyName = $company->getName();
        return $companyName;
    }

    public function generateDistribution()
    {
        $id = rand(1, 100);
        return "Distribution$id";
    }

    public function generateColor()
    {
        $id = rand(1, 2);
        if ($id == 1) {
            return "A_COLORI";
        } else {
            return "BIANCO_E_NERO";
        }
    }

    public function generateRating()
    {
        $rating = rand(1, 100);
        $rating /= 10;
        return $rating;
    }

    private function isActor($person)
    {
        $credits = $person->getMovieCredits();
        $cast = $credits->getCast()->getCast();
        if ($cast) {
            return true;
        } else {
            return false;
        }
    }

    private function getGender($person)
    {
        if ($person->isMale()) {
            return 'M';
        } elseif ($person->isFemale()) {
            return 'F';
        } else {
            return 'NS';
        }
    }

    private function generateEvent()
    {
        $id = rand(1, 30);
        return "Manifestazione$id";
    }

    private function getMusicComposerID($movie)
    {
        $credits = $movie->getCredits();
        $crew = $credits->getCrew()->getCrew();
        foreach ($crew as $member) {
            if ($member->getJob() == "Original Music Composer") {
                return $member->getId();
            }
        }
        return null;
    }

    private function generateTrack()
    {
        $id = rand(1, 500);
        return "Brano$id";
    }
}
