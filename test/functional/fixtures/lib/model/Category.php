<?php

/**
 * Subclass for representing a row from the 'category' table.
 *
 *
 *
 * @package lib.model
 */
class Category extends BaseCategory
{
  /**
   * @param array $fields
   * @return Category
   */
  public static function create($fields)
  {
    $category = new Category();
    $category->fromArray($fields, BasePeer::TYPE_FIELDNAME);
    $category->save();

    return $category;
  }

  public function __toString()
  {
    return $this->getName();
  }
}
