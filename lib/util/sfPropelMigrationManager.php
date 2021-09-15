<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Service class for preparing and executing migrations
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.util
 */
class sfPropelMigrationManager
{
  protected $connections;
  protected $pdoConnections = array();
  protected $migrationTable = 'propel_migration';
  protected $migrationDir;
  protected $migrationDatabase = 'default';

  public function __construct()
  {
    $this->migrationDatabase = sfConfig::get('sf_migration_database');
  }

  /**
   * Set the database connection settings
   *
   * @param array $connections
   */
  public function setConnections($connections)
  {
    $this->connections = $connections;
  }

  /**
   * Get the database connection settings
   *
   * @return array
   */
  public function getConnections()
  {
    return $this->connections;
  }

  public function getConnection($datasource)
  {
    if (!isset($this->connections[$datasource])) {
      throw new InvalidArgumentException(sprintf('Unknown datasource "%s"', $datasource));
    }

    return $this->connections[$datasource];
  }

  public function getPdoConnection($datasource)
  {
    if (!isset($this->pdoConnections[$datasource])) {
      $buildConnection = $this->getConnection($datasource);
      $buildConnection['dsn'] = str_replace("@DB@", $datasource, $buildConnection['dsn']);

      $this->pdoConnections[$datasource] = Propel::initConnection($buildConnection, $datasource);
    }

    return $this->pdoConnections[$datasource];
  }

  public function getPlatform($datasource)
  {
    $params = $this->getConnection($datasource);
    $adapter = $params['adapter'];
    $adapterClass = ucfirst($adapter) . 'Platform';

    return new $adapterClass();
  }

  /**
   * Set the migration table name
   *
   * @param string $migrationTable
   */
  public function setMigrationTable($migrationTable)
  {
    $this->migrationTable = $migrationTable;
  }

  /**
   * get the migration table name
   *
   * @return string
   */
  public function getMigrationTable()
  {
    return $this->migrationTable;
  }

  /**
   * Set the path to the migration classes
   *
   * @param string $migrationDir
   */
  public function setMigrationDir($migrationDir)
  {
    $this->migrationDir = $migrationDir;
  }

  /**
   * Get the path to the migration classes
   *
   * @return string
   */
  public function getMigrationDir()
  {
    return $this->migrationDir;
  }

  /**
   * @return string
   */
  public function getMigrationDatabase(): string
  {
    return $this->migrationDatabase;
  }

  /**
   * @param string $migrationDatabase
   */
  public function setMigrationDatabase(string $migrationDatabase): void
  {
    $this->migrationDatabase = $migrationDatabase;
  }

  /**
   * @return string[]
   */
  public function getExecutedMigrationNames()
  {
    $connections = $this->getConnections();

    if (!isset($connections[$this->migrationDatabase])) {
      throw new Exception('Provided migration database does not exist');
    }

    $migrationNames = [];

    $pdo = $this->getPdoConnection($this->migrationDatabase);
    $sql = sprintf('SELECT migration FROM %s', $this->getMigrationTable());

    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute();

      while ($migrationName = $stmt->fetchColumn()) {
        $migrationNames[] = $migrationName;
      }
    } catch (PDOException $e) {
      $this->createMigrationTable();
    }

    return array_unique($migrationNames);
  }

  /**
   * @return string
   */
  public function getLatestExecutedMigrationName()
  {
    $connections = $this->getConnections();

    if (!isset($connections[$this->migrationDatabase])) {
      throw new Exception('Provided migration database does not exist');
    }

    $migrationName = null;

    $pdo = $this->getPdoConnection($this->migrationDatabase);
    $sql = sprintf('SELECT migration FROM %s ORDER BY id DESC LIMIT 1', $this->getMigrationTable());

    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute();

      $migrationName = $stmt->fetchColumn();
    } catch (PDOException $e) {
      $this->createMigrationTable();
    }

    return $migrationName;
  }

  /**
   * @return bool
   */
  public function migrationTableExists()
  {
    $pdo = $this->getPdoConnection($this->migrationDatabase);
    $sql = sprintf('SELECT migration FROM %s', $this->getMigrationTable());

    $stmt = $pdo->prepare($sql);

    try {
      $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }

    return true;
  }

  public function createMigrationTable()
  {
    $connections = $this->getConnections();

    if (!isset($connections[$this->migrationDatabase])) {
      throw new Exception('Provided migration database does not exist');
    }

    $platform = $this->getPlatform($this->migrationDatabase);

    $database = new Database($this->migrationDatabase);
    $database->setPlatform($platform);

    $table = new Table($this->getMigrationTable());
    $table->setIdMethod('native');
    $database->addTable($table);

    $column = new Column('id');
    $column->getDomain()->copy($platform->getDomainForType('INTEGER'));
    $column->setNotNull(true);
    $column->setPrimaryKey(true);
    $column->setAutoIncrement(true);
    $table->addColumn($column);

    $column = new Column('migration');
    $column->getDomain()->copy($platform->getDomainForType('VARCHAR'));
    $column->setNotNull(true);
    $column->setUnique(true);
    $table->addColumn($column);

    $statements = $platform->getAddTableDDL($table);
    $pdo = $this->getPdoConnection($this->migrationDatabase);
    $res = PropelSQLParser::executeString($statements, $pdo);

    if (!$res) {
      throw new Exception(sprintf('Unable to create migration table in datasource "%s"', $this->migrationDatabase));
    }
  }

  public function addExecutedMigration($migrationName)
  {
    $platform = $this->getPlatform($this->migrationDatabase);
    $pdo = $this->getPdoConnection($this->migrationDatabase);
    $sql = sprintf('INSERT INTO %s (%s) VALUES (?)',
      $this->getMigrationTable(),
      $platform->quoteIdentifier('migration'),
    );
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $migrationName, PDO::PARAM_STR);
    $stmt->execute();
  }

  public function removeExecutedMigration($migrationName)
  {
    $platform = $this->getPlatform($this->migrationDatabase);
    $pdo = $this->getPdoConnection($this->migrationDatabase);
    $sql = sprintf('DELETE FROM %s WHERE %s = ?',
      $this->getMigrationTable(),
      $platform->quoteIdentifier('migration')
    );
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $migrationName, PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * @return string[]
   */
  public function getExistingMigrationNames()
  {
    $path = $this->getMigrationDir();

    $migrationNames = array();

    if (is_dir($path)) {
      foreach (scandir($path) as $file) {
        if (preg_match('/^PropelMigration_(\d+)\.php$/', $file, $matches)) {
          $migrationNames[] = trim($file, '.php');
        }
      }
    }

    return $migrationNames;
  }

  /**
   * @return string[]
   */
  public function getMissingMigrationNames()
  {
    return array_diff($this->getExistingMigrationNames(), $this->getExecutedMigrationNames());
  }

  /**
   * @return string
   */
  public static function generateMigrationClassName($timestamp)
  {
    return sprintf('PropelMigration_%d', $timestamp);
  }

  public function getMigrationObject($migrationName)
  {
    require_once sprintf('%s/%s.php',
      $this->getMigrationDir(),
      $migrationName
    );

    return new $migrationName();
  }

  public function generateMigrationClassBody($migrationsUp, $migrationsDown, $timestamp)
  {
    $timeInWords = date('Y-m-d H:i:s', $timestamp);
    $migrationAuthor = ($author = $this->getUser()) ? 'by ' . $author : '';
    $migrationClassName = $this->generateMigrationClassName($timestamp);
    $migrationUpString = var_export($migrationsUp, true);
    $migrationDownString = var_export($migrationsDown, true);
    $migrationClassBody = <<<EOP
<?php

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version $timestamp.
 * Generated on $timeInWords $migrationAuthor
 */
class $migrationClassName
{

    public function preUp(\$manager)
    {
        // add the pre-migration code here
    }

    public function postUp(\$manager)
    {
        // add the post-migration code here
    }

    public function preDown(\$manager)
    {
        // add the pre-migration code here
    }

    public function postDown(\$manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return $migrationUpString;
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return $migrationDownString;
    }

}
EOP;

    return $migrationClassBody;
  }

  public function generateMigrationFileName($timestamp)
  {
    return sprintf('%s.php', self::generateMigrationClassName($timestamp));
  }

  public function getUser()
  {
    if (function_exists('posix_getuid')) {
      $currentUser = posix_getpwuid(posix_getuid());
      if (isset($currentUser['name'])) {
        return $currentUser['name'];
      }
    }

    return '';
  }
}
