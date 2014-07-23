<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DropTables extends Command {

  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = 'db:dropTables';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Drops every table in the db';

  protected $_manager = null;

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
   
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function fire()
  {
    $dbname = Config::get('database.connections.mysql.database',false);
    if(!$dbname)
    {
      throw new Exception("Error Locating database name", 1);    
    }

    $var_name = "Tables_in_".$dbname;

    DB::statement('SET FOREIGN_KEY_CHECKS = 0');
    //Schema::drop('throttle');
    $result = DB::select(DB::raw('SHOW TABLES'));
    foreach($result as $table)
    {
      if(isset($table->$var_name))
      {
        $tablename = $table->$var_name;
        DB::statement("drop table $tablename");
      }
      
    }

    DB::statement('SET FOREIGN_KEY_CHECKS = 1');


  }

  /**
   * Get the console command arguments.
   *
   * @return array
   */
  protected function getArguments()
  {
    return array(
    //  array('example', InputArgument::REQUIRED, 'An example argument.'),
    );
  }

  /**
   * Get the console command options.
   *
   * @return array
   */
  protected function getOptions()
  {
    return array(
      array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    );
  }

}
