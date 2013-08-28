#!/usr/bin/env php
<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

require_once __DIR__ . '/../../bootstrap.php';

$runner = new Ustream_Daemon_Runner();

$runner->runDaemon(
	__DIR__ . '/run',
	\Ustream\DaemonTest\DaemonTestBase::CONTEXT,
	__DIR__ . '/ini',
	array()
);

?>