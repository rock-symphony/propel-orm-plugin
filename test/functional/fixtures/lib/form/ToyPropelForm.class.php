<?php

/**
 * ToyPropel form.
 *
 * @package    propel
 * @subpackage form
 * @author     Your name here
 */
class ToyPropelForm extends BaseToyPropelForm
{
  public function configure(): void
  {
    $this->embedI18n(array('fr', 'en'));
  }
}
