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
        $this->movieList = new MovieList;
        // TODO catch exceptions
        $key = file_get_contents("api_key.txt");
        $token  = new \Tmdb\ApiToken($key);
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
        $connection->query("SET FOREIGN_KEY_CHECKS = 1;");
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

        foreach ($this->movieList as $movieIDTitle) {
            $paramIDFilm = $movieIDTitle["id"];
            $paramTitolo = $movieIDTitle["original_title"];
            $movie = $this->repository->load($paramIDFilm);
            $paramAnno = $this->getYear($movie);
            $paramRegista = $this->getDirector($movie);
            // TODO nation
            // Missing production and distribution
            // Missing duration
            // missing color
            // Missing plot
            // Missing rating
            
            $commandFilm->execute();
            if ($commandFilm->affected_rows <= 0) {
                throw new Exception("!!!!!->Insert error: " . $commandFilm->error);
            }
        }
        $connection->commit();
        $commandFilm->close();
    }

    public function getDirector($movie)
    {
        $crew = $movie->getCredits()->getCrew()->getCrew();
        foreach ($crew as $member) {
            if ($member->getJob() == "Director") {
                return $member->getName();
            }
        }
        return null;
    }

    public function getYear($movie)
    {
        $releases = $movie->getReleases($movieID)->toArray();
        foreach ($releases as $release) {
            $dates[] = $release->getReleaseDate();
        }
        $year = (int)(min($dates)->format("Y"));
        return $year;
    }
}
