<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$app = 'crud';
$fixtures = __DIR__ . '/../fixtures/data/fixtures/fixtures.php';
if (!include(dirname(__FILE__).'/../../bootstrap/functional.php'))
{
  return;
}

require_once(dirname(__FILE__).'/restBrowser.class.php');

$b = new RestBrowser();
$b->browse(array('non-verbose-templates'));
