<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Executes the next migration down
 *
 * @package    symfony
 * @subpackage propel
 * @author     François Zaninotto
 * @version    SVN: $Id: sfPropelBuildModelTask.class.php 23922 2009-11-14 14:58:38Z fabien $
 */
class sfPropelMigrateDownTask extends sfPropelBaseTask
{
    private const OK = 0;
    private const NOT_OK = 1;

    /**
     * @see sfTask
     */
    protected function configure()
    {
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('migration-dir', null, sfCommandOption::PARAMETER_OPTIONAL, 'The migrations subdirectory', 'lib/model/migration'),
            new sfCommandOption('migration-table', null, sfCommandOption::PARAMETER_OPTIONAL, 'The name of the migration table', 'migration'),
            new sfCommandOption('verbose', null, sfCommandOption::PARAMETER_NONE, 'Enables verbose output'),
        ));
        $this->namespace = 'propel';
        $this->name = 'down';
        $this->aliases = array('migration-down');
        $this->briefDescription = 'Executes the next migration down';

        $this->detailedDescription = <<<EOF
The [propel:up|INFO] checks the version of the database structure, and looks for migration files already executed (i.e. with a lower version timestamp). The last executed migration found is reversed.

The task reads the database connection settings in [config/databases.yml|COMMENT].

The task looks for migration classes in [lib/model/migration|COMMENT].
EOF;
    }

    /**
     * @see sfTask
     */
    protected function execute($arguments = array(), $options = array())
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connections = $this->getConnections($databaseManager);
        $manager = new sfPropelMigrationManager();
        $manager->setConnections($connections);
        $manager->setMigrationTable($options['migration-table']);
        $migrationDirectory = sfConfig::get('sf_root_dir') . DIRECTORY_SEPARATOR . $options['migration-dir'];
        $manager->setMigrationDir($migrationDirectory);

        $latestExecutedMigrations = $manager->getExecutedMigrationNamesForBatch($manager->getLatestBatch());
        if (empty($latestExecutedMigrations))
        {
            $this->logSection('propel', 'No migration were ever executed on this database - nothing to reverse.');
            return self::OK;
        }
        $this->logSection('propel', 'Executing migrations down');

        foreach ($latestExecutedMigrations as $migrationName) {
            $migration = $manager->getMigrationObject($migrationName);
            if (false === $migration->preDown($manager)) {
                $this->logSection('propel', 'preDown() returned false. Aborting migration.', null, 'ERROR');

                return self::NOT_OK;
            }

            foreach ($migration->getDownSQL() as $datasource => $sql) {
                $connection = $manager->getConnection($datasource);
                if ($options['verbose']) {
                    $this->logSection('propel',
                        sprintf('  Connecting to database "%s" using DSN "%s"', $datasource, $connection['dsn']), null,
                        'COMMENT');
                }
                $pdo = $manager->getPdoConnection($datasource);
                $res = 0;
                $statements = PropelSQLParser::parseString($sql);
                foreach ($statements as $statement) {
                    try {
                        if ($options['verbose']) {
                            $this->logSection('propel', sprintf('  Executing statement "%s"', $statement), null, 'COMMENT');
                        }
                        $stmt = $pdo->prepare($statement);
                        $stmt->execute();
                        $res++;
                    } catch (PDOException $e) {
                        $this->logSection(sprintf('Failed to execute SQL "%s". Aborting migration.', $statement), null,
                            'ERROR');

                        return self::NOT_OK;
                        // continue
                    }
                }
                if (! $res) {
                    $this->logSection('propel', sprintf(
                        'Please review the code in "%s"',
                        $manager->getMigrationDir() . DIRECTORY_SEPARATOR . $migrationName
                    ));
                    $this->logSection('propel', 'Migration aborted', null, 'ERROR');

                    return self::NOT_OK;
                }
                $this->logSection('propel', sprintf(
                    '%d of %d SQL statements executed successfully on datasource "%s"',
                    $res,
                    count($statements),
                    $datasource
                ));

                $manager->removeExecutedMigration($datasource, $migrationName);
                if ($options['verbose']) {
                    $this->logSection(
                        'propel',
                        sprintf('  Removed %s from executed migrations for datasource "%s"', $migrationName, $datasource),
                        null,
                        'COMMENT'
                    );
                }
            }

            $migration->postDown($manager);
        }

        $this->logSection('propel', sprintf('Reverse migration complete. %d migrations were rolled back.', count($latestExecutedMigrations)));

        return self::OK;
    }

}
