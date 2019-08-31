<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\Config\Config;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\Core\User\UserManager;
use Windwalker\Dom\HtmlElement;
use Windwalker\Filter\InputFilter;
use function Windwalker\h;
use Windwalker\IO\Input;
use Windwalker\Session\Session;

/**
 * The CsrfManager class.
 *
 * @since  2.1.1
 */
class CsrfGuard
{
    public const TOKEN_KEY = 'form.token';

    /**
     * Property config.
     *
     * @var  Config
     */
    private $config;

    /**
     * Property input.
     *
     * @var  Input
     */
    private $input;

    /**
     * Property session.
     *
     * @var  Session
     */
    private $session;

    /**
     * Property userManager.
     *
     * @var  UserManager
     */
    private $userManager;

    /**
     * Property message.
     *
     * @var  string
     */
    protected $message = 'Invalid Token';

    /**
     * CsrfGuard constructor.
     *
     * @param Config      $config
     * @param Input       $input
     * @param Session     $session
     * @param UserManager $userManager
     */
    public function __construct(Config $config, Input $input, Session $session, UserManager $userManager)
    {
        $this->config      = $config;
        $this->input       = $input;
        $this->session     = $session;
        $this->userManager = $userManager;
    }

    /**
     * Validate token or die.
     *
     * @param bool   $justDie
     * @param string $message
     *
     * @return  bool
     * @throws \Exception
     */
    public function validate($justDie = false, $message = null)
    {
        $message = $message ?: $this->getMessage();

        if (!$this->checkToken()) {
            if ($justDie) {
                exit($message);
            }

            throw new InvalidTokenException($message);
        }

        return true;
    }

    /**
     * Create token input.
     *
     * @param   mixed $userId
     * @param   array $attribs
     *
     * @return  HtmlElement
     * @throws \Exception
     */
    public function input($userId = null, $attribs = [])
    {
        $attribs['type']  = 'hidden';
        $attribs['name']  = $this->getFormToken($userId);
        $attribs['value'] = 1;
        $attribs['class'] = $attribs['class'] ?? 'anticsrf';

        return h('input', $attribs, null);
    }

    /**
     * Create token string.
     *
     * @param   int $length Token string length.
     *
     * @return  string
     * @throws \Exception
     */
    public function createToken($length = 12)
    {
        static $chars = '0123456789abcdef';

        $max   = strlen($chars) - 1;
        $token = '';
        $name  = session_name();

        for ($i = 0; $i < $length; ++$i) {
            $token .= $chars[(random_int(0, $max))];
        }

        return md5($token . $name);
    }

    /**
     * Get form token string.
     *
     * @param   boolean $forceNew Force create new token.
     *
     * @return  string
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function getToken($forceNew = false)
    {
        /** @var Session $session */
        $session = $this->session;

        $token = $session->get(static::TOKEN_KEY);

        // Create a token
        if ($token === null || $forceNew) {
            $token = $this->createToken(12);

            $session->set(static::TOKEN_KEY, $token);
        }

        return $token;
    }

    /**
     * Get a token for specific user.
     *
     * @param   mixed   $userId   An identify of current user.
     * @param   boolean $forceNew Force create new token.
     *
     * @return string
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    public function getFormToken($userId = null, $forceNew = false)
    {
        $userId = $userId ?: $this->userManager->getUser()->id;
        $userId = $userId ?: $this->session->getId();

        $config = $this->config;

        return md5($config['system.secret'] . $userId . $this->getToken($forceNew));
    }

    /**
     * checkToken
     *
     * @param  mixed  $userId
     * @param  string $method
     *
     * @return  boolean
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    public function checkToken($userId = null, $method = null)
    {
        /** @var Input $input */
        $input = $this->input;
        $token = $this->getFormToken($userId);

        if ($method) {
            $input = $input->$method;
        }

        if ($input->get($token, null, InputFilter::ALNUM) !== null) {
            return true;
        }

        if ($input->header->get('X-CSRF-Token') === $token) {
            return true;
        }

        return false;
    }

    /**
     * Method to get property Message
     *
     * @return  string
     *
     * @since  3.5.6
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Method to set property message
     *
     * @param string $message
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.6
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }
}
