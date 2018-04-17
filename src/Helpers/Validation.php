<?php

namespace Tree6bee\Any\Helpers;

use DateTime;
use Exception;
use Tree6bee\Support\Helpers\Arr;

/**
 * 数据验证类
 * 所有的验证都走这个类，方便统一管理所有的验证类型
 * 参考 vendor/laravel/framework/src/Illuminate/Validation/Validator.php
 *
 * @copyright sh7ning 2016.1
 * @author    sh7ning
 * @version   0.0.1
 * @example
 * $input = array(
 *     'k1'   => array(
 *         '11',
 *     ),
 * );
 * $rules = array(
 *     'k1.0'      => 'required|max:10',
 * );
 * try {
 *     if ($error = Validation::validate($input, $rules)) {
 *         echo $error;
 *         return true;
 *     }
 * } catch (\Exception $e) {
 *     echo $e->getMessage();
 * }
 */
class Validation
{

    // Laravel 验证写法参考
    // public function sample()
    // {
    //     $validation = array(
    //         'name' => 'required|string|max:80',
    //     );
    //     $this->validate(request(), $validation);
    // }

    /**
     * data
     */
    protected $data = array();

    /**
     * rules
     */
    protected $rules = array();

    /**
     * error
     */
    protected $error = '';

    /**
     * 数字类型的验证
     */
    protected $numericRules = array('Numeric', 'Int');

    /**
     * 获取一个实例
     */
    public static function validate(array $data, array $rules, $validateAll = false)
    {
        $validation = new self($data, $rules, $validateAll);
        return $validation->error;
    }

    /**
     * private
     * @param boolean $validateAll 是否强制所有的规则验证完
     */
    protected function __construct(array $data, array $rules, $validateAll = false)
    {
        // $this->data = $validation->parseData($data);
        $this->data = $data;
        $this->setRules($rules);
        $this->run($validateAll);
    }

    /**
     * 验证
     */
    protected function run($validateAll)
    {
        foreach ($this->rules as $attribute => $rules) {
            $value = $this->getValue($attribute);
            if (! array_key_exists('Required', $rules) && empty($value)) {
                continue;
            }
            //必需
            foreach ($rules as $rule => $parameters) {
                if (! $this->passes($attribute, $value, $rule, $parameters) && ! $validateAll) {
                    return false;
                }
            }
        }
    }

    /**
     * 数据来源处理
     * @deprecated
     */
    protected function parseData(array $data)
    {
        return $data;
    }

    /**
     * setRules
     */
    protected function setRules(array $rules)
    {
        foreach ($rules as $key => $rule) {
            $rule = (is_string($rule)) ? explode('|', $rule) : $rule;
            foreach ($rule as $r) {
                list($r, $parameters) = $this->parseRule($r);
                if ($r == '') {
                    continue;
                }
                $this->rules[$key][$r] = $parameters;
            }
        }

        return $this;
    }

    /**
     * 验证单个规则
     * @param string $attribute 字段 如 'name'
     * @param string $rule 单个规则 如 'required'
     */
    protected function passes($attribute, $value, $rule, $parameters)
    {
        $method = "validate{$rule}";
        //存在该验证方法
        if (method_exists($this, $method)) {
            if (! $this->$method($value, $parameters, $attribute)) {
                $this->addFailure($attribute, $rule);
                return false;
            }
        }
        return true;
    }

    /**
     * 添加错误信息
     * @todo
     */
    protected function addFailure($attribute, $rule)
    {
        $this->error = "$attribute $rule error";
    }

    /**
     * Extract the rule name and parameters from a rule.
     *
     * @param  array|string  $rules
     * @return array
     */
    protected function parseRule($rules)
    {
        $parameters = array();

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);

            $parameters = $this->parseParameters($rules, $parameter);
        }

        return array(ucwords(trim($rules)), $parameters);
    }

    /**
     * Parse a parameter list.
     *
     * @param  string  $rule
     * @param  string  $parameter
     * @return array
     */
    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    /**
     * Get the value of a given attribute.
     *
     * @param  string  $attribute
     * @return mixed
     */
    protected function getValue($attribute)
    {
        return Arr::get($this->data, $attribute);
    }

    /**
     * 验证密码
     * 要求:必须包含大写、小写字母和数字
     * 至少一个小写字母，至少一个大写字母，至少包含一个数字
     * 不能包含多个连续的相同字符，不是常见密码
     * 不能与账户名相同，
     * qq:密码由6-16个字符组成,区分大小写,不能包含空格
     * tobao:只能包含大小写字母，数字，标点符号(空格外)，至少其中两种类型
     * alipay:必须是6-20个英文字母、数字或符号，不能是纯数字
     * @todo
     */
    public static function validatePassword($value, $parameters)
    {
    //     if(!is_string($str)) return false;
    //     if(!self::required($str)) return true;
    //     //require greater than 6 characters
    //     if(!self::length($str, array('min'=>6, 'max'=>50))) return false;
    //     //require at least one case insensitive letter
    //     $pattern = '^.*[a-z]+.*$';
    //     if(!self::regex($str, $pattern)) return false;
    //     //require at least one number
    //     $pattern = '^.*[0-9]+.*$';
    //     if(!self::regex($str, $pattern)) return false;
    //     //only allow these characters
    //     $pattern = '^[-a-z0-9 !@#$%^&+_()=*~]*$';
    //     if(!self::regex($str, $pattern)) return false;
    //     return true;

        // $exp = '/' . $pattern . '/' . $flags;
        // return preg_match($exp, $s) ? true : false;
    }

    /**
     * 验证昵称(用户名)
     * @todo
     * 要求:长度最少几位，格式中文还是纯英文数字_还是可以有特殊符号？同时不能为 admin 和 其他敏感词
     * @param array $parameters 名称形式 如 array('zh', 'en', 'mixed')
     */
    public static function validateNickname($value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }
        self::requireParameterCount(1, $parameters, 'regex');
        switch ($parameters[0]) {
        // case 'zh':  //这里可以用中文判断
        // case 'en':  //这里直接可以用alpha
            default:    //不建议在正则中使用长度判断，长度用专门的正则
                $pattern = '/^[a-zA-Z\x{4e00}-\x{9fa5}]+$/u';
        }
        return preg_match($pattern, $value);
    }

    /**
     * 判断是否为纯中文字符串(utf编码)
     * @description 参考 http://www.educity.cn/develop/684578.html
     */
    public static function validateZh($value)
    {
        if (preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $value)) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否包含emoji
     */
    public static function validateEmoji($value)
    {
        $value = json_encode($value);
        // preg_match_all("#(\\\ue[0-9a-f]{3})#ie", $value, $matchs);
        preg_match_all("/(\\\\ud83c\\\\u[0-9a-f]{4})|(\\\\ud83d\\\u[0-9a-f]{4})/", $value, $matchs);
        if (isset($matchs[0][0])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证手机号
     * 所有手机号都要发短信验证码验证成功才能保证真实存在
     */
    public static function validatePhone($value)
    {
        return preg_match('/^1[3|4|5|7|8]\d{9}$/', $value);
    }

    /**
     * 验证邮箱
     */
    public static function validateEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 验证是否是ip地址
     */
    public static function validateIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * 验证是否是有效的URL
     */
    public static function validateUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Required
     * 这个验证的两边不要有空格，不然会多验证一次...
     *
     * @access  public
     * @param   string
     * @return  bool
     */
    public static function validateRequired($value)
    {
        if (empty($value)) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        return true;
    }

    //---类型验证--

    public static function validateArray($value)
    {
        return is_array($value);
    }

    public static function validateBoolean($value)
    {
        $acceptable = array(true, false, 0, 1, '0', '1');
        return in_array($value, $acceptable, true);
    }

    public static function validateInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    public static function validateNumeric($value)
    {
        return is_numeric($value);
    }

    public static function validateString($value)
    {
        return is_string($value);
    }

    public static function validateJson($value)
    {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 验证是否是纯字母
     */
    public static function validateAlpha($value)
    {
        return is_string($value) && preg_match("/^([a-z])+$/i", $value);
        //laravel
        // return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    // --------------------------------------------------------------------

    /**
     * Alpha-numeric
     */
    public static function validateAlphaNum($value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }
        return preg_match("/^([a-z0-9])+$/i", $value);
        //laravel
        // return preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    // --------------------------------------------------------------------

    /**
     * Alpha-numeric with underscores and dashes
     */
    public static function validateAlphaDash($value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }
        return preg_match("/^([-a-z0-9_-])+$/i", $value);
        //laravel
        // return preg_match('/^[\pL\pM\pN_-]+$/u', $value);
    }

    /**
     * 正则
     * 正则修饰符参考 http://php.net/manual/zh/reference.pcre.pattern.modifiers.php
     * 常见正则整理:
     * 中英文 $pattern = '/^[a-zA-Z\x{4e00}-\x{9fa5}]{6,20}$/u';
     */
    protected function validateRegex($value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        self::requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value);
    }

    public static function validateDate($value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if (! is_string($value) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    protected function validateDateFormat($value, $parameters)
    {
        self::requireParameterCount(1, $parameters, 'date_format');

        if (! is_string($value)) {
            return false;
        }

        $parsed = date_parse_from_format($parameters[0], $value);

        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    //--数字大小或则字符串长度或则数组的数目等--
    protected function validateSize($value, $parameters, $attribute)
    {
        self::requireParameterCount(1, $parameters, 'size');

        return $this->getSize($attribute, $value) == $parameters[0];
    }

    protected function validateBetween($value, $parameters, $attribute)
    {
        self::requireParameterCount(2, $parameters, 'between');

        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    /**
     * Minimum Length
     */
    protected function validateMin($value, $parameters, $attribute)
    {
        self::requireParameterCount(1, $parameters, 'min');

        return $this->getSize($attribute, $value) >= $parameters[0];
    }


    /**
     * Max Length
     */
    protected function validateMax($value, $parameters, $attribute)
    {
        self::requireParameterCount(1, $parameters, 'max');
        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    protected function validateIn($value, $parameters, $attribute)
    {
        if (is_array($value) && ! empty($this->getRule($attribute, 'Array'))) {
            return count(array_diff($value, $parameters)) == 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }

    /**
     * Validate an attribute is not contained within a list of values.
     */
    protected function validateNotIn($value, $parameters, $attribute)
    {
        return ! $this->validateIn($value, $parameters, $attribute);
    }

    //---验证类辅助方法---

    /**
     * 检查参数个数
     */
    protected static function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new Exception("Validation rule $rule requires at least $count parameters.");
        }
    }

    /**
     * 获取长度
     */
    protected function getSize($attribute, $value)
    {
        $hasNumeric = ! empty($this->getRule($attribute, $this->numericRules));

        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
        if (is_numeric($value) && $hasNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        }

        return mb_strlen($value);
    }

    /**
     * 某个字段是否具有指定规则
     */
    protected function getRule($attribute, $rules)
    {
        if (! array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        $allRules = array_keys($this->rules[$attribute]);
        return array_intersect($allRules, $rules);
    }
}
