<?php
class JavascriptPackerTest extends PHPUnit_Framework_TestCase
{
    protected $filter;

    public function setUp()
    {
        parent::setUp();
        $this->filter = new GSAA_Filter_JavascriptPacker();
    }

    protected function tearDown() {
        unset($this->filter);
        parent::tearDown();
    }

    public function testBasicOutputWorks() {
        $script = 'alert("foo bar baz")';
        $output = $this->filter->filter($script);
        $this->assertContains($script, $output);
    }
    public function testCommentsRemovingWorks() {
        $output = $this->filter->filter('/* This is comment */ ' . "\n"
                                            . '//This is comment' . "\n"
                                            . 'alert("barbaz");');
        $this->assertNotContains('This is comment', $output);
        $this->assertContains('alert("barbaz")', $output);
    }
}