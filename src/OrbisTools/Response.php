<?php
/**
 * User: Vasiliy Shvakin (orbisnull) zen4dev@gmail.com
 */

namespace OrbisTools;

use DeltaCore\Config;
use OrbisTools\Parts\Configurable;

class Response
{
    use Configurable;

    protected $body;
    protected $code = 200;
    protected $contentType = 'text/html';
    protected $charset = 'utf-8';
    protected $language = 'en';
    protected $modified;
    protected $timeToCache;
    protected $etag;

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return !is_null($this->charset) ? $this->charset : $this->getConfig('charset');
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param mixed $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return mixed
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return !is_null($this->language) ? $this->language : $this->getConfig('language');
    }

    /**
     * @param int|string $timeToCache
     */
    public function setTimeToCache($timeToCache)
    {
        $this->timeToCache = Time::toSeconds($timeToCache);
    }

    /**
     * @return int
     */
    public function getTimeToCache()
    {
        return $this->timeToCache;
    }

    public function getHttpCode()
    {
        return $this->getCode();
    }

    /**
     * @param mixed $etag
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;
    }

    /**
     * @return mixed
     */
    public function getEtag()
    {
        if (is_null($this->etag)) {
            $this->etag = hash('md5', $this->body);
        }
        return $this->etag;
    }

    public function sendHeaders()
    {
        if (headers_sent()) {
            throw new \LogicException('Headers already send');
        }
        header("HTTP/1.1 {$this->getHttpCode()}");
        header("Content-Type:{$this->getContentType()}; charset={$this->getCharset()}");
        header("Content-Language: {$this->getLanguage()}");

        $modified = $this->getModified();
        Header::modified($modified);

        $cacheTime = $this->getTimeToCache();
        if ($cacheTime>0) {
            Header::cache($cacheTime);
        } elseif ($cacheTime<0) {
            Header::noCache();
        }
        header("ETag: {$this->getEtag()}");
    }

    public function sendReplay()
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $etagRequest = $_SERVER['HTTP_IF_NONE_MATCH'];
            $etag = $this->getEtag();
            if ($etagRequest === $etag) {
                header('HTTP/1.0 304 Not Modified');
                header('Status: 304 Not Modified');
                return;
            }
        }
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $modifiedRequest = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            $modifiedRequest = strtotime($modifiedRequest);
            $modified = $this->getModified();
            if ($modifiedRequest >= $modified) {
                header('HTTP/1.0 304 Not Modified');
                header('Status: 304 Not Modified');
                return;
            }
        }
        $this->sendHeaders();
        echo $this->getBody();
    }
}