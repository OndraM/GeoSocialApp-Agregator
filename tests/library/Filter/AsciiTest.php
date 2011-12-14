<?php
class AsciiTest extends PHPUnit_Framework_TestCase
{
    protected $filter;

    public function setUp()
    {
        parent::setUp();
        $this->filter = new GSAA_Filter_ASCII();
    }

    protected function tearDown() {
        unset($this->helper);
        parent::tearDown();
    }

    public function testBasic() {
        $valuesExpected = array(
                'ěščřžýáíéůďťň' => 'escrzyaieudtn',
                'ĚŠČŘŽÝÁÍÉŮĎŤŇ' => 'ESCRZYAIEUDTN',
                'abc 123'       => 'abc 123',
                'Übälüß'        => 'Uebaeluess',
                ''              => ''
                );

        foreach ($valuesExpected as $input => $output) {
            $this->assertEquals(
                $output,
                $result = $this->filter->filter($input),
                "Expected '$input' to filter to '$output', but received '$result' instead"
                );
        }
    }
}