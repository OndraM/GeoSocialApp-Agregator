<?php
class ListValuesArrayTest extends PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp() {
        parent::setUp();
        $this->view = new Zend_View(array(
            'helperPath' => array('GSAA_View_Helper' => APPLICATION_PATH . '/views/helpers')
        ));
        $this->helper = new GSAA_View_Helper_ListValuesArray();
        $this->helper->setView($this->view);
    }

    protected function tearDown() {
        unset($this->helper);
        parent::tearDown();
    }

    public function arrayProvider() {

    }

    public function testNothingIsRenderedWithoutValues() {
        $this->assertEquals('', $this->helper->listValuesArray(array(), ''));
        $this->assertEquals('', $this->helper->listValuesArray(array(array()), ''));
    }

    public function testListRenderedProperly() {
        $array = array(
                array('fb' => '123456789'),
                array('gg' => '333333333'),
                array('fq' => '+420800123456'),
                );
        $result = $this->helper->listValuesArray($array, 'phone');
        $dom = new Zend_Dom_Query($result);
        $results = $dom->query('ul li');
        $this->assertEquals(count($array), count($results));
        $i = 0;
        foreach ($results as $line) {
            $this->assertContains(current($array[$i++]), $line->nodeValue);
        }
    }
    public function testWhenEscapingIsDisabledAndValueWithSpecialCharactersIsPassedItIsPresentWithoutEscaping() {
        $array = array(array('fb' => '<a href="http://link.com">Link that!"\'should &not <be> escaped&<br /></a>'));
        $result = $this->helper->listValuesArray($array, 'notes', false);
        $this->assertContains(current(current($array)), $result);
    }

    public function testLinksRenderedProperly() {
        $array = array(
                array('gg' => array('Link name' => 'http://google-link.com/subpage/subpage.html')),
                array('fq' => array('Link from foursquare' => 'http://link-from-foursquare.4sq')),
                array('fq' => array('Second link'   => 'http://www.link.cz')),
                array('fq' => array('Twitter'    => 'http://twitter.com/foursquare')),
                array('fb' => array('Very long link from Facebok with \'some\' "really" special & <characters>' => 'http://mal\'form<>ed"-link.co\'m&'))
            );
        $result = $this->helper->listValuesArray($array, 'links');
        $dom = new Zend_Dom_Query($result);
        $results = $dom->query('ul li');
        $this->assertEquals(count($array), count($results));
        $i = 0;
        foreach ($results as $line) {
            // assert link title equals; html entities are parsed into original value
            $this->assertEquals(key(current($array[$i])), $line->nodeValue);
            // assert link href equals
            $this->assertEquals(current(current($array[$i])), $line->getElementsByTagName('a')->item(0)->getAttribute('href'));
            $i++;
        }
    }

    public function testWhenEscapingIsDisabledAndLinkTitleWithSpecialCharactersIsPassedItIsPresentWithoutEscaping() {
        $array = array(array('fb' => array('Very long link from Facebok with \'some\' "really" special & <characters>' => 'http://link.eu')));
        $result = $this->helper->listValuesArray($array, 'links', false);
        $this->assertContains(key(current(current($array))), $result);
    }

    public function testTipsRenderedProperly() {
        $array = array(
                array('fb' => array('id' => 'id21345678', 'text' => 'Tip text', 'date' => 1323821942)),
                array('fb' => array('id' => 'AKJDSA34567', 'text' => 'Second tip text', 'date' => 1323809050)),
                array('fq' => array('id' => '345FGHJK', 'text' => 'Tip text <with> special \'characters\'', 'date' => 1323817251)),
                array('fq' => array('id' => 'ERTYUI5678', 'text' => 'All special &chacraters& should be escaped', 'date' => 1323816322)),
                array('gw' => array('id' => 'XCVBN4567', 'text' => 'All your base are belongs to us', 'date' => 1234567890)),
            );
        $result = $this->helper->listValuesArray($array, 'tips');
        $dom = new Zend_Dom_Query($result);
        $results = $dom->query('ul li');
        $this->assertEquals(count($array), count($results));
        $i = 0;
        foreach ($results as $line) {
            // assert link title equals; html entities are parsed into original value
            $currentArray = current($array[$i]);
            $this->assertContains($currentArray['text'], $line->nodeValue);
            // assert proper icon is present
            $this->assertEquals(key($array[$i]), $line->getElementsByTagName('img')->item(0)->getAttribute('alt'));
            $i++;
        }
    }

}

