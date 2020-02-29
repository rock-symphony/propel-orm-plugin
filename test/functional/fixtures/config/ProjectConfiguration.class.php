<?php

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->enablePlugins(array('sfPropelORMPlugin'));
    $this->setPluginPath('sfPropelORMPlugin', realpath(dirname(__FILE__) . '/../../../..'));

    // SVN way
    //sfConfig::set('sf_propel_path', SF_DIR.'/../lib/vendor/propel');
    //sfConfig::set('sf_phing_path', SF_DIR.'/../lib/vendor/phing');

    // Git way
    sfConfig::set('sf_propel_path', realpath(dirname(__FILE__) . '/../../../../lib/vendor/propel'));
    sfConfig::set('sf_phing_path', realpath(dirname(__FILE__) . '/../../../../lib/vendor/phing'));
  }

  public function initializePropel($app)
  {
    // build Propel om/map/sql/forms
    $files = glob(sfConfig::get('sf_lib_dir').'/model/om/*.php');
    if (false === $files || !count($files))
    {
      chdir(sfConfig::get('sf_root_dir'));
      $task = new sfPropelBuildModelTask($this->dispatcher, new sfFormatter());
      ob_start();
      $task->run();
      $output = ob_get_clean();
    }

    $files = glob(sfConfig::get('sf_data_dir').'/sql/*.php');
    if (false === $files || !count($files))
    {
      chdir(sfConfig::get('sf_root_dir'));
      $task = new sfPropelBuildSqlTask($this->dispatcher, new sfFormatter());
      ob_start();
      $task->run();
      $output = ob_get_clean();
    }

    $files = glob(sfConfig::get('sf_lib_dir').'/form/base/*.php');
    if (false === $files || !count($files))
    {
      chdir(sfConfig::get('sf_root_dir'));
      $task = new sfPropelBuildFormsTask($this->dispatcher, new sfFormatter());
      $task->run(array(), array('application='.$app));
    }

    $files = glob(sfConfig::get('sf_lib_dir').'/filter/base/*.php');
    if (false === $files || !count($files))
    {
      chdir(sfConfig::get('sf_root_dir'));
      $task = new sfPropelBuildFiltersTask($this->dispatcher, new sfFormatter());
      $task->run(array(), array('application='.$app));
    }
  }
}
