<?php

namespace IbookParser;

class AuthorHandler
{
    use DBTrait;

    /**
     * Fetches author from DB
     * @return mixed
     */
    public function fetchAuthor(string $value): array
    {
        $links = $this->database->prepare('SELECT * FROM authors WHERE name= ?');
        $links->execute([$value]);

        return $links->fetchAll();
    }

    /**
     * Saves author to the DB
     * @param string $author
     * @return int
     */
    public function saveAuthor(string $author): int
    {
        $sql = $this->database->prepare('INSERT into authors (name) VALUES (?) ON CONFLICT (name) DO UPDATE SET name=excluded.name RETURNING ID');
        $sql->execute([$author]);
        $result = $sql->fetchAll();
        return $result[0]['id'];
    }

    /**
     * @param string $isbn
     * @param string $id
     * @return void
     */
    public function linkAuthor(string $isbn, string $id): void
    {
        $sql = $this->database->prepare('INSERT into author_book (book_isbn, author_id) VALUES (?, ?) ON CONFLICT (author_id, book_isbn) DO NOTHING');
        $sql->execute([$isbn, $id]);
    }
}