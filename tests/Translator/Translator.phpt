<?php

/**
 * Test: DK\Translator\Translator
 *
 * @testCase DKTests\Translator\TranslatorTest
 * @author David Kudera
 */

namespace DKTests\Translator;

use Tester\TestCase;
use DK\Translator\Translator;
use Tester\Assert;

require_once __DIR__. '/bootstrap.php';

/**
 *
 * @author David Kudera
 */
class TranslatorTest extends TestCase
{


	/** @var \DK\Translator\Translator */
	private $translator;


	protected function setUp()
	{
		$this->translator = new Translator(__DIR__. '/../data');
		$this->translator->setLanguage('en');
	}


	public function testConstruct_config()
	{
		$translator = new Translator(__DIR__. '/../data/config.json');
		Assert::same(realpath(__DIR__. '/../data'), $translator->getLoader()->getDirectory());
	}


	public function testGetLanguage()
	{
		Assert::same('en', $this->translator->getLanguage());
	}


	public function testLoadPlurals()
	{
		$plurals = $this->translator->getPluralForms();
		Assert::true(!empty($plurals));
	}


	public function testAddReplacement()
	{
		$this->translator->addReplacement('test', 'Test');
		$replacements = $this->translator->getReplacements();
		Assert::true(isset($replacements['test']));
		Assert::contains('Test', $replacements);
	}


	public function testRemoveReplacement()
	{
		$this->translator->addReplacement('test', 'Test');
		$this->translator->removeReplacement('test');
		$replacements = $this->translator->getReplacements();
		Assert::true(!isset($replacements['test']));
		Assert::notContains('Test', $replacements);		// I know that this is useless
	}


	public function testRemoveReplacement_notExists()
	{
		$translator = $this->translator;
		Assert::exception(function() use($translator) {
			$translator->removeReplacement('test');
		}, 'Exception');
	}


	public function testHasTranslation_exists()
	{
		Assert::true($this->translator->hasTranslation('web.pages.homepage.promo.title'));
	}


	public function testHasTranslation_exists_language()
	{
		Assert::true($this->translator->hasTranslation('web.pages.homepage.simple.title', 'cs'));
	}


	public function testHasTranslation_notExists()
	{
		Assert::false($this->translator->hasTranslation('some.unknown.translation'));
	}


	public function testHasTranslation_notExists_language()
	{
		Assert::false($this->translator->hasTranslation('some.unknown.translation', 'cs'));
	}


	public function testNormalizeMessage()
	{
		Assert::same(array(
			'ignored' => false,
			'language' => null,
			'message' => 'some.message.title',
			'num' => null,
			'helpers' => array(),
		), $this->translator->normalizeMessage('some.message.title'));
	}


	public function testNormalizeMessage_ignored()
	{
		Assert::same(array(
			'ignored' => true,
			'language' => null,
			'message' => 'do.not.translate.me',
			'num' => null,
			'helpers' => array(),
		), $this->translator->normalizeMessage(':do.not.translate.me:'));
	}


	public function testNormalizeMessage_language()
	{
		Assert::same(array(
			'ignored' => false,
			'language' => 'cs',
			'message' => 'overridden.language.title',
			'num' => null,
			'helpers' => array(),
		), $this->translator->normalizeMessage('cs|overridden.language.title'));
	}


	public function testNormalizeMessage_listItem()
	{
		Assert::same(array(
			'ignored' => false,
			'language' => null,
			'message' => 'accessing.list.item',
			'num' => 5,
			'helpers' => array(),
		), $this->translator->normalizeMessage('accessing.list.item[5]'));
	}


	public function testNormalizeMessage_helpers()
	{
		Assert::same(array(
			'ignored' => false,
			'language' => null,
			'message' => 'some.helper',
			'num' => null,
			'helpers' => array(
				array(
					'name' => 'firstUpper',
					'arguments' => array(),
				),
			),
		), $this->translator->normalizeMessage('some.helper|firstUpper'));
	}


	public function testNormalizeMessage_helpersMore()
	{
		Assert::same(array(
			'ignored' => false,
			'language' => null,
			'message' => 'some.helper',
			'num' => null,
			'helpers' => array(
				array(
					'name' => 'firstWord',
					'arguments' => array(),
				),
				array(
					'name' => 'firstUpper',
					'arguments' => array(),
				),
			),
		), $this->translator->normalizeMessage('some.helper|firstWord|firstUpper'));
	}


	public function testNormalizeMessage_helpersArguments()
	{
		Assert::same(array(
			'ignored' => false,
			'language' => null,
			'message' => 'some.helper',
			'num' => null,
			'helpers' => array(
				array(
					'name' => 'firstWord',
					'arguments' => array(),
				),
				array(
					'name' => 'truncate',
					'arguments' => array('5'),
				),
				array(
					'name' => 'removeLetters',
					'arguments' => array('a', 'b', 'c'),
				),
				array(
					'name' => 'firstUpper',
					'arguments' => array(),
				),
			),
		), $this->translator->normalizeMessage('some.helper|firstWord|truncate:5|removeLetters:a:b:c|firstUpper'));
	}

	public function testNormalizeMessage_combined()
	{
		Assert::same(array(
			'ignored' => true,
			'language' => 'cs',
			'message' => 'page.home.button',
			'num' => 4,
			'helpers' => array(
				array(
					'name' => 'truncate',
					'arguments' => array('5'),
				),
				array(
					'name' => 'firstUpper',
					'arguments' => array(),
				),
			),
		), $this->translator->normalizeMessage(':cs|page.home.button[4]|truncate:5|firstUpper:'));
	}


	public function testTranslate()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.title');
		Assert::same('Title of promo box', $t);
		Assert::same('web.pages.homepage.promo.title', $this->translator->getLastTranslated());
	}


	public function testTranslate_skipped()
	{
		$t = $this->translator->translate(':do.not.translate.me:');
		Assert::same('do.not.translate.me', $t);
		Assert::same(false, $this->translator->getLastTranslated());
	}


	public function testTranslate_list()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.list');
		Assert::same(array('1st item', '2nd item', '3rd item', '4th item', '5th item'), $t);
	}


	public function testTranslate_plurals()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.cars', 3);
		Assert::same('3 cars', $t);
	}


	public function testTranslate_pluralsList()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.fruits', 3);
		Assert::same(array('3 bananas', '3 citrons', '3 oranges'), $t);
	}


	public function testTranslate_replacementInMessage()
	{
		$this->translator->addReplacement('one', 1);
		$this->translator->addReplacement('dictionary', 'promo');
		$t = $this->translator->translate('web.pages.homepage.%dictionary%.%name%', null, array(
			'two' => 2,
			'name' => 'advanced'
		));
		Assert::same('1 2', $t);
		Assert::same('web.pages.homepage.promo.advanced', $this->translator->getLastTranslated());
	}


	public function testTranslate_secondArgArguments()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.advanced', array('one' => 1, 'two' => 2));
		Assert::same('1 2', $t);
	}


	public function testTranslate_directAccess()
	{
		Assert::same('first', $this->translator->translate('web.pages.homepage.promo.newList[0]'));
		Assert::same('second', $this->translator->translate('web.pages.homepage.promo.newList[1]'));
		Assert::same('third', $this->translator->translate('web.pages.homepage.promo.newList[2]'));
		Assert::same('web.pages.homepage.promo.newList', $this->translator->getLastTranslated());
	}


	public function testTranslate_rootDirectory()
	{
		Assert::same('hello', $this->translator->translate('first.test'));
	}


	public function testTranslate_directAccessNonList()
	{
		$translator = $this->translator;
		Assert::exception(function() use($translator) {
			$translator->translate('web.pages.homepage.promo.title[5]');
		}, 'Exception');
	}


	public function testTranslate_directAccessOutOfRange()
	{
		$translator = $this->translator;
		Assert::exception(function() use($translator) {
			$translator->translate('web.pages.homepage.promo.newList[5]');
		}, 'Exception');
	}


	public function testTranslate_notExists()
	{
		$t = $this->translator->translate('unknown.message');
		Assert::same('unknown.message', $t);
	}


	public function testTranslate_withoutLanguage()
	{
		$this->translator->setLanguage(null);
		$translator = $this->translator;
		Assert::exception(function() use($translator) {
			$translator->translate('web.pages.homepage.simple.title');
		}, 'Exception');
	}


	public function testTranslate_notString()
	{
		$t = $this->translator->translate(array());
		Assert::same(array(), $t);
	}


	public function testTranslate_shorterList()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.newList');
		Assert::same(array('first', 'second', 'third'), $t);
	}


	public function testTranslate_language()
	{
		Assert::same('Titulek promo boxu', $this->translator->translate('cs|web.pages.homepage.simple.title'));
	}


	public function testTranslate_language_skipped()
	{
		Assert::same('do.not.translate.me', $this->translator->translate(':cs|do.not.translate.me:'));
	}


	public function testTranslate_filters_not_found()
	{
		$this->translator->addFilter(function($message) {
			return strrev($message);
		});
		Assert::same('unknown.title', $this->translator->translate('unknown.title'));
	}


	public function testTranslate_filters_not_translatable()
	{
		$this->translator->addFilter(function($message) {
			return strrev($message);
		});
		Assert::same('web.pages.homepage.simple.title', $this->translator->translate(':web.pages.homepage.simple.title:'));
	}


	public function testTranslate_filters()
	{
		$this->translator->addFilter(function($message) {
			return strrev($message);
		});
		Assert::same('xob omorp fo eltiT', $this->translator->translate('web.pages.homepage.simple.title'));
	}


	public function testTranslate_filters_lists()
	{
		$this->translator->addFilter(function($message) {
			return strrev($message);
		});
		Assert::same(array(
			'sananab 3',
			'snortic 3',
			'segnaro 3'
		), $this->translator->translate('web.pages.homepage.promo.fruits', 3));
	}


	public function testTranslate_helpers()
	{
		$this->translator->addHelper('firstUpper', function($translation) {
			return ucfirst($translation);
		});
		$this->translator->addHelper('truncate', function($translation, $length) {
			return substr($translation, 0, $length);
		});

		Assert::same('He', $this->translator->translate('first.test|truncate:2|firstUpper'));
	}


	public function testGetTranslated()
	{
		$this->translator->translate('web.pages.homepage.promo.fruits', 3);
		$this->translator->translate('web.pages.homepage.promo.newList[2]');
		$this->translator->translate('unknown.title');
		$this->translator->translate('web.pages.homepage.promo.newList[2]');
		$this->translator->translate('web.pages.homepage.promo.title');
		$this->translator->translate('cs|web.pages.homepage.simple.title');
		$this->translator->translate(':web.pages.homepage.simple.title:');

		Assert::same(array(
			'web.pages.homepage.promo.fruits',
			'web.pages.homepage.promo.newList',
			'web.pages.homepage.promo.title',
			'web.pages.homepage.simple.title',
		), $this->translator->getTranslated());
	}


	public function testGetUntranslated()
	{
		$this->translator->translate('web.pages.homepage.promo.fruits', 3);
		$this->translator->translate('web.pages.homepage.promo.newList[2]');
		$this->translator->translate('unknown.title');
		$this->translator->translate('web.pages.homepage.promo.newList[2]');
		$this->translator->translate('web.pages.homepage.promo.title');
		$this->translator->translate('cs|web.pages.homepage.simple.title');
		$this->translator->translate(':web.pages.homepage.simple.title:');

		Assert::same(array(
			'unknown.title'
		), $this->translator->getUntranslated());
	}


	public function testTranslatePairs()
	{
		$t = $this->translator->translatePairs('web.pages.homepage.promo', 'keys', 'values');
		Assert::same(array(
			'1st title' => '1st text',
			'2nd title' => '2nd text',
			'3rd title' => '3rd text',
			'4th title' => '4th text'
		), $t);
	}


	public function testTranslatePairs_notArrays()
	{
		$translator = $this->translator;
		Assert::exception(function() use($translator) {
			$translator->translatePairs('web.pages.homepage.promo', 'title', 'list');
		}, 'Exception');
	}


	public function testTranslatePairs_differentLength()
	{
		$translator = $this->translator;
		Assert::exception(function() use($translator) {
			$translator->translatePairs('web.pages.homepage.promo', 'list', 'keys');
		}, 'Exception');
	}


	public function testTranslateMap()
	{
		$t = $this->translator->translateMap(array(
			'web.pages.homepage.promo.title',
			'web.pages.homepage.promo.info'
		));
		Assert::same(array(
			'Title of promo box',
			'Some info text'
		), $t);
	}


	public function testTranslateMap_plurals()
	{
		$t = $this->translator->translateMap(array(
			'web.pages.homepage.promo.cars',
			'web.pages.homepage.promo.mobile'
		), 6);
		Assert::same(array(
			'6 cars',
			'6 mobiles'
		), $t);
	}


	public function testTranslateMap_arguments()
	{
		$t = $this->translator->translateMap(array(
			'web.pages.homepage.promo.advanced'
		), null, array('one' => 1, 'two' => 2));
		Assert::same(array(
			'1 2'
		), $t);
	}


	public function testTranslateMap_base()
	{
		$t = $this->translator->translateMap(array('title', 'info'), null, null, 'web.pages.homepage.promo');
		Assert::same(array(
			'Title of promo box',
			'Some info text'
		), $t);
	}


	public function testTranslateMap_list()
	{
		$t = $this->translator->translateMap(array('web.pages.homepage.promo.fruits'), 4);
		Assert::same(array(array(
			'4 bananas',
			'4 citrons',
			'4 oranges'
		)), $t);
	}


	public function testGetData()
	{
		$this->translator->translate('web.pages.homepage.simple.title');
		$data = $this->translator->getData();
		Assert::same(array(
			'web/pages/homepage/simple' => array(
				'title' => array('Title of promo box')
			)
		), $data);
	}


	public function testInvalidate()
	{
		$this->translator->translate('web.pages.homepage.simple.title');
		$this->translator->invalidate();
		$data = $this->translator->getData();
		Assert::same(array(), $data);
	}

}

\run(new TranslatorTest);