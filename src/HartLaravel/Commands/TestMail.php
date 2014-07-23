<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TestMail extends Command {

  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = 'utilities:TestMail';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sends a mail to test the mailing configuration';

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
    $from = $this->argument('from');
    $to = $this->argument('to');

    Mail::send('emails.test', array(), function($message)  use ($from,$to)
    {
      $message->from($from ,"Sender name");

      $message->to($to)
      ->subject('Email test');

      Log::info("Sending test email from {$from} to {$to}");      
    });
    echo "OK";

  }

  /**
   * Get the console command arguments.
   *
   * @return array
   */
  protected function getArguments()
  {
    return array(
      array('from', InputArgument::REQUIRED, 'Email from address'),
      array('to', InputArgument::REQUIRED, 'Email destination address'),
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
      //array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    );
  }

}
