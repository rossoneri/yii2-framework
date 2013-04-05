<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**
 * NumberValidator validates that the attribute value is a number.
 *
 * The format of the number must match the regular expression specified in [[pattern]].
 * Optionally, you may configure the [[max]] and [[min]] properties to ensure the number
 * is within certain range.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class NumberValidator extends Validator
{
	/**
	 * @var boolean whether the attribute value can only be an integer. Defaults to false.
	 */
	public $integerOnly = false;
	/**
	 * @var integer|float upper limit of the number. Defaults to null, meaning no upper limit.
	 */
	public $max;
	/**
	 * @var integer|float lower limit of the number. Defaults to null, meaning no lower limit.
	 */
	public $min;
	/**
	 * @var string user-defined error message used when the value is bigger than [[max]].
	 */
	public $tooBig;
	/**
	 * @var string user-defined error message used when the value is smaller than [[min]].
	 */
	public $tooSmall;
	/**
	 * @var string the regular expression for matching integers.
	 */
	public $integerPattern = '/^\s*[+-]?\d+\s*$/';
	/**
	 * @var string the regular expression for matching numbers. It defaults to a pattern
	 * that matches floating numbers with optional exponential part (e.g. -1.23e-10).
	 */
	public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';


	/**
	 * Initializes the validator.
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = $this->integerOnly ? Yii::t('yii|{attribute} must be an integer.')
				: Yii::t('yii|{attribute} must be a number.');
		}
		if ($this->min !== null && $this->tooSmall === null) {
			$this->tooSmall = Yii::t('yii|{attribute} must be no less than {min}.');
		}
		if ($this->max !== null && $this->tooBig === null) {
			$this->tooBig = Yii::t('yii|{attribute} must be no greater than {max}.');
		}
	}

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param \yii\base\Model $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		if (is_array($value)) {
			$this->addError($object, $attribute, Yii::t('yii|{attribute} is invalid.'));
			return;
		}
		$pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
		if (!preg_match($pattern, "$value")) {
			$this->addError($object, $attribute, $this->message);
		}
		if ($this->min !== null && $value < $this->min) {
			$this->addError($object, $attribute, $this->tooSmall, array('{min}' => $this->min));
		}
		if ($this->max !== null && $value > $this->max) {
			$this->addError($object, $attribute, $this->tooBig, array('{max}' => $this->max));
		}
	}

	/**
	 * Validates the given value.
	 * @param mixed $value the value to be validated.
	 * @return boolean whether the value is valid.
	 */
	public function validateValue($value)
	{
		return preg_match($this->integerOnly ? $this->integerPattern : $this->numberPattern, "$value")
			&& ($this->min === null || $value >= $this->min)
			&& ($this->max === null || $value <= $this->max);
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * @param \yii\base\Model $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script.
	 */
	public function clientValidateAttribute($object, $attribute)
	{
		$label = $object->getAttributeLabel($attribute);
		$message = strtr($this->message, array(
			'{attribute}' => $label,
		));

		$pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
		$js = "
if(!value.match($pattern)) {
	messages.push(" . json_encode($message) . ");
}
";
		if ($this->min !== null) {
			$tooSmall = strtr($this->tooSmall, array(
				'{attribute}' => $label,
				'{min}' => $this->min,
			));

			$js .= "
if(value<{$this->min}) {
	messages.push(" . json_encode($tooSmall) . ");
}
";
		}
		if ($this->max !== null) {
			$tooBig = strtr($this->tooBig, array(
				'{attribute}' => $label,
				'{max}' => $this->max,
			));
			$js .= "
if(value>{$this->max}) {
	messages.push(" . json_encode($tooBig) . ");
}
";
		}

		if ($this->skipOnEmpty) {
			$js = "
if(jQuery.trim(value)!='') {
	$js
}
";
		}

		return $js;
	}
}