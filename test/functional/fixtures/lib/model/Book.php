<?php

/**
 * Subclass for representing a row from the 'book' table.
 *
 *
 *
 * @package lib.model
 */
class Book extends BaseBook
{
  /**
   * @param array $fields
   * @return Book
   */
  public static function create($fields)
  {
    $book = new Book();
    $book->fromArray($fields, BasePeer::TYPE_FIELDNAME);
    $book->save();

    return $book;
  }

  public function __toString()
  {
    return $this->getName();
  }
}
