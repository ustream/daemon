#!/usr/bin/env php
<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

require_once __DIR__ . '/../../bootstrap.php';

class SITF implements Ustream_Daemon_TaskFactory
{
	/**
	 * @param Ustream_Daemon_Daemon $daemon
	 *
	 * @return Ustream_Daemon_Task
	 */
	public function createTaskFor(Ustream_Daemon_Daemon $daemon)
	{
		return new Ustream_Daemon_CallbackTask(function () use ($daemon) {
			$logger = new Ustream_Daemon_Logger($daemon);
			$logger->info(sprintf('Single instance'));
		});
	}
}

$config = new Ustream_Daemon_Config();
$config->runDir = __DIR__.'/run';
$config->customDaemonUtilLogFile = __DIR__.'/run/daemon_util.log';
$config->logDir = __DIR__.'/run';
$config->context = \Ustream\DaemonTest\DaemonTestBase::CONTEXT;

$builder = new Ustream_Daemon_Builder($config);
$builder
	->setFileNameAsIdentifier(sprintf('singleinstance'))
	->sleepBetweenRuns(0.1)
	->build(new SITF())->start();

?>