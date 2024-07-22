<?php

namespace IbookParser\Services;

use GuzzleHttp\Client;
use IbookParser\DB\DBTrait;
use IbookParser\Handlers\AuthorHandler;
use IbookParser\Handlers\BookHandler;
use IbookParser\Handlers\GenreHandler;
use IbookParser\Handlers\LinkHandler;
use IbookParser\Handlers\ParseHandler;

class LabirintService
{
    use DBTrait;

    protected const SITE_URL = 'https://www.labirint.ru';
    protected const STORE_ID = 1;

    protected ParseHandler $helper;

    public function __construct()
    {
        $this->helper = new ParseHandler(new Client());
        $this->connect();
    }

    /**
     * Gets all the categories and parses each
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $categories = $this->getCategories();

        foreach ($categories as $category) {
            $links = $this->parseCategory($category);

            foreach ($links as $link) {
                $this->collectBook($link, $category['genre_id']);
            }
        }
    }

    /**
     * Collects book and saves its data to the DB
     * @param $link
     * @param $main_genre
     * @return void
     */
    public function collectBook($link, $main_genre)
    {

        $book = $this->parseBook($link);

        $bookHandler = new BookHandler();
        $bookHandler->saveBook($book);

        $genreHandler = new GenreHandler();
        $genreHandler->linkGenre($book['isbn'], $main_genre);

        if (isset($book['authors'])) {
            $authorHelper = new AuthorHandler();

            foreach ($book['authors'] as $author) {
                if ($exists = $authorHelper->fetchAuthor($author)) {
                    $authorId = $exists[0]['id'];
                } else {
                    $authorId = $authorHelper->saveAuthor($author);
                }

                $authorHelper->linkAuthor($book['isbn'], $authorId);
            }
        }

        foreach ($book['genres'] as $genre) {
            if ($exists = $genreHandler->fetchGenre($genre)) {
                $genreId = $exists[0]['id'];
            } else {
                $genreId = $genreHandler->saveGenre($genre);
            }
            $genreHandler->linkGenre($book['isbn'], $genreId);
        }

        if (isset($book['image-link'])) {
            $this->saveCoverLink($book);
        }

        $this->savePrices($book);
    }

    /**
     * Gets all categories for Labirint shop
     * @return array
     */
    protected function getCategories(): array
    {
        $categories = $this->database->prepare('SELECT * FROM shop_categories WHERE shop_id=?');
        $categories->execute([self::STORE_ID]);

        return $categories->fetchAll();
    }

    /**
     * Parses each category page and collects links for books
     * @param array $category
     * @return array
     */
    protected function parseCategory(array $category): array
    {
        $links = $this->parseLinks($category['category']);

        foreach ($links as $link) {
            $linkHandler = new LinkHandler();
            $linkHandler->saveLink($link, $category['genre_id']);
        }

        return $links;
    }

    /**
     * Collects book links from the given category and returns an array
     * @param string $category
     * @return array
     */
    protected function parseLinks(string $category): array
    {
        $url = self::SITE_URL . '/genres/' . $category . '/';
        $dom = $this->helper->parse($url);

        $pageExpression = "(//div[@class='pagination-number']/div[@class='pagination-number__right']//a[@class='pagination-number__text'])[1]";
        $lastPage = $this->helper->find($dom, $pageExpression);
        $lastPage = $lastPage->item(0)->textContent;

        $linkExpression = "//div[@class='inner-catalog']//a[@class='product-title-link']";
        $currentPage = '?page=';

        $extractedLinks = [];

        for ($i = 1; $i <= (int)$lastPage; $i++) {
            $dom = $this->helper->parse($url . $currentPage . $i);
            $links = $this->helper->find($dom, $linkExpression);

            foreach ($links as $link) {
                $extractedLinks[] = 'https://www.labirint.ru' . $link->getAttribute('href');
            }
        }

        return $extractedLinks;
    }

    /**
     * Parses page, collects book's info and returns a book array
     * @param string $url
     * @return array
     */
    protected function parseBook(string $url): array
    {
        $book = [];
        $dom = $this->helper->parse($url);

        $data = $this->helper->find($dom, "//div[@id='product-info']");
        $book['title'] = $data->item(0)->getAttribute('data-name');
        $book['genres'][] = $data->item(0)->getAttribute('data-maingenre-name');
        $book['genres'][] = $data->item(0)->getAttribute('data-first-genre-name');
        $book['price'] = $data->item(0)->getAttribute('data-price');
        $book['discount-price'] = $data->item(0)->getAttribute('data-discount-price');

        // extracts cover
        $image = $this->helper->find($dom, "//div[@id='product-image']/img");
        $book['image-link'] = $image->item(0)->getAttribute('data-src');

        // gets authors (multiple possible)
        $authors = $this->helper->find($dom, "(//div[@class='authors'])[1]/a");
        if ($authors->length) {
            foreach ($authors as $author) {
                $book['authors'][] = $author->textContent;
            }
        }

        // extracts year from the string
        $year = $this->helper->find($dom, "//div[@class='publisher']");
        $year = $year->item(0)->textContent;
        preg_match_all('/\d{4}/', $year, $result);
        $book['year'] = $result[0][0];

        // extracts number of pages
        $pages = $this->helper->find($dom, "//div[@class='pages2']/span");
        if ($pages->item(0)) {
            $book['pages'] = $pages->item(0)->getAttribute('data-pages');
        } else {
            $book['pages'] = null;
        }

        // extracts and formats isbn
        $isbn = $this->helper->find($dom, "//div[@class='isbn']");
        $isbn = $isbn->item(0)->textContent;
        $result = preg_split('/,/', $isbn); // in case if there are 2 isbns
        $book['has_x'] = preg_match('/X/', $result[0]);
        $book['isbn'] = preg_replace('/\D/', '', $result[0]);

        // extracts annotation (some books have small and full annotations, that's why
        // we first try to get full one
        $fullAnnotation = $this->helper->find($dom, "//div[@id='fullannotation']/p");

        $annotation = '';
        if ($fullAnnotation->item(0)) {
            $annotation = $fullAnnotation->item(0)->textContent;
        } else {
            // if there is no full version, try to get normal product-about
            $text = $this->helper->find($dom, "//div[@id='product-about']//p");
            $annotation = $text->item(0)->textContent;
        }

        // removes tags from annotation
        $book['annotation'] = preg_replace('/\<br>|\<em>|\<\/em>/', '/n ', $annotation);

        return $book;
    }

    /**
     * Saves prices for the given book
     * @param $book
     * @return void
     */
    public function savePrices($book): void
    {
        $sql = $this->database->prepare('INSERT into shop_prices (book_isbn, price, discount_price, shop_id) VALUES (?, ?, ?, ?)');
        $sql->execute([
            $book['isbn'],
            $book['price'],
            $book['discount-price'],
            self::STORE_ID
        ]);
    }

    /**
     * Saves cover link for the given book
     * @param $book
     * @return void
     */
    public function saveCoverLink($book): void
    {
        $sql = $this->database->prepare('INSERT into book_cover_links (book_isbn, link) VALUES (?, ?) ON CONFLICT DO NOTHING');
        $sql->execute([
            $book['isbn'],
            $book['image-link']
        ]);
    }
}