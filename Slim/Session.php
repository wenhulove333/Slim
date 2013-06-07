<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.2.0
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slim;

/**
 * Session
 * @package    Slim
 * @author     Josh Lockhart
 * @since      2.3.0
 */
class Session extends \Slim\Helper\Set
{
    /**
     * The session save handler
     * @var mixed
     */
    protected $handler;

    /**
     * Has the session started?
     * @var boolean
     */
    protected $started = false;

    /**
     * Has the session finished?
     * @var boolean
     */
    protected $finished = false;

    /**
     * Constructor
     * @param  array  $options Session settings
     * @param  mixed  $handler The session save handler
     */
    public function _construct(array $options = array(), $handler = null)
    {
        $this->setOptions($options);
        $this->setHandler($handler);
    }

    /**
     * Get the session save handler
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set the session save handler
     * @param mixed $handler
     * @return  bool
     */
    public function setHandler($handler)
    {
        if ($handler !== null) {
            $this->handler = $handler;

            return session_set_save_handler(
                array($this->handler, 'open'),
                array($this->handler, 'close'),
                array($this->handler, 'read'),
                array($this->handler, 'write'),
                array($this->handler, 'destroy'),
                array($this->handler, 'gc')
            );
        }
    }

    /**
     * Start the session
     * @throws \RuntimeException If session already started by PHP
     * @throws \RuntimeException If HTTP headers have already been sent and using HTTP session cookie
     * @throws \RuntimeException If unable to start new PHP session
     */
    public function start()
    {
        if ($this->started === true && $this->finished === false) {
            return true;
        }

        if (isset($_SESSION) && session_id()) {
            throw new \RuntimeException('Session already started by PHP.');
        }

        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            throw new \RuntimeException(sprintf('Unable to start session because headers have already been sent by "%s" (line %d).', $file, $line));
        }

        session_cache_limiter(''); // Disable cache headers from being sent by PHP
        ini_set('session.use_cookies', 1);

        if (session_start() === false) {
            throw new \RuntimeException('Unable to start session');
        }

        $this->data = &$_SESSION; // Needed to use inherited \Slim\Helper\Set methods on the $_SESSION array
        $this->started = true;
    }

    /**
     * Save session data and close session
     */
    public function save()
    {
        session_write_close();
        $this->finished = true;
    }

    /**
     * Regenerate session ID
     * @param  boolean $destroy Destroy existing session data?
     * @return bool
     */
    public function regenerate($destroy = false)
    {
        return session_regenerate_id($destroy);
    }

    /**
     * Clear session data
     */
    public function clear()
    {
        $this->data = array();
    }

    /**
     * Is the session started?
     * @return boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Is the session finished?
     * @return boolean
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * Set session options
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $theOptions = array_flip(array(
            'cache_limiter',
            'cookie_domain',
            'cookie_httponly',
            'cookie_lifetime',
            'cookie_path',
            'cookie_secure',
            'entropy_file',
            'entropy_length',
            'gc_divisor',
            'gc_maxlifetime',
            'gc_probability',
            'hash_bits_per_character',
            'hash_function',
            'name',
            'referer_check',
            'serialize_handler',
            'use_cookies',
            'use_only_cookies',
            'use_trans_sid',
            'upload_progress.enabled',
            'upload_progress.cleanup',
            'upload_progress.prefix',
            'upload_progress.name',
            'upload_progress.freq',
            'upload_progress.min-freq',
            'url_rewriter.tags',
        ));

        foreach ($options as $key => $value) {
            if (isset($theOptions[$key])) {
                ini_set('session.' . $key, $value);
            }
        }
    }
}
