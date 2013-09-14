<?php

/**
 * Test: DK\Translator\Translator\Nette\Translator
 *
 * @testCase DKTests\Translator\Nette\TranslatorTest
 * @author David Kudera
 */

namespace DKTests\Translator\Nette;

use Tester\TestCase;
use DK\Translator\Nette\Translator;

require_once __DIR__. '/../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class TranslatorTest extends TestCase
{


	/** @var \DK\Translator\Nette\Translator */
	private $translator;


	protected function setUp()
	{
		$this->translator = new Translator(__DIR__. '/../../data');
		$this->translator->setLanguage('en');
	}

}