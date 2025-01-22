<?php

/**
 * Class Book
 *
 * Represents a book item with authors, pages, ISBN, and cover properties.
 * This class extends Item and implements JsonSerializable for JSON representation.
 */
class Book extends Item implements JsonSerializable
{
    protected $_authors;
    protected $_pages;
    protected $_isbn;
    protected $_cover;

    public function __construct($itemParams, $bookParams)
    {
        parent::__construct($itemParams);

        $this->_authors = $bookParams["authors"] ?? [];
        $this->_pages = $bookParams["pages"] ?? 0;
        $this->_isbn = $bookParams["isbn"] ?? '';
        $this->_cover = $bookParams["cover"] ?? '';
    }

    /**
     * @return array The names of the authors
     */
    public function getAuthors()
    {
        return $this->_authors;
    }

    /**
     * @param array $authors
     */
    public function setAuthors(array $authors)
    {
        $this->_authors = $authors;
    }

    /**
     * @return int The page count
     */
    public function getPages()
    {
        return $this->_pages;
    }

    /**
     * @param int $pages
     */
    public function setPages(int $pages)
    {
        $this->_pages = $pages;
    }

    /**
     * @return string The ISBN number
     */
    public function getIsbn()
    {
        return $this->_isbn;
    }

    /**
     * @param string $isbn
     */
    public function setIsbn(string $isbn)
    {
        $this->_isbn = $isbn;
    }

    /**
     * @return string The URL to the cover image
     */
    public function getCover()
    {
        return $this->_cover;
    }

    /**
     * @param string $cover
     */
    public function setCover(string $cover)
    {
        $this->_cover = $cover;
    }

    /**
     * Serializes the book object into JSON format.
     * 
     * @return array Serialized representation of the book object.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id'             => $this->_id ?? null,
            'title'          => $this->_title ?? '',
            'description'    => $this->_description ?? '',
            'publishedDate'  => $this->_publishedDate ?? '',
            'available'      => $this->_available ?? false,
            'borrowedDate'   => $this->_borrowedDate ?? '',
            'returnDate'     => $this->_returnDate ?? '',
            'borrower'       => $this->_borrower ?? null,
            'holds'          => $this->_holds ?? [],
            'authors'        => $this->_authors,
            'pages'          => $this->_pages,
            'isbn'           => $this->_isbn,
            'cover'          => $this->_cover
        ];
    }
}

