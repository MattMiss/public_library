<?php

/**
 * Class Item
 *
 * Represents a general item with properties such as ID, title, description, published date,
 * availability, borrowed date, return date, borrower, and holds. This class is abstract and
 * implements JsonSerializable for JSON representation.
 */

 abstract class Item implements JsonSerializable
 {
     protected $_id;
     protected $_title;
     protected $_description;
     protected $_publishedDate;
     protected $_available;
     protected $_borrowedDate;
     protected $_returnDate;
     protected $_borrower;
     protected $_holds;
 
     public function __construct($params)
     {
         $this->_id = $params['id'] ?? null;
         $this->_title = $params['title'] ?? '';
         $this->_description = $params['desc'] ?? '';
         $this->_publishedDate = $params['pubDate'] ?? null;
         $this->_available = $params['available'] ?? true;
         $this->_borrowedDate = $params['borrowDate'] ?? null;
         $this->_returnDate = $params['returnDate'] ?? null;
         $this->_borrower = $params['borrower'] ?? null;
         $this->_holds = $params['holds'] ?? [];
     }
 
     public function getId()
     {
         return $this->_id;
     }
 
     public function setId($id)
     {
         $this->_id = $id;
     }
 
     public function getTitle()
     {
         return $this->_title;
     }
 
     public function setTitle($title)
     {
         $this->_title = $title;
     }
 
     public function getDescription()
     {
         return $this->_description;
     }
 
     public function setDescription($description)
     {
         $this->_description = $description;
     }
 
     public function getPublishedDate()
     {
         return $this->_publishedDate;
     }
 
     public function setPublishedDate($publishedDate)
     {
         $this->_publishedDate = $publishedDate;
     }
 
     public function getAvailable()
     {
         return $this->_available;
     }
 
     public function setAvailable($available)
     {
         $this->_available = $available;
     }
 
     public function getBorrowedDate()
     {
         return $this->_borrowedDate ? date('Y-m-d', $this->_borrowedDate) : null;
     }
 
     public function setBorrowedDate($borrowedDate)
     {
         $this->_borrowedDate = strtotime($borrowedDate);
     }
 
     public function getReturnDate()
     {
         return $this->_returnDate ? date('Y-m-d', $this->_returnDate) : null;
     }
 
     public function setReturnDate($returnDate)
     {
         $this->_returnDate = strtotime($returnDate);
     }
 
     public function getBorrower()
     {
         return $this->_borrower;
     }
 
     public function setBorrower($borrower)
     {
         $this->_borrower = $borrower;
     }
 
     public function getHolds()
     {
         return $this->_holds;
     }
 
     public function setHolds($holds)
     {
         $this->_holds = $holds;
     }
 
     public function placeHold($id)
     {
         $this->_holds[] = $id;
     }
 
     public function checkIn()
     {
         $this->_available = true;
         $this->_borrowedDate = null;
         $this->_returnDate = null;
         $this->_borrower = null;
     }
 
     public function checkOut($id)
     {
         $this->_available = false;
         $this->_borrower = $id;
 
         $today = strtotime("today");
         $return = strtotime("+14 day", $today);
 
         $this->_borrowedDate = $today;
         $this->_returnDate = $return;
     }
 
     public function extendDueDate($days)
     {
         if ($this->_returnDate) {
             $this->_returnDate = strtotime("+$days days", $this->_returnDate);
         }
     }
 
     #[\ReturnTypeWillChange]
     public function jsonSerialize()
     {
         return [
             'id' => $this->_id,
             'title' => $this->_title,
             'description' => $this->_description,
             'publishedDate' => $this->getPublishedDate(),
             'available' => $this->_available,
             'borrowedDate' => $this->getBorrowedDate(),
             'returnDate' => $this->getReturnDate(),
             'borrower' => $this->_borrower,
             'holds' => $this->_holds
         ];
     }
 }
 