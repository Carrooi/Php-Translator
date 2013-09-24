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


	public function testSetDirectory()
	{
		Assert::exception(function() {
			$this->translator->setDirectory(__DIR__. '/../unknown');
		}, 'Exception');
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
		Assert::exception(function() {
			$this->translator->removeReplacement('test');
		}, 'Exception');
	}


	public function testTranslate()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.title');
		Assert::same('Title of promo box', $t);
	}


	public function testTranslate_notExists()
	{
		$t = $this->translator->translate('unknown.message');
		Assert::same('unknown.message', $t);
	}


	public function testTranslate_withoutLanguage()
	{
		$this->translator->setLanguage(null);
		Assert::exception(function() {
			$this->translator->translate('web.pages.homepage.simple.title');
		}, 'Exception');
	}


	public function testTranslate_notString()
	{
		$t = $this->translator->translate(array());
		Assert::same(array(), $t);
	}


	public function testTranslate_skipped()
	{
		$t = $this->translator->translate(':do.not.translate.me:');
		Assert::same('do.not.translate.me', $t);
	}


	public function testTranslate_list()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.list');
		Assert::same(array('1st item', '2nd item', '3rd item', '4th item', '5th item'), $t);
	}


	public function testTranslate_shorterList()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.newList');
		Assert::same(array('first', 'second', 'third'), $t);
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
	}


	public function testTranslate_directAccessNonList()
	{
		Assert::exception(function() {
			$this->translator->translate('web.pages.homepage.promo.title[5]');
		}, 'Exception');
	}


	public function testTranslate_directAccessOutOfRange()
	{
		Assert::exception(function() {
			$this->translator->translate('web.pages.homepage.promo.newList[5]');
		}, 'Exception');
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
		Assert::exception(function() {
			$this->translator->translatePairs('web.pages.homepage.promo', 'title', 'list');
		}, 'Exception');
	}


	public function testTranslatePairs_differentLength()
	{
		Assert::exception(function() {
			$this->translator->translatePairs('web.pages.homepage.promo', 'list', 'keys');
		}, 'Exception');
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