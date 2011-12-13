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
        /*$dom = new Zend_Dom_Query($this->helper->serviceIcon('fq'));
        $results = $dom->query('img');
        $this->assertEquals(1, count($results));
*/

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
            $this->assertNotSame(false, strpos($line->nodeValue, current($array[$i++])));
        }
    }

    // TODO: test links
    // TODO: test tips
/*
    pubic function testRenderLinksProperImage() {
        $result = $this->helper->serviceIcon('fb');
        $this->assertNotSame(false, strpos($result, 'icon-fb.png'));

        $result = $this->helper->serviceIcon('gg', 'someClass');
        $this->assertNotSame(false, strpos($result, 'icon-gg.png'));
    }

    public function testRenderWithClassWorks() {
        $dom = new Zend_Dom_Query($this->helper->serviceIcon('fq', 'right'));
        $results = $dom->query('img.icon-right');
        $this->assertEquals(1, count($results));

        $dom = new Zend_Dom_Query($this->helper->serviceIcon('fq', 'left'));
        $results = $dom->query('img.icon-left');
        $this->assertEquals(1, count($results));
    }
 *
 */
}

