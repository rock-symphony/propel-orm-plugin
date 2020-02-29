<?php

/**
 * Subclass for representing a row from the 'article' table.
 *
 *
 *
 * @package lib.model
 */
class Article extends BaseArticle
{
  /**
   * @param array $fields
   * @return Article
   */
  public static function create(array $fields)
  {
    $article = new Article();
    $article->fromArray($fields, BasePeer::TYPE_FIELDNAME);
    $article->save();

    return $article;
  }
}
