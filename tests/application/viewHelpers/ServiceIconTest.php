<?php
class ServiceIconTest extends PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp() {
        parent::setUp();
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $this->bootstrap->bootstrap();

        $this->view = new Zend_View();
        $this->helper = new GSAA_View_Helper_ServiceIcon();
        $this->helper->setView($this->view);
    }

    protected function tearDown() {
        unset($this->helper);
        parent::tearDown();
    }

    public function testRenderWorks() {
        $dom = new Zend_Dom_Query($this->helper->serviceIcon('fq'));
        $results = $dom->query('img');
        $this->assertEquals(1, count($results));
    }

    public function testRenderLinksProperImage() {
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

    public function testOutputIsValidHtml() {
        $output = $this->helper->serviceIcon('fb');
        $output .= $this->helper->serviceIcon('fq', 'left');
        $output .= $this->helper->serviceIcon('fq', 'right');
        $doc = new DOMDocument;
        $dom = $doc->loadHTML($output);
        $this->assertTrue($dom !== false);
    }
}

