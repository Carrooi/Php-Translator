#!/bin/sh

dir=$(cd `dirname $0` && pwd)

runnerScript="$dir/../vendor/nette/tester/Tester/coverage-report.php"
if [ ! -f "$runnerScript" ]; then
	echo "Nette Tester is missing. You can install it using Composer:" >&2
	echo "php composer.phar update --dev." >&2
	exit 2
fi

php "$runnerScript" -c "$dir/Translator/coverage.dat" -o "$dir/Translator/coverage.html" -s "$dir/../src/DK/Translator"