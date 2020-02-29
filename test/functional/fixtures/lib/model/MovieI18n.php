<?php

/**
 * Subclass for representing a row from the 'movie_i18n' table.
 *
 *
 *
 * @package lib.model
 */
class MovieI18n extends BaseMovieI18n
{
    /**
     * @param array $fields
     * @return MovieI18n
     */
    public static function create(array $fields)
    {
        $i18n = new MovieI18n();
        $i18n->fromArray($fields, BasePeer::TYPE_FIELDNAME);
        $i18n->save();

        return $i18n;
    }
}
