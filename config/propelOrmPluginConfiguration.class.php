<?php

/**
 * PropelOrmPlugin configuration.
 */
class propelOrmPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    sfConfig::set('sf_orm', 'propel');

    if (!sfConfig::get('sf_admin_module_web_dir'))
    {
      sfConfig::set('sf_admin_module_web_dir', '/propelOrmPlugin');
    }

    if (! sfConfig::get('sf_propel_path', false)) {
        throw new InvalidArgumentException(
            'Please configure `sf_propel_path` to point to the Propel vendor directory.'
        );
    }

    if (!Propel::isInit())
    {
      if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled'))
      {
        Propel::setLogger(new sfPropelLogger($this->dispatcher));
      }

      $propelConfiguration = new PropelConfiguration();
      Propel::setConfiguration($propelConfiguration);

      $this->dispatcher->notify(new sfEvent($propelConfiguration, 'propel.configure'));

      Propel::initialize();
    }

    $this->dispatcher->connect('user.change_culture', array('sfPropel', 'listenToChangeCultureEvent'));

    if (sfConfig::get('sf_web_debug'))
    {
      $this->dispatcher->connect('debug.web.load_panels', array('sfWebDebugPanelPropel', 'listenToAddPanelEvent'));

      $modules = sfConfig::get('sf_enabled_modules', array());
      $modules[] = 'sfPropelORMExplain';

      sfConfig::set('sf_enabled_modules', $modules);
    }

    if (sfConfig::get('sf_test'))
    {
      $this->dispatcher->connect('context.load_factories', array($this, 'clearAllInstancePools'));
    }
  }

  /**
   * Clears all instance pools.
   *
   * This method is used to clear Propel's static instance pools between
   * requests performed in functional tests.
   */
  public function clearAllInstancePools()
  {
    $finder = sfFinder::type('file')->name('*TableMap.php');
    foreach ($finder->in($this->configuration->getModelDirs()) as $file)
    {
      $omClass = basename($file, 'TableMap.php');
      if (class_exists($omClass) && is_subclass_of($omClass, 'BaseObject'))
      {
        $peer = constant($omClass.'::PEER');
        call_user_func(array($peer, 'clearInstancePool'));
      }
    }
  }
}
