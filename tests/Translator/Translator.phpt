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


	public function testLoadPlurals()
	{
		$plurals = $this->translator->getPluralForms();
		Assert::true(!empty($plurals));
	}


	public function testTranslate()
	{
		$t = $this->translator->translate('web.pages.homepage.promo.title');
		Assert::same('Title of promo box', $t);
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

}

\run(new TranslatorTest);