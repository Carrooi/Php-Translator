[![Build Status](https://travis-ci.org/sakren/php-translator.png?branch=master)](https://travis-ci.org/sakren/php-translator)

[![Donate](http://b.repl.ca/v1/donate-PayPal-brightgreen.png)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RY2VVL44CA94Q)

# translator

PHP translator with plural forms support.

This library is compatible with node package [translator](https://npmjs.org/package/translator).

## Installation

Preferred way is to install via [composer](http://getcomposer.org/).

```
php composer.phar require sakren/translator
```

## Dictionary files

This translator supposed to be translator working with key -> translation principe. For easier manipulation, you can have
many smaller dictionaries for smaller group of translations.

These dictionaries are json files with language code on the beginning. Below is example of few files.

```
/app/lang/homepage/en.menu.json
/app/lang/homepage/promo/en.box.json
/app/lang/en.about.json
```

There we have got three dictionaries, two for homepage and one for about page, but these names are totally up to you.

## Dictionary

Here is example of /app/lang/homepage/promo/en.box.json dictionary.

```
{
	"title": "Promo box",
	"description": "some description",
	"text": "and some really long text",
	"someOtherTextToDisplay": "other boring text"
}
```

This is the most simple example of dictionary (and most stupid). Again these translation's names are up to you.

## Usage

When you have got your dictionaries, you can setup translator and start using it.

```
$translator = new \DK\Translator\Translator('/app/lang');
$translator->setLanguage('en');

$message = $translator->translate('homepage.promo.box.text');		// output: and some really long text
```

You just have to set language, and base directory path.

Then you can begin with translating. You can see that messages to translate are paths to your dictionary files but with
dots instead of slashes and without language code and .json extension.

## Plural forms

There is already registered 138 plural forms and you can find list of them on [this](http://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html)
site. If you will miss some language, wrote issue or register it by your own.

First you have to set plural forms rule for language which you want to use. This is the same for like plural forms for
gettext.

```
$translator->addPluralForm(
	'en',				// language code
	2,					// total count of plural forms for this language
	'(n===1) ? 0 : 1'	// decision code. In "n" variable is count of items and it says that if it is 1 item, first (0) form will be used, otherwise second form
);
```

For comparing, here is example of czech plural forms.

```
$translator->addPluralForm(
	'cs',
	3,
	'(n===0) ? 2 : ((n===1) ? 0 : ((n>=2 && n<=4) ? 1 : 2))'
);
```

Now we have to add plural forms to our dictionary. (/app/lang/homepage/promo/en.box.json)

```
{
	"cars": [
		"1 car",
		"%count% cars"
	]
}
```

%count% will be automatically replaced with count of items. Again for comparing czech version. (/app/lang/homepage/promo/cs.box.json)

```
{
	"cars": [
		"1 auto",
		"%count% auta",
		"%count% aut"
	]
}
```

And now you can finally use it.

```
$message = $translator->translate('homepage.promo.box.cars', 2);		// output: 2 cars
```

## Replacements

%count% is the base example of replacements, but you can create others. For example you can set replacement for %site%
and then it will be automatically changed to name of your site, so if you will change it in future, you will change it only
in one place.

Dictionary:

```
{
	"info": "web site name: %site%"
}
```

Usage:

```
$translator->addReplacement('site', 'my-site-name.com');
$message = $translator->translate('dictionary.info');		// output: web site name: my-site-name.com
```

This is example of persistent replacements, but you can create independent replacements for each translation.

Dictionary:

```
{
	"info": "display some random variable: %something%"
}
```

Usage:

```
$message = $translator->translate('dictionary.info', null, array(		// output: display some random variable: 2 books
	'something' => '2 books'
));
```

if you do not need to pass any count (like in example above), you can remove second argument (null).

```
$message = $translator->translate('dictionary.info', array(		// output: display some random variable: 2 books
	'something' => '2 books'
));
```

### In names of translations

These replacements can be used also in message names. This is quite useful when you have got for example different user
roles with different translations. Then you can set replacement with name `role` and save these translations into
different directories.

en.admin.json:

```
{
	"title": "Page for admin"
}
```

en.normal.json:

```
{
	"title": "Page for normal user"
}
```

Usage:

```
$translator->addReplacement('role', $user->getRole());
$translator->translate('admin.%role%');
```

## Filters (eg. markdown)

If you want some special formatting in your translations (for example markdown syntax or others) you can add filter
which will automatically transform all successfully translated messages.

```
$translator->addFilter(function($message) {
	return strrev($message);
});

var_dump($translator->translate('homepage.title'));			// output is reversed title in homepage dictionary
```

## Helpers

You can imagine helpers as translation dependent filter with optional arguments.

```
$translator->addHelper('truncate', function($translation, $length) {
	return substr($translation, 0, $length);
});
$translator->addHelper('firstUpper', function($translation) {
	return ucfirst($translation);
});

var_dump($translator->translate('homepage.promo.box.description|truncate:4|firstUpper');		// output: Some
```

## List of translations

Sometimes you may want to display list of texts but don't want to create translations with these names: item1, item2,
item3 and so on. What if you will want to add some other? This is not the good idea.

But you can create lists in your dictionary and translator will return array of translations.

Dictionary:

```
{
	"someList": [
		["1st item"],
		["2nd item"],
		["3rd item"],
		["4th item"]
	]
}
```

Usage:

```
$messages = $translator->translate('dictionary.someList');		// output: array(1st item, 2nd item, 3rd item, 4th item)
```

And you can also use it with plural forms.

Dictionary:

```
{
	"fruits": [
		[
			"1 orange",
			"%count% oranges"
		],
		[
			"1 banana",
			"%count% bananas"
		]
	]
}
```

Usage:

```
$messages = $translator->translate('dictionary.fruits', 6);		// output: array(6 oranges, 6 bananas)
```

### Accessing exact item

```
$message = $translator->translate('dictionary.someList[0]');
```

### Shorter syntax

If your list contains just singular forms translations, you can use shorter syntax for it.

```
{
	"-- myList": [
		"first item",
		"second item",
		"third item"
	]
}
```

## List of pairs

If you have got one list of for example titles or headlines and other list with texts for these titles, you can let this
translator to automatically combine these two lists together into associative array.

Dictionary:

```
{
	"titles": [
		["first"],
		["second"]
	],
	"texts": [
		["text for first title"]
		["text for second title"]
	]
}
```

Usage:

```
$translator->translatePairs('dictionary', 'titles', 'texts');
```

Output:

```
[
	'first' => 'text for first title',
	'second' => 'text for second title'
]
```

## Translate whole array

When you have got some array, which you need to translate, you don't have to iterate through it yourself.

```
$messages = array(
    'homepage.promo.box.title',
    'homepage.promo.box.description',
    'homepage.promo.box.text'
);

$result = $translator->translateMap($messages);
```

If your array to translate contains translations just from one dictionary, you can set some kind of base path.

```
$messages = array(
    'title',
    'description',
    'text'
);

$result = $translator->translateMap($messages, null, null, 'homepage.promo.box.title');
```

Or use count for translations with plural forms.

```
$result = $translator->translateMap($messages, 6);
```

Or with some replacements.

```
$result = $translator->translateMap($messages, null, array('type' => 'book'));
```

## Temporary override language

There may be some cases when you need to get translations for different language.

```
$translator = new \DK\Translator\Translator('/app/lang');
$translator->setLanguage('en');

$message = $translator->translate('cs|homepage.title');
```

Now in `message` variable will be translation of `homepage.title` in czech language.

## Comments in dictionaries

You can write some comments into your dictionaries. These comments has to be enclosed into `#`.

```
{
	"message": [
		"# this message will be displayed in home page #",
		 "translation of message"
	]
}
```

Or with lists:

```
{
	"list": [
		"# this is list of some items #",
		[
			"# first item in list #",
			"first"
		],
		[
			"# second item in list #",
			"second"
		]
	]
}
```

## With Nette framework

If you want to use this translator with nette, please use [sakren/nette-translator](https://github.com/sakren/nette-translator)
library.

## Changelog

* 1.7.0
	+ Optimized messages parsing
	+ Added translation helpers
	+ Added method `getLastTranslated()`

* 1.6.4
	+ Method `getMessageInfo()` is now public

* 1.6.3
	+ Method `findTranslation()` is now public

* 1.6.2
	+ Added methods `getTranslated()` and `getUntranslated()`
	+ Added method `save()` into loaders (prepared for future improvements)

* 1.6.1
	+ Some internal changes

* 1.6.0
	+ Optimizations
	+ Added badges and travis
	+ Failing tests after clean installation
	+ Creating translator from config file (for API)
	+ Added method `hasTranslation`
	+ Option for temporary overriding language
	+ Added filters

* 1.5.0
	+ Added translateMap method

* 1.4.0
	+ Accessing items from lists in translate method

* 1.3.2
	+ Translate method: can pass args as second argument

* 1.3.1
	+ Added some information

* 1.3.0
	+ Added tests
	+ Added shorter syntax for writing lists
	+ Added support for comments
	+ Removed Nette support (will be in another package)

* 1.2.2
	+ Optimized plural forms
	+ Replacements were not applied to messages (huge bug, sorry)

* 1.2.1
	+ Replacements in messages

* 1.2.0
	+ Added translatePairs method