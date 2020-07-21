<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Create form classes for the current model.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfPropelBuildFormsTask.class.php 23927 2009-11-14 16:10:57Z fabien $
 */
class sfPropelBuildFormsTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_OPTIONAL, 'The connection name', false),
      new sfCommandOption('model-dir-name', null, sfCommandOption::PARAMETER_REQUIRED, 'The model dir name', 'model'),
      new sfCommandOption('form-dir-name', null, sfCommandOption::PARAMETER_REQUIRED, 'The form dir name', 'form'),
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('generator-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The generator class', 'sfPropelFormGenerator'),
      new sfCommandOption('all-connections', null, sfCommandOption::PARAMETER_REQUIRED, 'To build all connections', true),
    ));

    $this->namespace = 'propel';
    $this->name = 'build-forms';
    $this->briefDescription = 'Creates form classes for the current model';

    $this->detailedDescription = <<<EOF
The [propel:build-forms|INFO] task creates form classes from the schema:

  [./symfony propel:build-forms|INFO]

The task read the schema information in [config/*schema.xml|COMMENT] and/or
[config/*schema.yml|COMMENT] from the project and all installed plugins.

The task use by default [all-connections|COMMENT] as defined in [config/databases.yml|COMMENT].
You can use only one connection by using the [--connection|COMMENT] option:

  [./symfony propel:build-forms --connection="name"|INFO]

The model form classes files are created in [lib/form|COMMENT].

This task never overrides custom classes in [lib/form|COMMENT].
It only replaces base classes generated in [lib/form/base|COMMENT].
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    if (false === $options['connection'] && false === $options['all-connections'])
    {
        $options['connection'] = 'propel';
    }
    elseif (false !== $options['connection'])
    {
        $options['all-connections'] = false;
    }

    $this->logSection('propel', 'generating form classes');

    $generatorManager = new sfGeneratorManager($this->configuration);
    $generatorManager->generate($options['generator-class'], array(
      'connection'      => $options['connection'],
      'model_dir_name'  => $options['model-dir-name'],
      'form_dir_name'   => $options['form-dir-name'],
      'all_connections' => $options['all-connections'],
    ));

    $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);

    $constants = array(
      'PROJECT_NAME' => isset($properties['symfony']['name']) ? $properties['symfony']['name'] : 'symfony',
      'AUTHOR_NAME'  => isset($properties['symfony']['author']) ? $properties['symfony']['author'] : 'Your name here'
    );

    // customize php and yml files
    $finder = sfFinder::type('file')->name('*.php');
    $this->getFilesystem()->replaceTokens($finder->in(sfConfig::get('sf_lib_dir').'/form/'), '##', '##', $constants);

    // check for base form class
    if (!class_exists('BaseForm'))
    {
      $file = sfConfig::get('sf_lib_dir').'/'.$options['form-dir-name'].'/BaseForm.class.php';
      $this->getFilesystem()->copy(sfConfig::get('sf_symfony_lib_dir').'/task/generator/skeleton/project/lib/form/BaseForm.class.php', $file);
      $this->getFilesystem()->replaceTokens($file, '##', '##', $constants);
    }
  }
}
