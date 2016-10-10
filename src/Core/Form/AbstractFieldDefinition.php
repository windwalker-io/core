<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Form;

use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Form\Field;
use Windwalker\Form\FieldDefinitionInterface;
use Windwalker\Form\FieldHelper;
use Windwalker\Form\Form;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The AbstractFieldDefinition class.
 *
 * @method  Field\TextField      text($name = null, $label = null)
 * @method  Field\NumberField    number($name = null, $label = null)
 * @method  Field\RangeField     range($name = null, $label = null)
 * @method  Field\DatetimeLocalField  datetimeLocal($name = null, $label = null)
 * @method  Field\MonthField     month($name = null, $label = null)
 * @method  Field\TelField       tel($name = null, $label = null)
 * @method  Field\TimeField      time($name = null, $label = null)
 * @method  Field\UrlField       url($name = null, $label = null)
 * @method  Field\WeekField      week($name = null, $label = null)
 * @method  Field\ButtonField    button($name = null, $label = null)
 * @method  Field\CheckboxField  checkbox($name = null, $label = null)
 * @method  Field\CheckboxesField  checkboxes($name = null, $label = null)
 * @method  Field\CustomHtmlField  customHtml($name = null, $label = null)
 * @method  Field\ColorField     color($name = null, $label = null)
 * @method  Field\EmailField     email($name = null, $label = null)
 * @method  Field\HiddenField    hidden($name = null, $label = null)
 * @method  Field\ListField      list($name = null, $label = null)
 * @method  Field\PasswordField  password($name = null, $label = null)
 * @method  Field\RadioField     radio($name = null, $label = null)
 * @method  Field\SpacerField    spacer($name = null, $label = null)
 * @method  Field\TextareaField  textarea($name = null, $label = null)
 * @method  Field\TimezoneField  timezone($name = null, $label = null)
 *
 * @since  3.0
 */
abstract class AbstractFieldDefinition implements FieldDefinitionInterface
{
	use BootableTrait;

	/**
	 * Property namespaces.
	 *
	 * @var  PriorityQueue
	 */
	protected $namespaces = [];

	/**
	 * Property form.
	 *
	 * @var  Form
	 */
	protected $form;

	/**
	 * Define the form fields.
	 *
	 * @param Form $form The Windwalker form object.
	 *
	 * @return  void
	 */
	public function define(Form $form)
	{
		$this->namespaces = new PriorityQueue;
		$this->namespaces->insert('Windwalker\Form\Field', PriorityQueue::NORMAL);

		$this->bootTraits($this);

		$this->form = $form;

		$this->doDefine($form);
	}

	/**
	 * Define the form fields.
	 *
	 * @param Form $form The Windwalker form object.
	 *
	 * @return  void
	 */
	abstract protected function doDefine(Form $form);

	/**
	 * __call
	 *
	 * @param   string $name
	 * @param   array  $args
	 *
	 * @return  \Windwalker\Form\Field\AbstractField
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __call($name, $args)
	{
		if (!count($args))
		{
			throw new \InvalidArgumentException('Please add name to field: ' . $name);
		}

		$field = FieldHelper::findFieldClass($name, clone $this->namespaces);

		if ($field === false)
		{
			throw new \InvalidArgumentException(sprintf('Field: %s (%s) not found. (Namespaces: %s)', $name, ucfirst($name) . 'Field', implode(" |\n ", iterator_to_array(clone $this->namespaces))));
		}

		$field = new $field(...$args);

		$this->addField($field);

		return $field;
	}

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
	public function add($name, $field = null, $fieldset = null, $group = null)
	{
		return $this->form->add($name, $field, $fieldset, $group);
	}

	/**
	 * addField
	 *
	 * @param string|Field\AbstractField|\SimpleXMLElement  $field
	 * @param string                                        $fieldset
	 * @param string                                        $group
	 *
	 * @return  Field\AbstractField|Field\ListField
	 */
	public function addField($field, $fieldset = null, $group = null)
	{
		return $this->form->addField($field, $fieldset, $group);
	}

	/**
	 * wrap
	 *
	 * @param string   $fieldset
	 * @param string   $group
	 * @param callable $callback
	 *
	 * @return  static
	 */
	public function wrap($fieldset, $group, callable $callback)
	{
		$this->form->wrap($fieldset, $group, $callback);

		return $this;
	}

	/**
	 * fieldset
	 *
	 * @param string   $fieldset
	 * @param callable $callback
	 *
	 * @return  static
	 */
	public function fieldset($fieldset, callable $callback)
	{
		$this->form->fieldset($fieldset, $callback);

		return $this;
	}

	/**
	 * group
	 *
	 * @param string   $group
	 * @param callable $callback
	 *
	 * @return  static
	 */
	public function group($group, callable $callback)
	{
		$this->form->group($group, $callback);

		return $this;
	}

	/**
	 * addNamespace
	 *
	 * @param string $namespace
	 * @param int    $priority
	 *
	 * @return  static
	 */
	public function addNamespace($namespace, $priority = PriorityQueue::NORMAL)
	{
		$this->namespaces->insert($namespace, $priority);

		return $this;
	}
}
 