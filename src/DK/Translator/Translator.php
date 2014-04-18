<?php

namespace DK\Translator;

use DK\Translator\Loaders\Loader;
use Tester\Dumper;

/**
 *
 * @author David Kudera
 */
class Translator
{


	/** @var \DK\Translator\Loaders\Loader */
	private $loader;

	/** @var string */
	private $language;

	/** @var array */
	private $plurals = array();

	/** @var array  */
	private $replacements = array();

	/** @var array  */
	private $filters = array();

	/** @var array  */
	private $helpers = array();

	/** @var array  */
	private $data = array();

	/** @var array  */
	private $translated = array();

	/** @var array  */
	private $untranslated = array();

	/** @var string|bool */
	private $lastTranslated = null;


	/**
	 * @param string|\DK\Translator\Loaders\Loader $pathOrLoader
	 * @throws \Exception
	 */
	public function __construct($pathOrLoader)
	{
		if (!is_string($pathOrLoader) && !$pathOrLoader instanceof Loader) {
			throw new \Exception('Argument passed to translator must be string or Loader.');
		}

		if (is_string($pathOrLoader)) {
			$config = array(
				'path' => $pathOrLoader,
				'loader' => 'Json'
			);

			if (preg_match('/\.json$/', $pathOrLoader)) {
				$_config = json_decode(file_get_contents($pathOrLoader));
				if (isset($_config->path)) {
					$config['path'] = $_config->path;
				}
				if (isset($_config->loader)) {
					$config['loader'] = $_config->loader;
				}
				if ($config['path'][0] === '.') {
					$config['path'] = $this->joinPaths(dirname($pathOrLoader), $config['path']);
				}
			}

			$loader = "\\DK\\Translator\\Loaders\\$config[loader]";
			$pathOrLoader = new $loader($config['path']);
		}

		$this->setLoader($pathOrLoader);

		$plurals = json_decode(file_get_contents(__DIR__. '/pluralForms.json'), true);
		foreach ($plurals as $language => $data) {
			$this->addPluralForm($language, $data['count'], $data['form']);
		}
	}


	/**
	 * @return array
	 */
	public function getTranslated()
	{
		return $this->translated;
	}


	/**
	 * @return array
	 */
	public function getUntranslated()
	{
		return $this->untranslated;
	}


	/**
	 * @return bool|string
	 */
	public function getLastTranslated()
	{
		return $this->lastTranslated;
	}


	/**
	 * @param string $left
	 * @param string $right
	 * @return string
	 */
	private function joinPaths($left, $right)
	{
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '') {
				$paths[] = $arg;
			}
		}
		$path = preg_replace('#/+#','/',join('/', $paths));
		return realpath($path);
	}


	/**
	 * @return \DK\Translator\Loaders\Loader
	 */
	public function getLoader()
	{
		return $this->loader;
	}


	/**
	 * @param \DK\Translator\Loaders\Loader $loader
	 * @return \DK\Translator\Translator
	 */
	public function setLoader(Loader $loader)
	{
		$this->loader = $loader;
		return $this;
	}


	/**
	 * @return \DK\Translator\Translator
	 */
	public function invalidate()
	{
		$this->data = array();
		return $this;
	}


	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
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
	 * @return array
	 */
	public function getPluralForms()
	{
		return $this->plurals;
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
	 * @return array
	 */
	public function getReplacements()
	{
		return $this->replacements;
	}


	/**
	 * @param callable $fn
	 * @return \DK\Translator\Translator
	 */
	public function addFilter($fn)
	{
		$this->filters[] = $fn;
		return $this;
	}


	/**
	 * @param string $name
	 * @param callable $fn
	 * @return \DK\Translator\Translator
	 */
	public function addHelper($name, $fn)
	{
		$this->helpers[$name] = $fn;
		return $this;
	}


	/**
	 * @private public because of php 5.3
	 * @param string|array $translation
	 * @return array
	 */
	public function _applyFilters($translation)
	{
		if (is_array($translation)) {
			$_this = $this;
			return array_map(function($t) use ($_this) {
				return $_this->_applyFilters($t);
			}, $translation);
		}

		foreach ($this->filters as $filter) {
			$translation = $filter($translation);
		}

		return $translation;
	}


	/**
	 * @param $translation
	 * @param array $helpers
	 * @return mixed
	 * @throws \Exception
	 */
	public function applyHelpers($translation, array $helpers)
	{
		if (is_array($translation)) {
			$_this = $this;
			return array_map(function($t) use ($_this, $helpers) {
				return $_this->applyHelpers($t, $helpers);
			}, $translation);
		}

		foreach ($helpers as $helper) {
			if (!isset($this->helpers[$helper['name']])) {
				throw new \Exception('Helper '. $helper['name']. ' is not registered.');
			}

			array_unshift($helper['arguments'], $translation);

			$translation = call_user_func_array($this->helpers[$helper['name']], $helper['arguments']);
		}

		return $translation;
	}


	/**
	 * @param string $path
	 * @param string $name
	 * @param string|null $language
	 * @return array
	 */
	public function _loadCategory($path, $name, $language = null)
	{
		if ($language === null) {
			$language = $this->getLanguage();
		}

		$categoryName = $path. '/'. $name;
		if (!isset($this->data[$categoryName])) {
			$data = $this->loader->load($path, $name, $language);
			$data = $this->_normalizeTranslations($data);

			$this->data[$categoryName] = $data;
		}

		return $this->data[$categoryName];
	}


	/**
	 * @param array $translations
	 * @return array
	 */
	private function _normalizeTranslations($translations)
	{
		$result = array();
		foreach ($translations as $name => $translation) {
			$list = false;
			if (preg_match('~^--\s(.*)~', $name, $match)) {
				$name = $match[1];
				$list = true;
			}

			if (is_string($translation)) {
				$result[$name] = array($translation);
			} elseif (is_array($translation)) {
				$result[$name] = array();
				foreach ($translation as $t) {
					if (is_array($t)) {
						$buf = array();
						foreach ($t as $sub) {
							if (!preg_match('~^\#.*\#$~', $sub)) {
								$buf[] = $sub;
							}
						}
						$result[$name][] = $buf;
					} else {
						if (!preg_match('~^\#.*\#$~', $t)) {
							if ($list === true && !is_array($t)) {
								$t = array($t);
							}
							$result[$name][] = $t;
						}
					}
				}
			}
		}

		return $result;
	}


	/**
	 * @param string $message
	 * @return array
	 */
	public function normalizeMessage($message)
	{
		$ignored = false;
		$language = null;
		$num = null;
		$helpers = array();

		// :do.not.translate.me:
		if (preg_match('/^\:(.*)\:$/', $message, $match)) {
			$message = $match[1];
			$ignored = true;
		}

		// cs|overridden.language
		if (preg_match('/^([a-zA-Z-]+)\|(.*)$/', $message, $match)) {
			$message = $match[2];
			$language = $match[1];
		}

		// accessing.list.item[5]
		if (preg_match('/(.+)\[(\d+)\](|.*)?$/', $message, $match)) {
			$message = $match[1]. $match[3];		// append helpers
			$num = (int) $match[2];
		}

		// parse.helpers|truncate:5|firstUpper
		if (preg_match('/^(.*?)\|(.*)$/', $message, $match)) {
			$message = $match[1];
			$helpers = explode('|', $match[2]);

			$helpers = array_map(function($helper) {
				$name = $helper;
				$arguments = array();

				if (preg_match('/^(.*?)\:(.*)$/', $helper, $match)) {
					$name = $match[1];
					$arguments = explode(':', $match[2]);
				}

				return array(
					'name' => $name,
					'arguments' => $arguments,
				);
			}, $helpers);
		}

		return array(
			'ignored' => $ignored,
			'language' => $language,
			'message' => $message,
			'num' => $num,
			'helpers' => $helpers,
		);
	}


	/**
	 * @param string $message
	 * @param null|string $language
	 * @return bool
	 */
	public function hasTranslation($message, $language = null)
	{
		if ($language === null) {
			$language = $this->getLanguage();
		}

		return $this->findTranslation($message, $language) !== null;
	}


	/**
	 * @param string $message
	 * @param null|string $language
	 * @return array|null
	 */
	public function findTranslation($message, $language = null)
	{
		if ($language === null) {
			$language = $this->getLanguage();
		}

		$info = $this->getMessageInfo($message);
		$data = $this->_loadCategory($info['path'], $info['category'], $language);
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
		$this->lastTranslated = false;

		if (!is_string($message)) {
			return $message;
		}

		if (is_array($count)) {
			$args = $count;
			$count = null;
		}

		if ($count !== null) {
			$args['count'] = $count;
		}

		$found = false;

		$messageInfo = $this->normalizeMessage($message);

		$message = $messageInfo['message'];
		$num = $messageInfo['num'];
		$language = $messageInfo['language'] ?: $this->getLanguage();

		if ($language === null) {
			throw new \Exception('You have to set language.');
		}

		if ($messageInfo['ignored']) {
			$originalMessage = $messageInfo['message'];
		} else {
			$message = $originalMessage = $this->applyReplacements($message, $args);
			$translation = $this->findTranslation($message, $language);
			$found = $this->hasTranslation($message, $language);

			if ($num !== null) {
				if (!$this->isList($translation)) {
					throw new \Exception('Translation '. $message. ' is not a list.');
				}

				if (!isset($translation[$num])) {
					throw new \Exception('Item '. $num. ' was not found in '. $message. ' translation.');
				}

				$translation = $translation[$num];
			}

			if ($translation !== null) {
				$message = $this->pluralize($message, $translation, $count, $language);
			}
		}

		$message = $this->prepareTranslation($message, $args);

		if ($found) {
			$message = $this->_applyFilters($message);

			if (count($messageInfo['helpers']) > 0) {
				$message = $this->applyHelpers($message, $messageInfo['helpers']);
			}

			$this->lastTranslated = $originalMessage;

			if (!in_array($originalMessage, $this->translated)) {
				$this->translated[] = $originalMessage;
			}
		} elseif (!$messageInfo['ignored'] && !in_array($originalMessage, $this->untranslated)) {
			$this->untranslated[] = $originalMessage;
		}

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
	 * @param array $list
	 * @param int|null $count
	 * @param array $args
	 * @param string|null $base
	 * @return array
	 */
	public function translateMap(array $list, $count = null, array $args = null, $base = null)
	{
		if ($args === null) {
			$args = array();
		}

		$base = $base === null ? '' : $base. '.';

		$_this = $this;
		return array_map(function($a) use($_this, $count, $args, $base) {
			return $_this->translate($base. $a, $count, $args);
		}, $list);
	}


	/**
	 * @param array $translation
	 * @return bool
	 */
	private function isList($translation)
	{
		return is_array($translation[0]);
	}


	/**
	 * @param string $message
	 * @param array $translation
	 * @param int|null $count
	 * @param string|null $language
	 * @return array|string
	 */
	private function pluralize($message, array $translation, $count = null, $language = null)
	{
		if ($language === null) {
			$language = $this->getLanguage();
		}

		if ($count !== null) {
			if (is_string($translation[0])) {
				$pluralForm = 'n='. $count. ';plural=+('. $this->plurals[$language]['form']. ');';
				$pluralForm = preg_replace('/([a-z]+)/', '$$1', $pluralForm);

				$n = null;
				$plural = null;

				eval($pluralForm);

				$message = $plural !== null && isset($translation[$plural]) ? $translation[$plural] : $translation[0];
			} else {
				$result = array();
				foreach ($translation as $t) {
					$result[] = $this->pluralize($message, $t, $count, $language);
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
	public function getMessageInfo($message)
	{
		$num = strrpos($message, '.');
		$path = substr($message, 0, $num);
		$name = substr($message, $num + 1);
		$num = strrpos($path, '.');
		if ($num !== false) {
			$category = substr($path, $num + 1);
		} else {
			$category = $path;
		}
		$path = substr($path, 0, $num);
		$path = preg_replace('/\./', '/', $path);

		return array(
			'path' => $path,
			'category' => $category,
			'name' => $name
		);
	}

}