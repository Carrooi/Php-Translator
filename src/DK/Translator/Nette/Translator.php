<?php

namespace DK\Translator\Nette;

if (!interface_exists('Nette\Localization\ITranslator')) {
	throw new \Exception('Nette was not found');
}

use DK\Translator\Translator as DKTranslator;
use Nette\Localization\ITranslator;

/**
 *
 * @author David Kudera
 */
class Translator extends DKTranslator implements ITranslator
{

}