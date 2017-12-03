<?php

/*
 * This file is part of the Purl package, a project by Jonathan H. Wage.
 *
 * (c) 2013 Jonathan H. Wage
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Purl;

use Pdp\Parser as PslParser;
use Pdp\PublicSuffixListManager;

/**
 * Url is a simple OO class for manipulating Urls in PHP.
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 *
 * @property string         $scheme
 * @property string         $host
 * @property integer        $port
 * @property string         $user
 * @property string         $pass
 * @property \Purl\Path     $path
 * @property \Purl\Query    $query
 * @property \Purl\Fragment $fragment
 * @property string         $publicSuffix
 * @property string         $registerableDomain
 * @property string         $subdomain
 * @property string         $canonical
 * @property string         $resource
 */
class Url extends AbstractPart
{
  /**
   * @var string The original url string.
   */
  private $url;

  /**
   * @var ParserInterface
   */
  private $parser;

  /**
   * Construct a new Url instance.
   *
   * @param string          $url
   * @param ParserInterface $parser
   */
  public function __construct($url = null, ParserInterface $parser = null)
  {
    $this->data = [
        'scheme'             => null,
        'host'               => null,
        'port'               => null,
        'user'               => null,
        'pass'               => null,
        'path'               => null,
        'query'              => null,
        'fragment'           => null,
        'publicSuffix'       => null,
        'registerableDomain' => null,
        'subdomain'          => null,
        'canonical'          => null,
        'resource'           => null,
    ];

    $this->partClassMap = [
        'path'     => Path::class,
        'query'    => Query::class,
        'fragment' => Fragment::class,
    ];

    $this->url = $url;
    $this->parser = $parser;
  }

  /**
   * Static convenience method for creating a new Url instance.
   *
   * @param string $url
   *
   * @return Url
   */
  public static function parse($url): self
  {
    return new self($url);
  }

  /**
   * Extracts urls from a string of text.
   *
   * @param string $string
   *
   * @return array $urls
   */
  public static function extract($string): array
  {
    $regex = "/(\bhttps?:\/\/[^\s()<>]+(?:\([\w]+\)|[^[:punct:]\s]|\/))/i";

    preg_match_all($regex, $string, $matches);
    $urls = [];
    foreach ($matches[0] as $url) {
      $urls[] = self::parse($url);
    }

    return $urls;
  }

  /**
   * Creates an Url instance based on data available on $_SERVER variable.
   *
   * @return Url
   */
  public static function fromCurrent(): self
  {
    $scheme = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    ) ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = "$scheme://$host";

    $url = new self($baseUrl);

    if (!empty($_SERVER['REQUEST_URI'])) {

      if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
        list($path, $query) = array_pad(explode('?', $_SERVER['REQUEST_URI'], 2), 2, null);
      } else {
        $path = $_SERVER['REQUEST_URI'];
        $query = '';
      }

      $url->set('path', $path);
      $url->set('query', $query);
    }

    // Only set port if different from default (80 or 443)
    if (!empty($_SERVER['SERVER_PORT'])) {
      $port = $_SERVER['SERVER_PORT'];

      if (
          ($scheme == 'http' && $port != 80)
          ||
          ($scheme == 'https' && $port != 443)
      ) {
        $url->set('port', $port);
      }
    }

    // Authentication
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
      $url->set('user', $_SERVER['PHP_AUTH_USER']);
      if (!empty($_SERVER['PHP_AUTH_PW'])) {
        $url->set('pass', $_SERVER['PHP_AUTH_PW']);
      }
    }

    return $url;
  }

  /**
   * Gets the ParserInterface instance used to parse this Url instance.
   *
   * @return ParserInterface
   */
  public function getParser(): ParserInterface
  {
    if ($this->parser === null) {
      $this->parser = self::createDefaultParser();
    }

    return $this->parser;
  }

  /**
   * Sets the ParserInterface instance to use to parse this Url instance.
   *
   * @param ParserInterface $parser
   */
  public function setParser(ParserInterface $parser)
  {
    $this->parser = $parser;
  }

  /**
   * Join this Url instance together with another Url instance or a string url.
   *
   * @param Url|string $url
   *
   * @return Url
   */
  public function join($url): self
  {
    $this->initialize();
    $parts = $this->getParser()->parseUrl($url);

    foreach ($parts as $partsKey => $partsValue) {
      if ($partsValue !== null) {
        $this->data[$partsKey] = $partsValue;
      }
    }

    foreach ($this->data as $key => &$value) {
      $value = $this->preparePartValue($key, $value);
    }

    return $this;
  }

  /** @noinspection PhpMissingParentCallCommonInspection */
  /**
   * @inheritDoc
   * @override
   */
  public function set($key, $value)
  {
    $this->initialize();
    $this->data[$key] = $this->preparePartValue($key, $value);

    return $this;
  }

  /**
   * @param $string
   */
  public function setPathString($string)
  {
    $this->set('path', $string);
  }

  /**
   * Set the Path instance.
   *
   * @param Path $path
   *
   * @return $this
   */
  public function setPath(Path $path)
  {
    $this->data['path'] = $path;

    return $this;
  }

  /**
   * Get the Path instance.
   *
   * @return Path
   */
  public function getPath(): Path
  {
    $this->initialize();

    return $this->data['path'];
  }

  /**
   * @param $string
   */
  public function setQueryString($string)
  {
    $this->set('query', $string);
  }

  /**
   * Set the Query instance.
   *
   * @param Query $query
   *
   * @return $this
   */
  public function setQuery(Query $query)
  {
    $this->data['query'] = $query;

    return $this;
  }

  /**
   * Get the Query instance.
   *
   * @return Query
   */
  public function getQuery(): Query
  {
    $this->initialize();

    return $this->data['query'];
  }

  /**
   * @param $string
   */
  public function setFragmentString($string)
  {
    $this->set('fragment', $string);
  }

  /**
   * Set the Fragment instance.
   *
   * @param Fragment $fragment
   *
   * @return $this
   */
  public function setFragment(Fragment $fragment)
  {
    $this->data['fragment'] = $fragment;

    return $this;
  }

  /**
   * Get the Fragment instance.
   *
   * @return Fragment
   */
  public function getFragment(): Fragment
  {
    $this->initialize();

    return $this->data['fragment'];
  }

  /**
   * Gets the netloc part of the Url. It is the user, pass, host and port returned as a string.
   *
   * @return string
   */
  public function getNetloc(): string
  {
    $this->initialize();

    return ($this->user && $this->pass
            ? $this->user . ($this->pass
                ? ':' . $this->pass : '') . '@'
            : '') . $this->host . ($this->port
            ? ':' . $this->port : '');
  }

  /**
   * Builds a string url from this Url instance internal data and returns it.
   *
   * @return string
   */
  public function getUrl(): string
  {
    $this->initialize();

    $parts = \array_map(
        function ($value) {
          return (string)$value;
        },
        $this->data
    );

    if (!$this->isAbsolute()) {
      return self::httpBuildUrlRelative($parts);
    }

    return self::httpBuildUrl($parts);
  }

  /**
   * Set the string url for this Url instance and sets initialized to false.
   *
   * @param string
   */
  public function setUrl($url)
  {
    $this->initialized = false;
    $this->data = [];
    $this->url = $url;
  }

  /**
   * Checks if the Url instance is absolute or not.
   *
   * @return boolean
   */
  public function isAbsolute(): bool
  {
    $this->initialize();

    return $this->scheme && $this->host;
  }

  /**
   * @inheritDoc
   */
  public function __toString()
  {
    return $this->getUrl();
  }

  /**
   * @inheritDoc
   */
  protected function doInitialize()
  {
    $parts = $this->getParser()->parseUrl($this->url);

    foreach ($parts as $k => $v) {
      if (!isset($this->data[$k])) {
        $this->data[$k] = $v;
      }
    }

    foreach ($this->data as $key => &$value) {
      $value = $this->preparePartValue($key, $value);
    }
  }

  /**
   * Reconstructs a string URL from an array of parts.
   *
   * @param array $parts
   *
   * @return string $url
   */
  private static function httpBuildUrl(array $parts): string
  {
    $relative = self::httpBuildUrlRelative($parts);

    return sprintf(
        '%s://%s%s%s%s',
        $parts['scheme'],
        $parts['user'] ? sprintf('%s%s@', $parts['user'], $parts['pass'] ? sprintf(':%s', $parts['pass']) : '') : '',
        $parts['host'],
        $parts['port'] ? sprintf(':%d', $parts['port']) : '',
        $relative
    );
  }

  /**
   * Reconstructs relative part of URL from an array of parts.
   *
   * @param array $parts
   *
   * @return string $url
   */
  private static function httpBuildUrlRelative(array $parts): string
  {
    $parts['path'] = ltrim($parts['path'], '/');

    return sprintf(
        '/%s%s%s',
        $parts['path'] ? $parts['path'] : '',
        $parts['query'] ? '?' . $parts['query'] : '',
        $parts['fragment'] ? '#' . $parts['fragment'] : ''
    );
  }

  /**
   * Creates the default Parser instance to parse urls.
   *
   * @return Parser
   */
  private static function createDefaultParser(): Parser
  {
    $pslManager = new PublicSuffixListManager(dirname(dirname(__DIR__)) . '/data');
    $pslParser = new PslParser($pslManager->getList());

    return new Parser($pslParser);
  }
}
