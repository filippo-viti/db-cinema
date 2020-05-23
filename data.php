<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/get_list.php';
require_once __DIR__ . '/vendor/autoload.php';

class CinemaData
{
    private $connection;
    private $movieList;
    private $repository;

    public function __construct()
    {
        $this->connection = null;
        $this->movieList = new MovieList();
        // TODO catch exceptions
        $key = file_get_contents("api_key.txt");
        $token = new \Tmdb\ApiToken($key);
        $client = new \Tmdb\Client($token);
        $this->repository = new \Tmdb\Repository\MovieRepository($client);
    }

    public function getMovieList()
    {
        return $this->movieList;
    }

    public function getRepository()
    {
        return $this->repository;
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
        echo "Getting data...\n";
        // take data from api
        foreach ($this->movieList->getList() as $movieIDTitle) {
            $paramIDFilm = $movieIDTitle["id"];
            $paramTitolo = $movieIDTitle["original_title"];
            echo "Movie: $paramTitolo\n";
            $movie = $this->repository->load($paramIDFilm);
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
        }
        $connection->commit();
        $commandFilm->close();
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
}
