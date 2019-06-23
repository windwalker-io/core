<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Form;

use Windwalker\Form\FieldDefinitionInterface;
use Windwalker\Form\Filter\MaxLengthFilter;
use Windwalker\Form\Form;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Form\Field;

/**
 * The CoreFieldDefinitionInterface class.
 *
 * @since  3.5.7
 */
interface CoreFieldDefinitionInterface extends FieldDefinitionInterface
{
    /*
     * MySQL text length. @see https://stackoverflow.com/a/23169977
     */
    public const TEXT_MAX_ASCII     = MaxLengthFilter::TEXT_MAX_ASCII;
    public const TEXT_MAX_UTF8      = MaxLengthFilter::TEXT_MAX_UTF8;
    public const LONGTEXT_MAX_ASCII = MaxLengthFilter::LONGTEXT_MAX_ASCII;
    public const LONGTEXT_MAX_UTF8  = MaxLengthFilter::LONGTEXT_MAX_UTF8;

    /*
     * Integer length. @see https://yd514.iteye.com/blog/1909097
     */
    public const INT_SIGNED_MAX = 2147483647;
    public const INT_SIGNED_MIN = -2147483648;
    public const INT_UNSIGNED_MAX = 4294967295;

    public const TINYINT_SIGNED_MAX = 127;
    public const TINYINT_SIGNED_MIN = -128;
    public const TINYINT_UNSIGNED_MAX = 255;

    /**
     * add
     *
     * @param string                     $name
     * @param string|Field\AbstractField $field
     * @param string                     $fieldset
     * @param string                     $group
     *
     * @return  Field\AbstractField|Field\ListField
     */
    public function add($name, $field = null, $fieldset = null, $group = null);

    /**
     * addField
     *
     * @param string|Field\AbstractField|\SimpleXMLElement $field
     * @param string                                       $fieldset
     * @param string                                       $group
     *
     * @return  Field\AbstractField|Field\ListField
     */
    public function addField($field, $fieldset = null, $group = null);

    /**
     * wrap
     *
     * @param string   $fieldset
     * @param string   $group
     * @param callable $callback
     *
     * @return  static
     */
    public function wrap($fieldset, $group, callable $callback);

    /**
     * fieldset
     *
     * @param string   $fieldset
     * @param callable $callback
     *
     * @return  static
     */
    public function fieldset($fieldset, callable $callback);

    /**
     * group
     *
     * @param string   $group
     * @param callable $callback
     *
     * @return  static
     */
    public function group($group, callable $callback);

    /**
     * addNamespace
     *
     * @param string $namespace
     * @param int    $priority
     *
     * @return  static
     */
    public function addNamespace($namespace, $priority = PriorityQueue::NORMAL);

    /**
     * addMap
     *
     * @param string $name
     * @param string $class
     *
     * @return  static
     */
    public function addMap($name, $class);
}
