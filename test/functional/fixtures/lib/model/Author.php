<?php

/**
 * Subclass for representing a row from the 'author' table.
 *
 *
 *
 * @package lib.model
 */
class Author extends BaseAuthor
{
    /**
     * @param array $fields
     * @return Author
     */
    public static function create(array $fields)
    {
        $author = new Author();
        $author->fromArray($fields, BasePeer::TYPE_FIELDNAME);
        $author->save();

        return $author;
    }

	public function __toString()
  {
    return $this->getName();
  }

}
