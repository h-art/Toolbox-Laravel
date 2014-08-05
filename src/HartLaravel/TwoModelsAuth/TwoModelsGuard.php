<?php
namespace Hart\TwoModelsAuth;

use Illuminate\Auth\Guard;
class TwoModelsGuard extends Guard
{
	/**
	 * Determine if the current user is authenticated.
	 *
	 * @return bool
	 */
	public function check()
	{

		if(!is_null($this->user()))
		{
			$user = $this->user();			
			$class = get_class($user);	
			//die(var_dump($class,$this->getExpectedModel(),$user));
			
			return $class === $this->getExpectedModel();
		}
		else
		{
			return false;
		}
		
	}


	/**
	 * Get a unique identifier for the auth session value.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'login_'.md5(get_class($this).$this->getExpectedModel());
	}

	/**
	 * Get the name of the cookie used to store the "recaller".
	 *
	 * @return string
	 */
	public function getRecallerName()
	{
		return 'remember_'.md5(get_class($this).$this->getExpectedModel());
	}


	protected function adminArea()
	{
		$admin_area_identifier = \Config::get('auth.admin_area_identifier');
		if(!$admin_area_identifier)
		{
			throw new Exception("missing auth.admin_area_identifier parameter!", 1);			
		}

		return (false !== strpos($this->request->url(),$admin_area_identifier));		
	}

	protected function getModel()
	{
		return \Config::get('auth.model');
	}

	protected function getModelAdmin()
	{
		$model_admin = \Config::get('auth.model_admin',false);
		if(!$model_admin)
		{
			throw new Exception("Missing auth.model_admin parameter!", 1);
		}

		return $model_admin;
	}

	protected function getExpectedModel()
	{
		if($this->adminArea())
		{
			return $this->getModelAdmin();
		}
		else
		{
			return $this->getModel();
		}
	}
}