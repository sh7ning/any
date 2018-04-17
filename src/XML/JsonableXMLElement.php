<?php

namespace Tree6bee\Any\XML;

use SimpleXMLElement;
use JsonSerializable;

/**
 * XML to Json conversion, XML to Array conversion
 *
 * 版本依赖:
 *      - JsonSerializable 5.4.0
 */
class JsonableXMLElement extends SimpleXMLElement implements JsonSerializable
{
    /**
     * SimpleXMLElement JSON serialization
     *
     * @return null|string
     *
     * @link http://php.net/JsonSerializable.jsonSerialize
     * @see JsonSerializable::jsonSerialize
     */
    public function jsonSerialize()
    {
        //处理属性
        $data = array();
        foreach ($this->attributes() as $attr => $val) {
            $data["_$attr"] = (string) $val;
        }

        //处理元素
        if (count($this)) { //有子节点
            foreach ($this as $tag => $element) {
                if (isset($data[$tag])) {
                    if (is_array($data[$tag]) === false) {
                        $data[$tag] = [$data[$tag]];
                    }
                    $data[$tag][] = $element;
                } else {
                    $data[$tag] = $element;
                }
            }
        } else {
            if (empty($data)) {
                $data = (string) $this;
            } else {
                $data['_'] = (string) $this;
            }
        }

        // if ($this->xpath('/*') == array($this)) {
        //     // the root element needs to be named
        //     $data = [$this->getName() => $data];
        // }

        return $data;
    }

    public static function xml2Json($xml)
    {
        return json_encode(new self($xml, LIBXML_NOCDATA | LIBXML_NOBLANKS));
    }

    public static function xml2Array($xml)
    {
        return json_decode(self::xml2Json($xml), true);
    }
}
