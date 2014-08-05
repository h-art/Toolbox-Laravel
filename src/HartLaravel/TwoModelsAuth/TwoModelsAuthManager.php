<?php
namespace Hart\TwoModelsAuth;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;

use Hart\TwoModelsAuth\TwoModelsGuard;

class TwoModelsAuthManager extends AuthManager 
{
		
	public function createEloquentDriver()
	{
		$provider = $this->createEloquentProvider();

		return new TwoModelsGuard($provider, $this->app['session.store']);
	}

	/**
	 * Create an instance of the Eloquent user provider.
	 *
	 * @return \Illuminate\Auth\EloquentUserProvider
	 */
	protected function createEloquentProvider()
	{
		$model = $this->app['config']['auth.model'];

		// se non sono in admin, faccio riferimento agli utenti normali
		if(false === strpos($_SERVER['REQUEST_URI'], 'admin'))
		{
			$model = $model = $this->app['config']['auth.model'];
		}
		else
		{
			$model = $this->app['config']['auth.model_admin'];
		}

		return new EloquentUserProvider($this->app['hash'], $model);
	}

}