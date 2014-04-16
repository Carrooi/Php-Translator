<?php

namespace DK\Translator\Loaders;

/**
 *
 * @author David Kudera
 */
interface Loader
{


	/**
	 * @param string $parent
	 * @param string $name
	 * @param string $language
	 * @return array
	 */
	public function load($parent, $name, $language);


	/**
	 * @param string $parent
	 * @param string $name
	 * @param string $language
	 * @param array $data
	 * @return void
	 */
	public function save($parent, $name, $language, $data);


	/**
	 * @param string $parent
	 * @param string $name
	 * @param string $language
	 * @return string
	 */
	public function getFileSystemPath($parent, $name, $language);

}