<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class is the Propel implementation of sfPager.  It interacts with the propel record set and
 * manages criteria.
 *
 * @package    sfPropelPlugin
 * @subpackage addon
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfPropelPager.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sfPropelPager extends sfPager
{
  protected ?Criteria $criteria = null;
  protected $con = null;
  protected string $peer_method_name       = 'doSelect';
  protected string $peer_count_method_name = 'doCount';

  /**
   * @param string $class
   * @param int $maxPerPage
   * @see sfPager
   */
  public function __construct(string $class, int $maxPerPage = 10)
  {
    parent::__construct($class, $maxPerPage);

    $this->setCriteria(new Criteria());
    $this->tableName = constant($this->getClassPeer().'::TABLE_NAME');
  }

  public function init($con = null): void
  {
    $this->con = $con;
    $this->results = null;

    $hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
    $maxRecordLimit = $this->getMaxRecordLimit();

    $criteriaForCount = clone $this->getCriteria();
    $criteriaForCount
      ->setOffset(0)
      ->setLimit(0)
      ->clearGroupByColumns()
    ;

    $count = call_user_func(array($this->getClassPeer(), $this->getPeerCountMethod()), $criteriaForCount, false, $this->con);

    $this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);

    $criteria = $this->getCriteria()
      ->setOffset(0)
      ->setLimit(0)
    ;

    if (0 == $this->getPage() || 0 == $this->getMaxPerPage())
    {
      $this->setLastPage(0);
    }
    else
    {
      $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

      $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
      $criteria->setOffset($offset);

      if ($hasMaxRecordLimit)
      {
        $maxRecordLimit = $maxRecordLimit - $offset;
        if ($maxRecordLimit > $this->getMaxPerPage())
        {
          $criteria->setLimit($this->getMaxPerPage());
        }
        else
        {
          $criteria->setLimit($maxRecordLimit);
        }
      }
      else
      {
        $criteria->setLimit($this->getMaxPerPage());
      }
    }
  }

  protected function retrieveObject(int $offset)
  {
    $criteriaForRetrieve = clone $this->getCriteria();
    $criteriaForRetrieve
      ->setOffset($offset - 1)
      ->setLimit(1)
    ;

    $results = call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $criteriaForRetrieve, $this->con);

    return is_array($results) && isset($results[0]) ? $results[0] : null;
  }

  public function getResults(): array
  {
    return call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $this->getCriteria(), $this->con);
  }

  /**
   * Returns the peer method name.
   *
   * @return string
   */
  public function getPeerMethod(): string
  {
    return $this->peer_method_name;
  }

  /**
   * Sets the peer method name.
   *
   * @param string $method A method on the current peer class
   */
  public function setPeerMethod(string $method): void
  {
    $this->peer_method_name = $method;
  }

  /**
   * Returns the peer count method name.
   *
   * @return string
   */
  public function getPeerCountMethod(): string
  {
    return $this->peer_count_method_name;
  }

  /**
   * Sets the peer count method name.
   *
   * @param string $peer_count_method_name
   */
  public function setPeerCountMethod(string $peer_count_method_name): void
  {
    $this->peer_count_method_name = $peer_count_method_name;
  }

  /**
   * Returns the name of the current model class' peer class.
   *
   * @return string
   */
  public function getClassPeer(): string
  {
    return constant($this->class.'::PEER');
  }

  /**
   * Returns the current Criteria.
   *
   * @return Criteria
   */
  public function getCriteria(): Criteria
  {
    return $this->criteria;
  }

  /**
   * Sets the Criteria for the current pager.
   *
   * @param Criteria $criteria
   */
  public function setCriteria(Criteria $criteria): void
  {
    $this->criteria = $criteria;
  }
}
