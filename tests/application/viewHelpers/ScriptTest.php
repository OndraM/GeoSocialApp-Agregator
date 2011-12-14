<?php
class ScriptTest extends PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp() {
        parent::setUp();
        $this->view = new Zend_View(array(
            'helperPath' => array('GSAA_View_Helper' => APPLICATION_PATH . '/views/helpers')
        ));
        $this->helper = new GSAA_View_Helper_Script();
        $this->helper->setView($this->view);
    }

    protected function tearDown() {
        unset($this->helper);
        parent::tearDown();
    }

    public function testInplaceRenderWorks() {
        $output = $this->helper->script('alert("foo")', true);
        $this->assertContains('alert("foo")', $output);
    }

    public function testIfAddedToHeadNoOutputIsRendered() {
        $output = $this->helper->script('alert("foo")', false);
        $this->assertEquals(null, $output);
    }

    public function testAddingScriptToHeadWorks() {
        $this->helper->script('alert("foo")');
        $output = $this->view->headScript()->toString();
        $this->assertContains('alert("foo")', $output);
    }

    public function testEmptyScriptDontProduceOutput() {
        $output = $this->helper->script('', true);
        $this->assertEquals(null, $output);
    }

    public function testNotPackedScriptContainsComments() {
        $output = $this->helper->script('/* This is comment */ ' . "\n"
                                            . '//This is comment' . "\n"
                                            . 'alert("barbaz");',
                                        true, false);
        $this->assertEquals(2, substr_count($output, 'This is comment'));
        $this->assertContains('alert("barbaz")', $output);
    }
    public function testPackedScriptDontContainsComments() {
        $output = $this->helper->script('/* This is comment */ ' . "\n"
                                            . '//This is comment' . "\n"
                                            . 'alert("barbaz");',
                                        true, true);
        $this->assertNotContains('This is comment', $output);
        $this->assertContains('alert("barbaz")', $output);
    }
}

