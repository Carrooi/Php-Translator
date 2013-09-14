<?php

namespace DK\Translator;

/**
 *
 * @author David Kudera
 */
class Translator
{


	/** @var string */
	private $directory;

	/** @var string */
	private $language;

	/** @var array */
	private $plurals = array();

	/** @var array  */
	private $replacements = array();

	/** @var array  */
	private $data = array();


	/**
	 * @param string $directory
	 */
	public function __construct($directory)
	{
		$this->setDirectory($directory);

		$plurals = json_decode(file_get_contents(__DIR__. '/pluralForms.json'), true);
		foreach ($plurals as $language => $data) {
			$this->addPluralForm($language, $data['count'], $data['form']);
		}
	}


	/**
	 * @return $this
	 */
	public function invalidate()
	{
		$this->data = array();
		return $this;
	}


	/**
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->directory;
	}


	/**
	 * @param string $directory
	 * @return \DK\Translator\Translator
	 * @throws \Exception
	 */
	public function setDirectory($directory)
	{
		if (!is_dir($directory)) {
			throw new \Exception("Directory '$directory'' does not exists.");
		}

		$this->directory = $directory;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}


	/**
	 * @param string $language
	 * @return \DK\Translator\Translator
	 */
	public function setLanguage($language)
	{
		$this->language = $language;
		return $this;
	}


	/**
	 * @param string $language
	 * @param int $count
	 * @param string $form
	 * @return \DK\Translator\Translator
	 */
	public function addPluralForm($language, $count, $form)
	{
		$this->plurals[$language] = array(
			'count' => $count,
			'form' => $form
		);
		return $this;
	}


	/**
	 * @param string $search
	 * @param string $replacement
	 * @return \DK\Translator\Translator
	 */
	public function addReplacement($search, $replacement)
	{
		$this->replacements[$search] = $replacement;
		return $this;
	}


	/**
	 * @param string $search
	 * @return \DK\Translator\Translator
	 * @throws \Exception
	 */
	public function removeReplacement($search)
	{
		if (!isset($this->replacements[$search])) {
			throw new \Exception("Replacement '$search' was not found.");
		}

		unset($this->replacements[$search]);
		return $this;
	}


	/**
	 * @param string $path
	 * @param string $name
	 * @return array
	 */
	private function loadCategory($path, $name)
	{
		$categoryName = $path. '/'. $name;
		if (!isset($this->data[$categoryName])) {
			$name = $path. '/'. $this->language. '.'. $name. '.json';
			$path = $this->getDirectory(). '/'. $name;
			$this->data[$categoryName] = $this->load($path, $categoryName);
		}

		return $this->data[$categoryName];
	}


	/**
	 * @param string $path
	 * @param string $categoryName
	 * @return array
	 */
	private function load($path, $categoryName)
	{
		$data = $this->loadFromFile($path);
		return $this->normalizeTranslations($data);
	}


	/**
	 * @param string $path
	 * @return array
	 */
	private function loadFromFile($path)
	{
		if (is_file($path)) {
			$data = json_decode(file_get_contents($path), true);
		} else {
			$data = array();
		}

		return $data;
	}


	/**
	 * @param array $translations
	 * @return array
	 */
	private function normalizeTranslations($translations)
	{
		$result = array();
		foreach ($translations as $name => $translation) {
			$list = false;
			if (preg_match('~^--\s(.*)~', $name, $match)) {
				$name = $match[1];
				$list = true;
			}
			if (is_string($translation)) {
				$result[$name] = [$translation];
			} elseif (is_array($translation)) {
				$result[$name] = array();
				foreach ($translation as $t) {
					if (is_array($t)) {
						$result[$name][] = $t;
					} else {
						if ($list === true && !is_array($t)) {
							$t = array($t);
							$result[$name][] = $t;
						}
					}
				}


				$result[$name] = $translation;
			}
		}
		return $result;
	}


	/**
	 * @param string $message
	 * @return array|null
	 */
	private function findTranslation($message)
	{
		$info = $this->getMessageInfo($message);
		$data = $this->loadCategory($info['path'], $info['category']);
		return isset($data[$info['name']]) ? $data[$info['name']] : null;
	}


	/**
	 * @param string $message
	 * @param int|null $count
	 * @param array $args
	 * @return array|string
	 * @throws \Exception
	 */
	public function translate($message, $count = null, array $args = array())
	{
		if ($this->language === null) {
			throw new \Exception('You have to set language.');
		}

		if (!is_string($message)) {
			return $message;
		}

		if ($count !== null) {
			$args['count'] = $count;
		}

		if (preg_match('~^\:(.*)\:$~', $message, $match) !== 0) {
			$message = $message[1];
		} else {
			$message = $this->applyReplacements($message, $args);
			$translation = $this->findTranslation($message);

			if ($translation !== null) {
				$message = $this->pluralize($message, $translation, $count);
			}
		}

		$message = $this->prepareTranslation($message, $args);

		return $message;
	}


	/**
	 * @param string $message
	 * @param string $key
	 * @param string $value
	 * @param int|null $count
	 * @param array $args
	 * @return array
	 * @throws \Exception
	 */
	public function translatePairs($message, $key, $value, $count = null, array $args = array())
	{
		$key = "$message.$key";
		$value = "$message.$value";

		$key = $this->translate($key, $count, $args);
		$value = $this->translate($value, $count, $args);

		if (!is_array($key) || !is_array($value)) {
			throw new \Exception('Translations are not arrays.');
		}

		if (count($key) !== count($value)) {
			throw new \Exception('Keys and values translations have not got the same length.');
		}

		return array_combine($key, $value);
	}


	/**
	 * @param string $message
	 * @param array $translation
	 * @param int|null $count
	 * @return array|string
	 */
	private function pluralize($message, array $translation, $count = null)
	{
		if ($count !== null) {
			if (is_string($translation[0])) {
				$pluralForm = 'n='. $count. ';plural=+('. $this->plurals[$this->language]['form']. ');';
				$pluralForm = preg_replace('/([a-z]+)/', '$$1', $pluralForm);

				$n = null;
				$plural = null;

				eval($pluralForm);

				$message = $plural !== null && isset($translation[$plural]) ? $translation[$plural] : $translation[0];
			} else {
				$result = array();
				foreach ($translation as $t) {
					$result[] = $this->pluralize($message, $t, $count);
				}
				$message = $result;
			}
		} else {
			if (is_string($translation[0])) {
				$message = $translation[0];
			} else {
				$message = array();
				foreach ($translation as $t) {
					$message[] = $t[0];
				}
			}
		}

		return $message;
	}


	/**
	 * @param string|array $message
	 * @param array $args
	 * @return array|string
	 */
	private function prepareTranslation($message, array $args = array())
	{
		if (is_string($message)) {
			$message = $this->applyReplacements($message, $args);
		} else {
			$result = array();
			foreach ($message as $m) {
				$result[] = $this->prepareTranslation($m, $args);
			}
			$message = $result;
		}

		return $message;
	}


	/**
	 * @param string $message
	 * @param array $args
	 * @return string
	 */
	private function applyReplacements($message, array $args = array())
	{
		$replacements = $this->replacements;

		foreach ($args as $name => $value) {
			$replacements[$name] = $value;
		}

		foreach ($replacements as $name => $value) {
			if ($value !== false) {
				$message = preg_replace('~%'. $name. '%~', $value, $message);
			}
		}

		return $message;
	}


	/**
	 * @param string $message
	 * @return array
	 */
	private function getMessageInfo($message)
	{
		$num = strrpos($message, '.');
		$path = substr($message, 0, $num);
		$name = substr($message, $num + 1);
		$num = strrpos($path, '.');
		$category = substr($path, $num + 1);
		$path = substr($path, 0, $num);
		$path = preg_replace('~\.~', '/', $path);

		return array(
			'path' => $path,
			'category' => $category,
			'name' => $name
		);
	}

}