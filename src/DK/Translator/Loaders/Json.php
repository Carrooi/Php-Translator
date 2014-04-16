<?php

namespace DK\Translator\Loaders;

/**
 *
 * @author David Kudera
 */
class Json implements Loader
{


	/** @var string  */
	private $directory;


	/**
	 * @param string $directory
	 */
	public function __construct($directory)
	{
		$this->directory = $directory;
	}


	/**
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->directory;
	}


	/**
	 * @param string $parent
	 * @param string $name
	 * @param string $language
	 * @return array
	 */
	public function load($parent, $name, $language)
	{
		$path = $this->getFileSystemPath($parent, $name, $language);
		if (is_file($path)) {
			return json_decode(file_get_contents($path));
		} else {
			return array();
		}
	}


	/**
	 * @param string $parent
	 * @param string $name
	 * @param string $language
	 * @param array $data
	 */
	public function save($parent, $name, $language, $data)
	{
		$options = PHP_VERSION_ID >= 504000 ? JSON_PRETTY_PRINT : 0;
		$path = $this->getFileSystemPath($parent, $name, $language);

		file_put_contents($path, json_encode($data, $options));
	}


	/**
	 * @param string $parent
	 * @param string $name
	 * @param string $language
	 * @return string
	 */
	public function getFileSystemPath($parent, $name, $language)
	{
		return $this->directory. ($parent !== '' ? '/'. $parent : ''). "/$language.$name.json";
	}

} 