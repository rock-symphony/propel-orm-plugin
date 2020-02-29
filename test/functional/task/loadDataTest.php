<?php

$app = 'frontend';
$fixtures = __DIR__ . '/../fixtures/data/fixtures/fixtures.php';
require_once dirname(__FILE__).'/../../bootstrap/functional.php';

$browser = new sfTestFunctional(new sfBrowser(), null, array(
  'propel' => 'sfTesterPropel'
));


$browser->info('Check that ARRAY columns are loaded');
$criteria = AuthorQuery::create()
  ->filterByHobbies(array('foo', 'bar'), Criteria::CONTAINS_ALL);
$browser->with('propel')->begin()->
  check('Author', $criteria)->
end();
