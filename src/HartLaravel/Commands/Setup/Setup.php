<?php

/**
 * @author Camarda Camillo
 * 
 */
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Setup extends Command 
{
	protected 	$_virtual_hosts_path = false,
				$_hosts_path = false,
				$_default_domain = false,
				$_working_dir = null,
				$_project_name = false,
				$_replacement_strings = array();

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'project:setup';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();		
		$this->_working_dir = exec("pwd");
		$this->_default_domain = Config::get('setup.default_domain',false);
		$this->_project_name = Config::get('setup.project_name',false);
		$this->_virtual_hosts_path	= Config::get('setup.default_vhost_path',false);
		$this->_hosts_path	= Config::get('setup.default_hosts_path',false);
	}


	protected function askForVhostEditing()
	{
		$edit_vhost = true;
		if ($edit_vhost = $this->confirm('Do you wish the setup to edit your virtual host? [yes|no] (yes)',true))
		{
		    $this->_virtual_hosts_path = $this->ask("Virtual hosts path ? ({$this->_virtual_hosts_path})",$this->_virtual_hosts_path);
		    if(!is_readable($this->_virtual_hosts_path))
			{
				throw new Exception($this->_virtual_hosts_path." is not readable");			
			}
		}

		return $edit_vhost;
	}

	protected function askForHostEditing()
	{
		$edit_hosts = true;

		if($edit_hosts = $this->confirm('Do you wish the setup to edit your host file? [yes|no] (yes)',true))
		{
			$this->_hosts_path = $this->ask("Hosts file path ? ({$this->_hosts_path})",$this->_hosts_path);	
			if(!is_readable($this->_hosts_path))
			{
				throw new Exception($this->_hosts_path." is not readable");			
			}
		}

		return $edit_hosts;
	}



	protected function handleDBFile($path)
	{
		


		$database_name = false;
		while(!$database_name)
		{
			$database_name = $this->ask("DB Name?",false);
		}

		$default_user = Config::get('setup.default_db_user',"root");
		$database_user = $this->ask("DB User? ($default_user)",$default_user);

		$default_password = Config::get('setup.default_db_password',"root");
		$database_password = $this->ask("DB Password? ($default_password)",$default_password);

		$dbfile = $this->_working_dir."/data/default_local_database.php";
		$dbfile_contents = file_get_contents($dbfile);

		$dbfile_contents = str_replace(
			array("%database_name%","%database_user%","%database_password%"), 
			array($database_name,$database_user,$database_password), 
			$dbfile_contents
		);

		$status = file_put_contents($path,$dbfile_contents);
		if(false === $status)
		{
			throw new Exception("Error while writing db file to $path", 1);
		}


	}


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$this->info("## Project {$this->_project_name} SETUP ##");

		/* VHOST and HOST FILES HANDLING*/
		$edit_vhost = $this->askForVhostEditing();
		$edit_hosts = $this->askForHostEditing();

		if($edit_vhost || $edit_hosts)
		{
			$this->_project_name = $this->ask("Project name? ({$this->_project_name})",$this->_project_name);
			$this->_default_domain = $this->ask("Default domain? ({$this->_default_domain})",$this->_default_domain);
			$this->_working_dir = $this->ask("Working directory ? ({$this->_working_dir})",$this->_working_dir);
			$this->setupReplacementStrings();
				
			if($edit_vhost)
			{
				$this->handleVirtualHostsFile();	
			}

			if($edit_hosts)
			{
				$this->handleHostsFile();	
			}
		}



		/* DB HANDLING */
		$db_local_config_path = $this->_working_dir."/app/config/local/database.php";
		if(is_readable($db_local_config_path))
		{
			$this->info("database.php already found. skipping db file creation");
			return;
		}
		else
		{
			if($this->confirm("Do you want to create your local database.php file? [yes|no] (yes)",true))
			{
				$this->handleDBFile($db_local_config_path);
			}
		}

		$this->seedDB();
		$this->info("Setup completed, please restart apache now");		
	}

	protected function seedDB()
	{
		$this->info("Migrating DB");
		//in teoria si puo fare da artisan ma non ci sono riuscito		
		exec("php artisan migrate --env=local");
		$this->info("Seeding DB");
		exec("php artisan db:seed --env=local");
		$this->info("Done");
	}

	protected function setupReplacementStrings()
	{
		$this->_replacement_strings = array(
			"%working_directory%" 	=> $this->_working_dir,
			"%default_domain%"		=> $this->_default_domain,
			"%project_name%"		=> $this->_project_name,
			"%datetime%"			=> date('Y-m-d H:i:s'),
		);

	}

	protected function readDefaultVirtualHostFile()
	{
		$default_virtual_host_path = $this->_working_dir."/data/default_virtual_host";
		$default_virtual_host_contents = file_get_contents($default_virtual_host_path);

		if(FALSE === $default_virtual_host_contents)
		{
			throw new Exception("Cannot read/find default virtual host configuration (looking in: {$default_virtual_host_path})", 1);
		}

		return $default_virtual_host_contents;
	}

	protected function readVirtualHostsFile()
	{
		$virtual_hosts_contents = file_get_contents($this->_virtual_hosts_path);
		
		if(FALSE === $virtual_hosts_contents)
		{
			throw new Exception("Cannot read/find virtual hosts file (looking in: {$this->_virtual_hosts_path})", 1);
		}

		return $virtual_hosts_contents;
	}

	protected function handleVirtualHostsFile()
	{
		$default_virtual_host_string = $this->readDefaultVirtualHostFile();

		$default_virtual_host_string = str_replace(
			array_keys($this->_replacement_strings), 
			array_values($this->_replacement_strings), 
			$default_virtual_host_string
		);
		

		$virtual_hosts_contents = $this->readVirtualHostsFile();


		if(false !== strpos( $virtual_hosts_contents,$this->_default_domain))
		{
			$this->info("Virtual host file already contains {$this->_default_domain} entry. Returning");
			return;
		}

		$this->info("Appending new configuration to virtual hosts...");
		$command = "sudo sh -c \"echo '$default_virtual_host_string' >> {$this->_virtual_hosts_path}\"";
		exec($command);
		$this->info("Done");
	}

	protected function handleHostsFile()
	{
		$contents = file_get_contents($this->_hosts_path);
		if(false !== strpos($contents,$this->_default_domain))
		{
			$this->info("Hosts file already contains {$this->_default_domain} entry. Returning");
			return;
		}	

		$this->info("Appending new configuration to hosts file ...");
		exec("sudo echo \"127.0.0.1\t{$this->_default_domain}\" >> ".$this->_hosts_path);
		$this->info("Done");
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			
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
