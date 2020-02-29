<?php

/**
 * Subclass for representing a row from the 'movie' table.
 *
 *
 *
 * @package lib.model
 */
class Movie extends BaseMovie
{
    /**
     * @param array $fields
     * @return Movie
     */
    public static function create(array $fields)
    {
        $movie = new Movie();
        $movie->fromArray($fields, BasePeer::TYPE_FIELDNAME);
        $movie->save();

        return $movie;
    }
}
