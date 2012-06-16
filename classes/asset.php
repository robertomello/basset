<?php namespace Basset;

use File;
use Bundle;

class Asset {

	/**
	 * Array containing the data related to the asset.
	 * 
	 * @var array
	 */
	protected $data;

	/**
	 * Create a new Basset\Asset instance.
	 *
	 * @param  string  $name
	 * @param  string  $file
	 * @param  string  $group
	 * @param  string  $extension
	 * @param  string  $directory
	 * @param  array   $dependencies
	 * @return void
	 */
	public function __construct($name, $file, $dependencies)
	{
		$this->name = $name;

		$this->file = $file;

		$this->dependencies = (array) $dependencies;

		$this->bundle = false;

		$this->external = false;

		$this->updated = 0;
	}

	public function exists($directory)
	{
		if(!parse_url($this->file, PHP_URL_SCHEME))
		{

			// If a directory is defined we'll use what the user has set. If the asset is
			// prefixed with a bundle identifier then we'll use that. In the end we'll just
			// use the normal public path.
			if(!is_null($directory))
			{
				$this->directory = path('base') . $directory;
			}
			elseif(str_contains($this->file, '::'))
			{
				list($bundle, $file) = explode('::', $this->file);

				$this->directory = path('public') . Bundle::assets($bundle);

				$this->bundle = true;
			}
			else
			{
				$this->directory = path('public');
			}

			// If the directory that was provided initially is empty and no directory separator
			// is in the file name we'll default to the CSS or JS directory within the public
			// directory.
			if(is_null($directory) and !str_contains($this->file, '/'))
			{
				$this->directory .= File::extension($this->file);
			}

			$this->directory = realpath($this->directory);

			if(!file_exists($this->directory . DS . $this->file))
			{
				return false;
			}
		}
		else
		{
			$this->external = true;
		}

		return true;
	}

	/**
	 * If the asset is external or internal.
	 * 
	 * @return bool
	 */
	public function external()
	{
		return $this->external;
	}

	/**
	 * get
	 *
	 * Gets the contents of an asset and if it's a stylesheet it is run through the
	 * URI rewriter to correct any ill-formed directories.
	 *
	 * @param  array   $symlinks
	 * @param  string  $document_root
	 * @return string
	 */
	public function get($symlinks = array(), $document_root = '')
	{
		$failed = PHP_EOL . '/* Basset could not find asset [' . $this->directory . DS . $this->file . '] */' . PHP_EOL;

		$contents = @file_get_contents($this->directory . DS . $this->file);

		if(empty($contents))
		{
			return $failed;
		}

		if($this->is('styles'))
		{
			$contents = Vendor\URIRewriter::rewrite($contents, dirname($this->directory .DS . $this->file), $document_root, $symlinks);
		}

		if($this->is('less') && Config::get('less.php'))
		{
			$less = new Vendor\lessc;
			$less->importDir = $this->directory;

			$contents = $less->parse($contents);
		}

		return $contents . PHP_EOL;
	}

	/**
	 * Checks if the asset is part of a group based on its extension.
	 * 
	 * @param  string  $group
	 * @return void
	 */
	public function is($group)
	{
		$extensions = array(
			'css'  => 'styles',
			'less' => 'styles',
			'js'   => 'scripts'
		);

		return in_array(File::extension($this->file), $extensions);
	}

	/**
	 * Magic setter for asset data.
	 * 
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Magic getter for asset data.
	 * 
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->data[$key];
	}

}