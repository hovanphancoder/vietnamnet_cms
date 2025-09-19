<?php
namespace System\Libraries;

use Respect\Validation\Validator as RespectValidator;
use Respect\Validation\Exceptions\ValidationException;
use App\Libraries\Fastlang;

class Validate
{
    protected $errors = [];

    // Validate methods (example functions)

    public static function sql_table()
    {
        // List of SQL keywords that are not allowed as table names
        $sqlKeywords = [
            'select', 'order', 'table', 'group', 'where', 'index',
            'insert', 'update', 'delete', 'from', 'join', 'union',
            'having', 'into', 'alter', 'drop', 'create'
        ];

        return RespectValidator::alnum('_')->notEmpty()->lowercase()
            ->callback(function ($input) use ($sqlKeywords) {
                return !in_array(strtolower($input), $sqlKeywords);
            })
            ->setName('SQLTable');
    }

    public static function sql_column()
    {
        // List of SQL keywords that are not allowed as column names
        $sqlKeywords = [
            'select', 'order', 'table', 'group', 'where', 'index',
            'insert', 'update', 'delete', 'from', 'join', 'union',
            'having', 'into', 'alter', 'drop', 'create'
        ];

        return RespectValidator::alnum('_')
            ->callback(function ($input) use ($sqlKeywords) {
                return !in_array(strtolower($input), $sqlKeywords);
            })
            ->setName('SQLColumn');
    }

    public static function slug()
    {
        return RespectValidator::alnum('-_');
    }

    //alpha: Check if the string contains only alphabetic characters.
    public static function alpha()
    {
        return RespectValidator::alpha();
    }
    
    //digit: Check if the string contains only digits.
    public static function digit()
    {
        return RespectValidator::digit();
    }
    //lowercase: Check if the string contains only lowercase letters.
    public static function lowercase()
    {
        return RespectValidator::lowercase();
    }
    //uppercase: Check if the string contains only uppercase letters.
    public static function uppercase()
    {
        return RespectValidator::uppercase();
    }
    //contains: Check if the string contains a specific value.
    public static function contains($value)
    {
        return RespectValidator::contains($value);
    }
    // Alnum validator with additional characters
    public static function alnum(...$additionalChars)
    {   
        return RespectValidator::alnum(...$additionalChars);
    }
    // Length validator with minimum and maximum length
    public static function length($min = null, $max = null)
    {
        return RespectValidator::length($min, $max);
    }
    // Email validator
    public static function email()
    {
        return RespectValidator::email();
    }
    // URL validator
    public static function url()
    {
        return RespectValidator::url();
    }
    // Number validator
    public static function NumericVal()
    {
        return RespectValidator::NumericVal();
    }
    // Regex validator
    public static function regex($pattern)
    {
        return RespectValidator::regex($pattern);
    }
    // Date validator
    public static function date($format = 'Y-m-d')
    {
        return RespectValidator::date($format);
    }
    // NotEmpty validator
    public static function notEmpty()
    {
        return RespectValidator::notEmpty();
    }
    // Equals validator
    public static function equals($compareTo)
    {
        return RespectValidator::equals($compareTo);
    }
    // Phone number validator
    public static function phone()
    {
        return RespectValidator::phone();
    }
    // IP address validator
    public static function ip()
    {
        return RespectValidator::ip();
    }
    // In array validator
    public static function in($haystack)
    {
        return RespectValidator::in($haystack);
    }
    // Range validator
    public static function between($min, $max)
    {
        return RespectValidator::between($min, $max);
    }
    //startsWith: Check if the string starts with a specific value.
    public static function startsWith($value)
    {
        return RespectValidator::startsWith($value);
    }
    //endsWith: Check if the string ends with a specific value.
    public static function endsWith($value)
    {
        return RespectValidator::endsWith($value);
    }
    //inArray: Check if the value is in an array.
    public static function inArray(array $haystack, $strict = false)
    {
        return RespectValidator::in($haystack, $strict);
    }
    //uuid: Check if the string is a valid UUID.
    public static function uuid()
    {
        return RespectValidator::uuid();
    }
    //creditCard: Check if the string is a valid credit card number.
    public static function creditCard()
    {
        return RespectValidator::creditCard();
    }
    //not: Reverse the result of a validator (use ! for negation).
    public static function not($rule)
    {
        return RespectValidator::not($rule);
    }
    //optional: Allow a validator to be optional (will skip if value is null).
    public static function optional($rule)
    {
        return RespectValidator::optional($rule);
    }
    //lengthBetween: Check if the string length is within a certain range.
    public static function lengthBetween($min, $max)
    {
        return RespectValidator::length($min, $max);
    }
    //image: Check if the file is a valid image.
    public static function image()
    {
        return RespectValidator::image();
    }
    //json: Check if the string is valid JSON.
    public static function json()
    {
        return RespectValidator::json();
    }
    //domain: Check if the string is a valid domain name.
    public static function domain()
    {
        return RespectValidator::domain();
    }
    //macAddress: Check if the string is a valid MAC address.
    public static function macAddress()
    {
        return RespectValidator::macAddress();
    }
    //subset: Check if an array is a subset of another array.
    public static function subset(array $superset)
    {
        return RespectValidator::subset($superset);
    }
    //hexRgbColor: Check if the string is a valid hex RGB color code.
    public static function hexRgbColor()
    {
        return RespectValidator::hexRgbColor();
    }
    public static function maxAge($age)
    {
        return RespectValidator::maxAge($age);
    }
    public static function minAge($age)
    {
        return RespectValidator::minAge($age);
    }

    // There are many other methods, you can add similarly...

    /**
     * Validate an array of data according to declared rules
     *
     * @param array $data Data array to validate (['field_name' => 'value'])
     * @param array $rules Array of validation rules and error messages
     * [
     *      'username' => [
     *          'rules' => [Validate::alnum(), Validate::length(3, 20)],
     *          'messages' => ['Username must be a string without special characters.', 'Length must be from 3 to 20 characters.']
     *      ]
     * ]
     * @return bool True if data is valid, false if there are errors
     */
    public function check($data, $rules)
    {
        $this->errors = []; // Reset errors

        foreach ($rules as $field => $conditions) {
            $value = $data[$field] ?? null;

            if (isset($conditions['rules']) && is_array($conditions['rules'])) {
                foreach ($conditions['rules'] as $index => $rule) {
                    try {
                        // Perform validation
                        $rule->assert($value);
                    } catch (ValidationException $e) {
                        // Record error if any, store multiple errors for one field
                        $this->errors[$field][] = $conditions['messages'][$index] ?? Fastlang::_e('error_default');
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Lấy các lỗi sau khi validate
     *
     * @return array Mảng lỗi ['field_name' => ['error_message1', 'error_message2']]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
