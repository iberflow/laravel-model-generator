<?php

namespace Iber\Generator\Commands;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Pluralizer;
use Illuminate\Console\GeneratorCommand;
use Iber\Generator\Utilities\RuleProcessor;
use Iber\Generator\Utilities\SetGetGenerator;
use Iber\Generator\Utilities\VariableConversion;
use Symfony\Component\Console\Input\InputOption;

class MakeModelsCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build models from existing schema.';

    /**
     * Default model namespace.
     *
     * @var string
     */
    protected $namespace = 'Models/';

    /**
     * Default class the model extends.
     *
     * @var string
     */
    protected $extends = 'Illuminate\Database\Eloquent\Model';

    /**
     * Rule processor class instance.
     *
     * @var
     */
    protected $ruleProcessor;

    /**
     * Rules for columns that go into the guarded list.
     *
     * @var array
     */
    protected $guardedRules = 'ends:_guarded'; //['ends' => ['_id', 'ids'], 'equals' => ['id']];

    /**
     * Rules for columns that go into the fillable list.
     *
     * @var array
     */
    protected $fillableRules = '';

    /**
     * Rules for columns that set whether the timestamps property is set to true/false.
     *
     * @var array
     */
    protected $timestampRules = 'ends:_at'; //['ends' => ['_at']];

    /**
     * Contains the template stub for set function
     * @var string
     */
    protected $setFunctionStub;
    /**
     * Contains the template stub for get function
     * @var string
     */
    protected $getFunctionStub;

    /**
     * @var string
     */
    protected $databaseEngine = 'mysql';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if ($this->option("getset")) {
            // load the get/set function stubs
            $folder = __DIR__ . '/../stubs/';

            $this->setFunctionStub = $this->files->get($folder . "setFunction.stub");
            $this->getFunctionStub = $this->files->get($folder . "getFunction.stub");
        }

        // create rule processor

        $this->ruleProcessor = new RuleProcessor();
        $this->databaseEngine = config('database.default', 'mysql');

        \Event::listen(StatementPrepared::class, function ($event) {
            /** @var \PDOStatement $statement */
            $statement = $event->statement;
            /** @var Connection $connection */
            $connection = $event->connection;

            $pdo = $connection->getPdo();
            $statement->setFetchMode($pdo::FETCH_CLASS, \stdClass::class);
        });

        $tables = $this->getSchemaTables();

        foreach ($tables as $table) {
            $table = (object) $table;
            $this->generateTable( ->name );
        }
    }

    /**
     * Get schema tables.
     *
     * @return array
     */
    protected function getSchemaTables()
    {
        $filterTablesWhere = '';
        if ($this->option("tables")) {
            $tableNamesToFilter = explode(',', $this->option('tables'));
            if (is_array($tableNamesToFilter) && count($tableNamesToFilter) > 0) {
                $filterTablesWhere = ' AND table_name IN (\'' . implode('\', \'', $tableNamesToFilter) . '\')';
            }
        }

        switch ($this->databaseEngine) {
            case 'mysql':
                $tables = \DB::select("SELECT table_name AS name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_schema = '" . env('DB_DATABASE') . "'" . $filterTablesWhere);
                break;

            case 'sqlsrv':
            case 'dblib':
                $tables = \DB::select("SELECT table_name AS name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_catalog = '" . env('DB_DATABASE') . "'" . $filterTablesWhere);
                break;

            case 'pgsql':
                $tables = \DB::select("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = 'public' AND table_type='BASE TABLE' AND table_catalog = '" . env('DB_DATABASE') . "'" . $filterTablesWhere);
                break;
        }

        return $tables;
    }

    /**
     * Generate a model file from a database table.
     *
     * @param $table
     * @return void
     */
    protected function generateTable($table)
    {
        //prefix is the sub-directory within app
        $prefix = $this->option('dir');

        $ignoreTable = $this->option("ignore");

        if ($this->option("ignoresystem")) {
            $ignoreSystem = "users,permissions,permission_role,roles,role_user,migrations,password_resets";

            if (is_string($ignoreTable)) {
                $ignoreTable .= "," . $ignoreSystem;
            } else {
                $ignoreTable = $ignoreSystem;
            }
        }

        // if we have ignore tables, we need to find all the posibilites
        if (is_string($ignoreTable) && preg_match("/^" . $table . "|^" . $table . ",|," . $table . ",|," . $table . "$/", $ignoreTable)) {
            $this->info($table . " is ignored");

            return;
        }

        // replace table prefix
        $tablePrefix = $this->option('prefix') ?: \DB::getTablePrefix();
        $prefixRemovedTableName = str_replace($tablePrefix, '', $table);

        $class = VariableConversion::convertTableNameToClassName($prefixRemovedTableName);
        
        if (method_exists($this, 'qualifyClass')) {
            $name = Pluralizer::singular($this->qualifyClass($prefix . $class));
        } else {
            $name = Pluralizer::singular($this->parseName($prefix . $class));
        }

        if ($this->files->exists($path = $this->getPath($name)) && !$this->option('force')) {
            return $this->error($this->extends . ' for ' . $table . ' already exists!');
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->replaceTokens($name, $table));

        $this->info($this->extends . ' for ' . $table . ' created successfully.');
    }

    /**
     * Replace all stub tokens with properties.
     *
     * @param $name
     * @param $table
     *
     * @return mixed|string
     */
    protected function replaceTokens($name, $table)
    {
        $class = $this->buildClass($name);

        $properties = $this->getTableProperties($table);

        $extends = $this->option('extends');

        $class = str_replace('{{table}}', 'protected $table = \'' . $table . '\';', $class);

        $class = str_replace('{{primaryKey}}', $properties['primaryKey'] ? ('protected $primaryKey = \'' . $properties['primaryKey'] . '\';' . "\r\n\r\n\t") : '', $class);

        $class = str_replace('{{extends}}', $extends, $class);
        $class = str_replace('{{shortNameExtends}}', explode('\\', $extends)[count(explode('\\', $extends)) - 1], $class);
        $class = str_replace('{{fillable}}', 'protected $fillable = ' . VariableConversion::convertArrayToString($properties['fillable']) . ';', $class);
        $class = str_replace('{{guarded}}', 'protected $guarded = ' . VariableConversion::convertArrayToString($properties['guarded']) . ';', $class);
        $class = str_replace('{{timestamps}}', 'public $timestamps = ' . VariableConversion::convertBooleanToString($properties['timestamps']) . ';', $class);

        if ($this->option("getset")) {
            $class = $this->replaceTokensWithSetGetFunctions($properties, $class);
        } else {
            $class = str_replace(["{{setters}}\n\n", "{{getters}}\n\n"], '', $class);
        }

        return $class;
    }

    /**
     * Replaces setters and getters from the stub. The functions are created
     * from provider properties.
     *
     * @param  array  $properties
     * @param  string $class
     * @return string
     */
    protected function replaceTokensWithSetGetFunctions($properties, $class)
    {
        $getters = "";
        $setters = "";

        $fillableGetSet = new SetGetGenerator($properties['fillable'], $this->getFunctionStub, $this->setFunctionStub);
        $getters .= $fillableGetSet->generateGetFunctions();
        $setters .= $fillableGetSet->generateSetFunctions();

        $guardedGetSet = new SetGetGenerator($properties['guarded'], $this->getFunctionStub, $this->setFunctionStub);
        $getters .= $guardedGetSet->generateGetFunctions();

        return str_replace([
            "{{setters}}",
            "{{getters}}"
        ], [
            $setters,
            $getters
        ], $class);
    }

    /**
     * Fill up $fillable/$guarded/$timestamps properties based on table columns.
     *
     * @param $table
     *
     * @return array
     */
    protected function getTableProperties($table)
    {
        $primaryKey = $this->getTablePrimaryKey($table);
        $primaryKey = $primaryKey != 'id' ? $primaryKey : null;

        $fillable = [];
        $guarded = [];
        $timestamps = false;

        $columns = $this->getTableColumns($table);

        foreach ($columns as $column) {
            $column = (object) $column;

            //priotitze guarded properties and move to fillable
            if ($this->ruleProcessor->check($this->option('fillable'), $column->name)) {
                if (!in_array($column->name, array_merge(['id', 'created_at', 'updated_at', 'deleted_at'], $primaryKey ? [$primaryKey] : []))) {
                    $fillable[] = $column->name;
                }
            }
            if ($this->ruleProcessor->check($this->option('guarded'), $column->name)) {
                $fillable[] = $column->name;
            }
            //check if this model is timestampable
            if ($this->ruleProcessor->check($this->option('timestamps'), $column->name)) {
                $timestamps = true;
            }
        }

        return ['primaryKey' => $primaryKey, 'fillable' => $fillable, 'guarded' => $guarded, 'timestamps' => $timestamps];
    }

    /**
     * Get table columns.
     *
     * @param $table
     *
     * @return array
     */
    protected function getTableColumns($table)
    {
        switch ($this->databaseEngine) {

            case 'mysql':
                $columns = \DB::select("SELECT COLUMN_NAME as `name` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env("DB_DATABASE") . "' AND TABLE_NAME = '{$table}'");
                break;

            case 'sqlsrv':
            case 'dblib':
                $columns = \DB::select("SELECT COLUMN_NAME as 'name' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG = '" . env("DB_DATABASE") . "' AND TABLE_NAME = '{$table}'");
                break;

            case 'pgsql':
                $columns = \DB::select("SELECT COLUMN_NAME as name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG = '" . env("DB_DATABASE") . "' AND TABLE_NAME = '{$table}'");
                break;
        }

        return $columns;
    }

    /**
     * Get table primary key column.
     *
     * @param $table
     *
     * @return string
     */
    protected function getTablePrimaryKey($table)
    {

        switch ($this->databaseEngine) {
            case 'mysql':
                $primaryKeyResult = \DB::select(
                    "SELECT COLUMN_NAME
                  FROM information_schema.COLUMNS 
                  WHERE  TABLE_SCHEMA = '" . env("DB_DATABASE") . "' AND 
                         TABLE_NAME = '{$table}' AND 
                         COLUMN_KEY = 'PRI'");
                break;

            case 'sqlsrv':
            case 'dblib':
                $primaryKeyResult = \DB::select(
                    "SELECT ku.COLUMN_NAME
                   FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc
                   INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS ku
                   ON tc.CONSTRAINT_TYPE = 'PRIMARY KEY' 
                   AND tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME
                   WHERE ku.TABLE_CATALOG ='" . env("DB_DATABASE") . "' AND ku.TABLE_NAME='{$table}';");
                break;

            case 'pgsql':
                $primaryKeyResult = \DB::select(
                    "SELECT ku.COLUMN_NAME AS \"COLUMN_NAME\"
                   FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc
                   INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS ku
                   ON tc.CONSTRAINT_TYPE = 'PRIMARY KEY' 
                   AND tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME
                   WHERE ku.TABLE_CATALOG ='" . env("DB_DATABASE") . "' AND ku.TABLE_NAME='{$table}';");
                break;

        }

        if (count($primaryKeyResult) == 1) {
            $table = (object) $primaryKeyResult[0];
            return $table->COLUMN_NAME;
        }

        return null;
    }

    /**
     * Get stub file location.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__ . '/../stubs/model.stub';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['tables', null, InputOption::VALUE_OPTIONAL, 'Comma separated table names to generate', null],
            ['prefix', null, InputOption::VALUE_OPTIONAL, 'Table prefix', null],
            ['dir', null, InputOption::VALUE_OPTIONAL, 'Model directory', $this->namespace],
            ['extends', null, InputOption::VALUE_OPTIONAL, 'Parent class', $this->extends],
            ['fillable', null, InputOption::VALUE_OPTIONAL, 'Rules for $fillable array columns', $this->fillableRules],
            ['guarded', null, InputOption::VALUE_OPTIONAL, 'Rules for $guarded array columns', $this->guardedRules],
            ['timestamps', null, InputOption::VALUE_OPTIONAL, 'Rules for $timestamps columns', $this->timestampRules],
            ['ignore', "i", InputOption::VALUE_OPTIONAL, 'Ignores the tables you define, separated with ,', null],
            ['force', "f", InputOption::VALUE_OPTIONAL, 'Force override', false],
            ['ignoresystem', "s", InputOption::VALUE_NONE, 'If you want to ignore system tables.
            Just type --ignoresystem or -s'],
            ['getset', 'm', InputOption::VALUE_NONE, 'Defines if you want to generate set and get methods']
        ];
    }
}
