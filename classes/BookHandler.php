<?php

namespace IbookParser;

class BookHandler
{
    use DBTrait;

    /**
     * Searches book for the given isbn
     * @param string $isbn
     * @return array
     */
    public function fetchBook(string $isbn): array
    {
        $sql = $this->database->prepare('SELECT * from books WHERE isbn = ?');
        $sql->execute([$isbn]);

        return $sql->fetchAll();
    }

    /**
     * Saves new book or updates existing one
     * @param array $book
     * @return void
     */
    public function saveBook(array $book): void
    {
        $sql = $this->database->prepare('INSERT into books (isbn, has_x, title, description, published_year, pages) VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (isbn) DO UPDATE SET title=excluded.title, description=excluded.description, published_year=excluded.published_year, pages=excluded.pages
            ');
        $sql->execute([
            $book['isbn'],
            $book['has_x'],
            $book['title'],
            $book['annotation'],
            $book['year'],
            $book['pages'] ?? null
        ]);
    }
}