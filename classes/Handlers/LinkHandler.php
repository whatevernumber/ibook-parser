<?php

namespace IbookParser\Handlers;

use IbookParser\db\DBTrait;

class LinkHandler
{
    use DBTrait;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Fetches all links from DB
     * @return array
     */
    public function fetchLinks(): array
    {
        $links = $this->database->prepare('SELECT * FROM parse_links');
        $links->execute();

        return $links->fetchAll();
    }

    /**
     * Fetches given link from DB
     * @param $link
     * @return array
     */
    public function getLink($link): array
    {
        $links = $this->database->prepare('SELECT * FROM parse_links WHERE link=?');
        $links->execute([$link]);

        return $links->fetchAll();
    }

    /**
     * Saves link to the DB
     * @param string $link
     * @param int $genre_id
     * @return void
     */
    public function saveLink(string $link, int $genre_id): void
    {
        $sql = $this->database->prepare('INSERT into parse_links (link, main_genre_id) VALUES (?, ?) ON CONFLICT (link) DO NOTHING');
        $sql->execute([$link, $genre_id]);
    }
}