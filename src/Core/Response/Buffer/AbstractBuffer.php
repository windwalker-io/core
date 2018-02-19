<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Response\Buffer;

/**
 * The AbstractBuffer class.
 *
 * @since  3.0
 */
abstract class AbstractBuffer
{
    /**
     * Determines whether the request was successful
     *
     * @var  boolean
     */
    public $success = true;

    /**
     * The main response message
     *
     * @var  string
     */
    public $message;

    /**
     * Property code.
     *
     * @var  int
     */
    public $code;

    /**
     * The response data
     *
     * @var  mixed
     */
    public $data;

    /**
     * Constructor
     *
     * @param string $message
     * @param mixed  $data
     * @param bool   $success
     * @param int    $code
     */
    public function __construct($message = null, $data = null, $success = true, $code = 200)
    {
        $this->data    = $data;
        $this->success = $success;
        $this->message = $message;
        $this->code    = $code;
    }

    /**
     * toString
     *
     * @return  string
     */
    abstract public function toString();

    /**
     * __toString
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return (string) $this->toString();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        return (string) $e;
    }

    /**
     * getMimeType
     *
     * @return  string
     */
    abstract public function getMimeType();

    /**
     * Method to get property Success
     *
     * @return  boolean
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Method to set property success
     *
     * @param   boolean $success
     *
     * @return  static  Return self to support chaining.
     */
    public function setSuccess($success)
    {
        $this->success = (bool) $success;

        return $this;
    }

    /**
     * Method to get property Message
     *
     * @return  string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Method to set property message
     *
     * @param   string $message
     *
     * @return  static  Return self to support chaining.
     */
    public function setMessage($message)
    {
        $this->message = (string) $message;

        return $this;
    }

    /**
     * Method to get property Data
     *
     * @return  mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Method to set property data
     *
     * @param   mixed $data
     *
     * @return  static  Return self to support chaining.
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Method to get property Code
     *
     * @return  int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Method to set property code
     *
     * @param   int $code
     *
     * @return  static  Return self to support chaining.
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }
}
