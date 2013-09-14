# translator

PHP translator with plural forms support.
Can be also used with Nette.

This library is compatible with node package [translator](https://npmjs.org/package/translator).

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

## With Nette

```
new $translator = new \DK\Translator\Nette\Translator('/app/lang');
```

## Changelog

* 1.3.0
	+ Added tests

* 1.2.2
	+ Optimized plural forms
	+ Replacements were not applied to messages (huge bug, sorry)

* 1.2.1
	+ Replacements in messages

* 1.2.0
	+ Added translatePairs method