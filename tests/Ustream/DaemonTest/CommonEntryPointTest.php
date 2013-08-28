<?php
/**
 * @author pepov <pepov@ustream.tv>
 */
namespace Ustream\DaemonTest;

/**
 * CommonEntryPointTest
 */
class CommonEntryPointTest extends DaemonTestBase
{
	const PIDFILE = '%s/run/common-entry-point-%s.pid';
	const LOGFILE = '/tmp/common-entry-point-%s.log';

	/**
	 * @var DaemonControl
	 */
	private $daemonControl;

	/**
	 * @var int
	 */
	private $pid;

	/**
	 * @static
	 * @return \Ustream_Daemon_CallbackTask
	 */
	public static function createTask()
	{
		return new \Ustream_Daemon_CallbackTask(function () {
			\Ustream_Daemon_Logger::getInstance()->info('common-entry-point');
		});
	}

	/**
	 * @test
	 */
	public function runsWithCommonEntryPoint()
	{
		$this->daemonControl->start();

		RetryAssertion::wait()
			->times(100)
			->sleeping(100, RetryAssertion::MILLISEC)
			->until(array($this, 'checkPid'));

		$this->daemonControl->stop();

		RetryAssertion::wait()
			->times(100)
			->sleeping(100, RetryAssertion::MILLISEC)
			->until(array($this, 'checkPidFileGone'));

		$this->checkLogFiles();
	}

	/**
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->daemonControl = new DaemonControl(
			sprintf(__DIR__.'/entrypoint.php --id "%s"', 'common-entry-point'),
			$this->getPidFileName()
		);
	}

	/**
	 * @return string
	 */
	protected function getLogFile()
	{
		return sprintf(self::LOGFILE, self::CONTEXT);
	}

	/**
	 * @return string
	 */
	protected function getCommonLog()
	{
		return '/tmp/common.log';
	}

	private function checkLogFiles()
	{
		$this->assertTrue(is_file($this->getLogFile()), $this->getLogFile() . " doesn't exist!");
		$this->assertTrue(is_file($this->getCommonLog()), $this->getCommonLog() . " doesn't exist!");
		$logFileContent = file_get_contents($this->getLogFile());
		$this->assertRegExp('@common-entry-point@', $logFileContent);

		$this->assertRegExp(
			sprintf(
				'@releaseDaemon unlinking pidFile! pidfileLocation: %s | Pid is %d.@',
				$this->getPidFileName(),
				$this->pid
			),
			file_get_contents($this->getCommonLog())
		);
	}

	public function checkPidFileGone()
	{
		$pidFile = $this->getPidFileName();
		$this->assertFileNotExists($pidFile, sprintf('Pidfile %s must be removed after stop', $pidFile));
	}

	public function checkPid()
	{
		$pidFile = $this->getPidFileName();
		$this->assertPidFileExists($pidFile);
		$this->pid = $this->daemonControl->getPid();
		$this->assertPid($this->pid, $pidFile);
	}

	private function getPidFileName()
	{
		return sprintf(self::PIDFILE, __DIR__, self::CONTEXT);
	}
}

?>