<?php

namespace Tests\Shine\XML;

use Tree6bee\Any\XML\JsonableXMLElement;

class OrderServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testXml2Array()
    {
        $xml = file_get_contents(__DIR__ . '/data/test.xml');

        //array
        $array = JsonableXMLElement::xml2Array($xml);
        $this->assertArrayHasKey('_service', $array);
        $this->assertEquals('demo', $array['_service']);
        $this->assertEquals('toUser', $array['Ext'][0]['_']);

        //debug
        if (getenv('DEBUG_OUTPUT')) {
            $json = new JsonableXMLElement($xml, LIBXML_NOCDATA | LIBXML_NOBLANKS);
            echo "\nxml:\n" . $xml;
            echo "\njson:\n" . json_encode($json, JSON_PRETTY_PRINT), "\n";
        }
    }
}
