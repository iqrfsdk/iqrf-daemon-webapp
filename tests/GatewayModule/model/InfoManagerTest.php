<?php

/**
 * TEST: App\Model\InfoManager
 * @covers App\Model\InfoManager
 * @phpVersion >= 7.0
 * @testCase
 */
declare(strict_types = 1);

namespace Test\Model;

use App\GatewayModule\Model\InfoManager;
use App\IqrfAppModule\Model\CoordinatorParser;
use App\IqrfAppModule\Model\EmptyResponseException;
use App\IqrfAppModule\Model\EnumerationParser;
use App\IqrfAppModule\Model\IqrfAppManager;
use App\IqrfAppModule\Model\OsParser;
use App\Model\CommandManager;
use App\Model\FileManager;
use App\Model\JsonFileManager;
use App\Model\VersionManager;
use DateTime;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

class InfoManagerTest extends TestCase {

	/**
	 * @var Container Nette Tester Container
	 */
	private $container;

	/**
	 * @var CoordinatorParser DPA Coordinator response parser
	 */
	private $coordinatorParser;

	/**
	 * @var EnumerationParser DPA Enumeration response parser
	 */
	private $enumParser;

	/**
	 * @var OsParser DPA OS response parser
	 */
	private $osParser;

	/**
	 * @var VersionManager Version manager
	 */
	private $versionManager;

	/**
	 * @var array Mocked commands
	 */
	private $commands = [
		'daemonVersion1' => 'iqrf_startup version',
		'deviceTreeName' => 'cat /proc/device-tree/model',
		'dmiBoardName' => 'cat /sys/class/dmi/id/board_name',
		'dmiBoardVendor' => 'cat /sys/class/dmi/id/board_vendor',
		'dmiBoardVersion' => 'cat /sys/class/dmi/id/board_version',
		'ipAddressesEth0' => 'ip a s eth0 | grep inet | grep global | grep -v temporary | awk \'{print $2}\'',
		'ipAddressesWlan0' => 'ip a s wlan0 | grep inet | grep global | grep -v temporary | awk \'{print $2}\'',
		'macAddresses' => 'cat /sys/class/net/eth0/address',
		'networkAdapters' => 'ls /sys/class/net | awk \'{ print $0 }\'',
		'gitBranches' => 'git branch -v --no-abbrev',
	];

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
		$this->coordinatorParser = new CoordinatorParser();
		$this->enumParser = new EnumerationParser();
		$this->osParser = new OsParser();
		$this->versionManager = new VersionManager(new CommandManager(false));
	}

	/**
	 * @test
	 * Test function to get board info
	 */
	public function testGetBoard() {
		$commandManager0 = \Mockery::mock(CommandManager::class);
		$commandManager0->shouldReceive('send')->with($this->commands['deviceTreeName'], true)->andReturn('Raspberry Pi 2 Model B Rev 1.1');
		$iqrfAppManager0 = new IqrfAppManager($commandManager0, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager0 = new InfoManager($commandManager0, $iqrfAppManager0, $this->versionManager);
		Assert::same('Raspberry Pi 2 Model B Rev 1.1', $gwInfoManager0->getBoard());
		$commandManager1 = \Mockery::mock(CommandManager::class);
		$commandManager1->shouldReceive('send')->with($this->commands['deviceTreeName'], true);
		$commandManager1->shouldReceive('send')->with($this->commands['dmiBoardVendor'], true)->andReturn('AAEON');
		$commandManager1->shouldReceive('send')->with($this->commands['dmiBoardName'], true)->andReturn('UP-APL01');
		$commandManager1->shouldReceive('send')->with($this->commands['dmiBoardVersion'], true)->andReturn('V0.4');
		$iqrfAppManager1 = new IqrfAppManager($commandManager1, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager1 = new InfoManager($commandManager1, $iqrfAppManager1, $this->versionManager);
		Assert::same('AAEON UP-APL01 (V0.4)', $gwInfoManager1->getBoard());
		$commandManager2 = \Mockery::mock(CommandManager::class);
		$commandManager2->shouldReceive('send')->with($this->commands['deviceTreeName'], true);
		$commandManager2->shouldReceive('send')->with($this->commands['dmiBoardVendor'], true);
		$commandManager2->shouldReceive('send')->with($this->commands['dmiBoardName'], true);
		$commandManager2->shouldReceive('send')->with($this->commands['dmiBoardVersion'], true);
		$iqrfAppManager2 = new IqrfAppManager($commandManager2, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager2 = new InfoManager($commandManager2, $iqrfAppManager2, $this->versionManager);
		Assert::same('UNKNOWN', $gwInfoManager2->getBoard());
	}

	/**
	 * @test
	 * Test function to get IPv4 and IPv6 addresses of the gateway
	 */
	public function testGetIpAddresses() {
		$commandManager = \Mockery::mock(CommandManager::class);
		$commandManager->shouldReceive('send')->with($this->commands['networkAdapters'], true)->andReturn('eth0' . PHP_EOL . 'wlan0' . PHP_EOL . 'lo');
		$commandManager->shouldReceive('send')->with($this->commands['ipAddressesEth0'], true)->andReturn('192.168.1.100' . PHP_EOL . 'fda9:d95:d5b1::64');
		$commandManager->shouldReceive('send')->with($this->commands['ipAddressesWlan0'], true)->andReturn('');
		$iqrfAppManager = new IqrfAppManager($commandManager, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager = new InfoManager($commandManager, $iqrfAppManager, $this->versionManager);
		Assert::same(['eth0' => ['192.168.1.100', 'fda9:d95:d5b1::64']], $gwInfoManager->getIpAddresses());
	}

	/**
	 * @test
	 * Test function to get MAC addresses of the gateway
	 */
	public function testGetMacAddresses() {
		$commandManager = \Mockery::mock(CommandManager::class);
		$commandManager->shouldReceive('send')->with($this->commands['networkAdapters'], true)->andReturn('eth0' . PHP_EOL . 'lo');
		$commandManager->shouldReceive('send')->with($this->commands['macAddresses'], true)->andReturn('01:02:03:04:05:06');
		$iqrfAppManager = new IqrfAppManager($commandManager, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager = new InfoManager($commandManager, $iqrfAppManager, $this->versionManager);
		Assert::same(['eth0' => '01:02:03:04:05:06'], $gwInfoManager->getMacAddresses());
	}

	/**
	 * @test
	 * Test function to get hostname of the gateway
	 */
	public function testGetHostname() {
		$commandManager = \Mockery::mock(CommandManager::class);
		$output = 'gateway';
		$commandManager->shouldReceive('send')->with('hostname -f')->andReturn($output);
		$iqrfAppManager = new IqrfAppManager($commandManager, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager = new InfoManager($commandManager, $iqrfAppManager, $this->versionManager);
		Assert::same($output, $gwInfoManager->getHostname());
	}

	/**
	 * @test
	 * Test function to get information about the Coordinator
	 */
	public function testGetCoordinatorInfo() {
		$commandManager0 = \Mockery::mock(CommandManager::class);
		$now = new DateTime();
		$cmdRead = 'iqrfapp readonly timeout 200';
		$cmd = 'iqrfapp "{\"ctype\":\"dpa\",\"type\":\"raw\",\"msgid\":\"'
				. $now->getTimestamp() . '\",\"request\":\"00.00.02.00.ff.ff\",'
				. '\"request_ts\":\"\",\"confirmation\":\"\",\"confirmation_ts\":\"\",'
				. '\"response\":\"\",\"response_ts\":\"\"}"';
		$fileManager = new FileManager(__DIR__ . '/../../IqrfAppModule/model/data/');
		$jsonFileManager = new JsonFileManager(__DIR__ . '/../../IqrfAppModule/model/data/');
		$coordinatorInfo = $fileManager->read('response-os-read.json');
		$commandManager0->shouldReceive('send')->with($cmdRead, true)->andReturn('Timeout');
		$commandManager0->shouldReceive('send')->with($cmd, true)->andReturn('Received: ' . $coordinatorInfo);
		$iqrfAppManager0 = new IqrfAppManager($commandManager0, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager0 = new InfoManager($commandManager0, $iqrfAppManager0, $this->versionManager);
		$expected = $jsonFileManager->read('data-os-read');
		Assert::same($expected, $gwInfoManager0->getCoordinatorInfo());
		$commandManager1 = \Mockery::mock(CommandManager::class);
		$commandManager1->shouldReceive('send')->with($cmdRead, true)->andReturn('Timeout');
		$commandManager1->shouldReceive('send')->with($cmd, true);
		$iqrfAppManager1 = new IqrfAppManager($commandManager1, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager1 = new InfoManager($commandManager1, $iqrfAppManager1, $this->versionManager);
		Assert::exception(function() use ($gwInfoManager1) {
			$gwInfoManager1->getCoordinatorInfo();
		}, EmptyResponseException::class);
	}

	/**
	 * @test
	 * Test function to get version of the daemon
	 */
	public function testGetDaemonVersion() {
		$version = '0.7.0-1';
		$commandManager0 = \Mockery::mock(CommandManager::class);
		$commandManager0->shouldReceive('commandExist')->with('iqrf_startup')->andReturn(false);
		$iqrfAppManager0 = new IqrfAppManager($commandManager0, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager0 = new InfoManager($commandManager0, $iqrfAppManager0, $this->versionManager);
		Assert::same('none', $gwInfoManager0->getDaemonVersion());
		$commandManager1 = \Mockery::mock(CommandManager::class);
		$commandManager1->shouldReceive('commandExist')->with('iqrf_startup')->andReturn(true);
		$commandManager1->shouldReceive('send')->with($this->commands['daemonVersion1'])->andReturn($version);
		$iqrfAppManager1 = new IqrfAppManager($commandManager1, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager1 = new InfoManager($commandManager1, $iqrfAppManager1, $this->versionManager);
		Assert::same($version, $gwInfoManager1->getDaemonVersion());
		$commandManager2 = \Mockery::mock(CommandManager::class);
		$commandManager2->shouldReceive('commandExist')->with('iqrf_startup')->andReturn(true);
		$commandManager2->shouldReceive('send')->with($this->commands['daemonVersion1']);
		$iqrfAppManager2 = new IqrfAppManager($commandManager2, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$gwInfoManager2 = new InfoManager($commandManager2, $iqrfAppManager2, $this->versionManager);
		Assert::same('unknown', $gwInfoManager2->getDaemonVersion());
	}

	/**
	 * @test
	 * Test function to get version of the webapp
	 */
	public function testGetWebAppVersion() {
		$version = 'v1.1.6';
		$commandManager0 = \Mockery::mock(CommandManager::class);
		$commandManager0->shouldReceive('commandExist')->with('git')->andReturn(false);
		$iqrfAppManager0 = new IqrfAppManager($commandManager0, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$versionManager0 = new VersionManager($commandManager0);
		$gwInfoManager0 = new InfoManager($commandManager0, $iqrfAppManager0, $versionManager0);
		Assert::same($version, $gwInfoManager0->getWebAppVersion());
		$gitBranches = '* master                 733d45340cbb2565fd068ca3257ad39a5e46f963 Add a notification to an update webapp to newer stable version';
		$commandManager1 = \Mockery::mock(CommandManager::class);
		$commandManager1->shouldReceive('commandExist')->with('git')->andReturn(true);
		$commandManager1->shouldReceive('send')->with($this->commands['gitBranches'])->andReturn($gitBranches);
		$iqrfAppManager1 = new IqrfAppManager($commandManager1, $this->coordinatorParser, $this->osParser, $this->enumParser);
		$versionManager1 = new VersionManager($commandManager1);
		$gwInfoManager1 = new InfoManager($commandManager1, $iqrfAppManager1, $versionManager1);
		Assert::same($version . ' (master - 733d45340cbb2565fd068ca3257ad39a5e46f963)', $gwInfoManager1->getWebAppVersion());
	}

}

$test = new InfoManagerTest($container);
$test->run();
