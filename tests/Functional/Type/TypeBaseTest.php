<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\TypeBase;
use Migrate\Processor\ProcessorInterface;
use Migrate\Processor\Nl2br;
use Migrate\Exception\ElementNotFoundException;
use Migrate\Exception\ValidationException;

class TypeBaseTest extends CrawlerTestCase {

  /**
   * Ensure that the value can be added.
   */
  public function testAddingValueToRow() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->getMockForAbstractClass();

    $type->addValueToRow('Test');
    $this->assertObjectHasAttribute('test', $row);
    $this->assertEquals('Test', $row->test);
  }

  /**
   * Ensure that an exception is thrown if field name is not set.
   */
  public function testAddingValueToRowException() {
    $row = new \stdClass();
    $config = [
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->getMockForAbstractClass();

    $this->expectException(ValidationException::class);
    $type->addValueToRow('Test');
  }

  /**
   * Ensure that valid processors are returned.
   */
  public function testProcessorDiscoveryValid() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
      'processors' => [
        'nl2br' => [],
        'whitespace' => [],
      ]
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->getMockForAbstractClass();

    $processors = $type->getProcessors();
    $this->assertTrue(count($processors) === 2);
    foreach ($processors as $processor) {
      $implements = class_implements($processor);
      $this->assertArrayHasKey(ProcessorInterface::class, $implements);
    }
  }

  /**
   * Ensure that an exception is thrown for invalid processors.
   */
  public function testProcessorDiscoveryInvalid() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
      'processors' => [
        'not-existant-processors' => [],
      ]
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->getMockForAbstractClass();

    $this->expectException(\Exception::class);
    $type->getProcessors();
  }

  /**
   * Ensure that the processor can be applied.
   */
  public function testProcessorApplication() {
    $processor = $this->getMockBuilder(Nl2br::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor->expects($this->once())
      ->method('process')
      ->with('Test')
      ->willReturn('Processor applied');

    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['getProcessors'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('getProcessors')
      ->willReturn([$processor]);

    $processed_value = $type->processValue('Test');

    $this->assertEquals('Processor applied', $processed_value);
  }

  public function testTheProcessorChain() {
    $processor1 = $this->getMockBuilder(Nl2br::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor1->expects($this->once())
      ->method('process')
      ->with('Test')
      ->willReturn('Processor applied');

    $processor2 = $this->getMockBuilder(Nl2br::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor2->expects($this->once())
      ->method('process')
      ->with('Processor applied')
      ->willReturn('Chained!');

    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['getProcessors'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('getProcessors')
      ->willReturn([$processor1, $processor2]);

    $processed_value = $type->processValue('Test');

    $this->assertEquals('Chained!', $processed_value);
  }

  /**
   * Test that DOM process is called if we specify a DOM selector.
   */
  public function testDomSelector() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['processDom'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('processDom')
      ->willReturn('Test');

    $return = $type->process();
    $this->assertEquals('Test', $return);
  }

  /**
   * Test that DOM process is called if we specify a DOM selector.
   */
  public function testXpathSelector() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '//*[@id="wrapper"]/div/h1',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['processXpath'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('processXpath')
      ->willReturn('Test');

    $return = $type->process();
    $this->assertEquals('Test', $return);
  }

  /**
   * Test invalid selector.
   */
  public function testInvalidSelectorAllowNull() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '//div[contains(@class, "not-found")]',
      'options' => ['allow_null' => TRUE],
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['nullValue'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('nullValue')
      ->willReturn('Test');

    $this->expectException(ElementNotFoundException::class);
    $return = $type->process();
    $this->assertEquals('Test', $return);
  }

  /**
   * Ensure that nullValue is not called if we haven't called it out.
   */
  public function testInvalidSelectorNotAllowNull() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '//div[contains(@class, "not-found")]',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['nullValue'])
      ->getMockForAbstractClass();

    $type->expects($this->never())->method('nullValue');

    $this->expectException(ElementNotFoundException::class);
    $return = $type->process();
    $this->assertEquals('Test', $return);
  }

  /**
   * Test that processDom is called if no selector is given.
   */
  public function testNoSelector() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['processDom'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('processDom')
      ->willReturn(TRUE);

    $type->process();
  }


}
