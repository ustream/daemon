#!/usr/bin/env php
<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

require_once __DIR__ . '/../../bootstrap.php';

class MITF implements Ustream_Daemon_MultiInstanceTaskFactory
{
	/**
	 * @param Ustream_Daemon_Daemon $daemon
	 * @param int $instanceId
	 * @param int $instanceCount
	 *
	 * @return Ustream_Daemon_Task
	 */
	public function create(Ustream_Daemon_Daemon $daemon, $instanceId, $instanceCount)
	{
		return new Ustream_Daemon_CallbackTask(
			function () use ($instanceId, $instanceCount, $daemon) {
				$logger = new Ustream_Daemon_Logger($daemon);
				$logger->info(sprintf('Instance %d/%d', $instanceId, $instanceCount));
			}
		);
	}
}

$config = new Ustream_Daemon_Config();
$config->runDir = __DIR__.'/run';
$config->customDaemonUtilLogFile = __DIR__.'/run/daemon_util.log';
$config->logDir = __DIR__.'/run';
$config->context = \Ustream\DaemonTest\DaemonTestBase::CONTEXT;

$builder = new Ustream_Daemon_Builder($config);
$builder
	->setFileNameAsIdentifier('multiinstance')
	->sleepBetweenRuns(0.1)
	->buildMultiInstance(new MITF())->start();

?>