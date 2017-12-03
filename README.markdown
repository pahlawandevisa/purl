[![Build Status](https://travis-ci.org/voku/purl.svg?branch=master)](https://travis-ci.org/voku/purl)
[![codecov.io](https://codecov.io/github/voku/purl/coverage.svg?branch=master)](https://codecov.io/github/voku/purl?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/voku/purl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/voku/purl/?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/518b188ed990481ea7da72ad6fb4734f)](https://www.codacy.com/app/voku/purl)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a068f0c2-f560-4a9b-9984-35f18cee600e/mini.png)](https://insight.sensiolabs.com/projects/a068f0c2-f560-4a9b-9984-35f18cee600e)
[![Latest Stable Version](https://poser.pugx.org/voku/purl/v/stable)](https://packagist.org/packages/voku/purl) 
[![Total Downloads](https://poser.pugx.org/voku/purl/downloads)](https://packagist.org/packages/voku/purl) 
[![Latest Unstable Version](https://poser.pugx.org/voku/purl/v/unstable)](https://packagist.org/packages/voku/purl)
[![PHP 7 ready](http://php7ready.timesplinter.ch/voku/purl/badge.svg)](https://travis-ci.org/voku/purl)
[![License](https://poser.pugx.org/voku/purl/license)](https://packagist.org/packages/voku/purl)

Purl
====

WARNING: this is only a Fork of "https://github.com/jwage/purl"

Purl is a simple Object Oriented URL manipulation library for PHP.

## Installation

The suggested installation method is via [composer](https://getcomposer.org/):

```sh
composer require voku/purl
```

Using Purl
----------

Creating Url instances is easy:

```php
$url = new \Purl\Url('http://jwage.com');
```

You can also create `Url` instances through the static `parse` method if you prefer that style:

```php
$url = \Purl\Url::parse('http://jwage.com');
```

One benefit of using this method is you can chain methods together after creating the `Url`:

```php
$url = \Purl\Url::parse('http://jwage.com')
	->set('scheme', 'https')
	->set('port', '443')
	->set('user', 'jwage')
	->set('pass', 'password')
	->set('path', 'about/me')
	->set('query', 'param1=value1&param2=value2')
	->set('fragment', 'about/me?param1=value1&param2=value2');

echo $url->getUrl(); // https://jwage:password@jwage.com:443/about/me?param1=value1&param2=value2#about/me?param1=value1&param2=value2

// $url->path becomes instanceof Purl\Path
// ... but you can also use "$url->setPathString()", so you still have autocompletion in our IDE! 

// $url->query becomes instanceof Purl\Query
// ... but you can also use "$url->setQueryString()", so you still have autocompletion in our IDE! 

// $url->fragment becomes instanceof Purl\Fragment
// ... but you can also use "$url->setFragmentString()", so you still have autocompletion in our IDE! 
```

### Path Manipulation

```php
$url = new \Purl\Url('http://jwage.com');

// add path segments one at a time
$url->path->add('about')->add('me');

// set the path data from a string
$url->setPathString('about/me/another_segment'); // $url->path becomes instanceof Purl\Path

// get the path segments
print_r($url->path->getData()); // array('about', 'me', 'another_segment')
```

### Query Manipulation

```php
$url = new \Purl\Url('http://jwage.com');
$url->query->set('param1', 'value1');
$url->query->set('param2', 'value2');

echo $url->query; // param1=value1&param2=value2
echo $url; // http://jwage.com?param1=value1&param2=value2

// set the query data from an array
$url->query->setData(array(
	'param1' => 'value1',
	'param2' => 'value2'
));

// set the query data from a string
$url->query = 'param1=value1&param2=value2'; // $url->query becomes instanceof Purl\Query
print_r($url->query->getData()); //array('param1' => 'value1', 'param2' => 'value2')
```

### Fragment Manipulation

```php
$url = new \Purl\Url('http://jwage.com');
$url->setFragmentString('about/me?param1=value1&param2=value2'); // $url->fragment becomes instanceof Purl\Fragment
```

A Fragment is made of a path and a query and comes after the hashmark (#).

```php
echo $url->fragment->path; // about/me
echo $url->fragment->query; // param1=value1&param2=value2
echo $url; // http://jwage.com#about/me?param1=value1&param2=value2
```

### Domain Parts

Purl can parse a URL in to parts and its canonical form. It uses the list of domains from http://publicsuffix.org to break the domain into its public suffix, registerable domain, subdomain and canonical form.

```php
$url = new \Purl\Url('http://about.jwage.com');

echo $url->publicSuffix; // com
echo $url->registerableDomain; // jwage.com
echo $url->subdomain; // about
echo $url->canonical; // com.jwage.about/
```

#### Staying Up To Date

The list of domains used to parse a URL into its component parts is updated from time to time.
To ensure that you have the latest copy of the public suffix list, you can refresh 
the local copy of the list by running `./vendor/bin/pdp-psl data`

### Extract URLs

You can easily extract urls from a string of text using the `extract` method:

```php
$string = 'some text http://google.com http://jwage.com';
$urls = \Purl\Url::extract($string);

echo $urls[0]; // http://google.com/
echo $urls[1]; // http://jwage.com/
```

### Join URLs

You can easily join two URLs together using Purl:

```php
$url = new \Purl\Url('http://jwage.com/about?param=value#fragment');
$url->join('http://about.me/jwage');
echo $url; // http://about.me/jwage?param=value#fragment
```

Or if you have another `Url` object already:

```php
$url1 = new \Purl\Url('http://jwage.com/about?param=value#fragment');
$url2 = new \Purl\Url('http://about.me/jwage');
$url1->join($url2);
echo $url1; // http://about.me/jwage?param=value#fragment
```
