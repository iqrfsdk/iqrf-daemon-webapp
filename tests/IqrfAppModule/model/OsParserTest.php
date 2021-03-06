<?php

/**
 * TEST: App\IqrfAppModule\Model\OsParser
 * @covers App\IqrfAppModule\Model\OsParser
 * @phpVersion >= 7.0
 * @testCase
 */
declare(strict_types=1);

namespace Test\IqrfAppModule\Model;

use App\IqrfAppModule\Model\OsParser;
use App\Model\JsonFileManager;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

class OsParserTest extends TestCase {

	/**
	 * @var Container Nette Tester Container
	 */
	private $container;

	/**
	 * @var JsonFileManager JSON file manager
	 */
	private $jsonFileManager;

	/**
	 * @var OsParser DPA OS response parser
	 */
	private $parser;

	/**
	 * @var string OS Read HWP configuration packet
	 */
	private $packetHwpConfiguration;

	/**
	 * @var array Expected OS Read HWP configuration parsed response
	 */
	private $expectedHwpConfiguration;

	/**
	 * @var string OS Read info packet
	 */
	private $packetOsInfo;

	/**
	 * @var array Expected OS Read info parsed response
	 */
	private $expectedOsInfo;

	/**
	 * Constructor
	 * @param Container $container Nette Tester Container
	 */
	public function __construct(Container $container) {
		$this->container = $container;
	}

	/**
	 * Set up test environment
	 */
	public function setUp() {
		$this->parser = new OsParser();
		$this->jsonFileManager = new JsonFileManager(__DIR__ . '/data/');
		$this->packetHwpConfiguration = $this->jsonFileManager->read('response-os-hwp-config')['response'];
		$this->packetOsInfo = $this->jsonFileManager->read('response-os-read')['response'];
		$this->expectedHwpConfiguration = $this->jsonFileManager->read('data-os-hwp-config');
		$this->expectedOsInfo = $this->jsonFileManager->read('data-os-read');
	}

	/**
	 * @test
	 * Test function to parse DPA response
	 */
	public function testParse() {
		$arrayInfo = $this->parser->parse($this->packetOsInfo);
		Assert::equal($this->expectedOsInfo, $arrayInfo);
		$arrayConfig = $this->parser->parse($this->packetHwpConfiguration);
		Assert::equal($this->expectedHwpConfiguration, $arrayConfig);
	}

	/**
	 * @test
	 * Test function to parse response to DPA OS - "Read info" request
	 */
	public function testParseReadInfo() {
		$array = $this->parser->parseReadInfo($this->packetOsInfo);
		Assert::equal($this->expectedOsInfo, $array);
		$failPacket = preg_replace('/\.24\./', '\.ff\.', $this->packetOsInfo);
		$failArray = $this->parser->parseReadInfo($failPacket);
		$failExpected = $this->expectedOsInfo;
		$failExpected['TrType'] = $failExpected['McuType'] = 'UNKNOWN';
		Assert::equal($failExpected, $failArray);
	}

	/**
	 * @test
	 * Test function to get RF band from HWP configuration
	 */
	public function testGetRfBand() {
		Assert::equal('868 MHz', $this->parser->getRfBand('30'));
		Assert::equal('916 MHz', $this->parser->getRfBand('31'));
		Assert::equal('433 MHz', $this->parser->getRfBand('32'));
	}

	/**
	 * @test
	 * Test function to parse response to DPA OS - "Read HWP configuration" request
	 */
	public function testParseHwpConfiguration() {
		$actual = $this->parser->parseHwpConfiguration($this->packetHwpConfiguration);
		Assert::same($this->expectedHwpConfiguration, $actual);
	}

}

$test = new OsParserTest($container);
$test->run();
