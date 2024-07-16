<?php

namespace IbookParser;

class GenreHandler
{
    use DBTrait;

    /**
     * Fetches genre from DB
     * @param string $value
     * @return array
     */
    public function fetchGenre(string $value): array
    {
        $links = $this->database->prepare('SELECT * FROM genres WHERE value=?');
        $links->execute([$value]);

        return $links->fetchAll();
    }

    /**
     * Saves genre to the DB
     * @param string $genre
     * @return int last saved id
     */
    public function saveGenre(string $genre): int
    {
        $sql = $this->database->prepare('INSERT into genres (value) VALUES (?)  ON CONFLICT (value) DO UPDATE SET value=excluded.value RETURNING ID');
        $sql->execute([$genre]);
        $result = $sql->fetchAll();
        return $result[0]['id'];
    }

    /**
     * @param string $isbn
     * @param string $id
     * @return void
     */
    public function linkGenre(string $isbn, string $id): void
    {
        $sql = $this->database->prepare('INSERT into book_genre (book_isbn, genre_id) VALUES (?, ?)  ON CONFLICT (book_isbn, genre_id) DO NOTHING');
        $sql->execute([$isbn, $id]);
    }
}