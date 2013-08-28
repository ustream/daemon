Ustream_Daemon [![Build Status](https://secure.travis-ci.org/ustream/daemon.png)](http://travis-ci.org/ustream/daemon?branch=master)
============

`Ustream_Daemon` is a library for running php code as daemons. It works around php's limitations in this area and
implements some sane default behaviors. The main goal is to provide a robust way of running php code continously while
decoupling the business logic from the details of running a background task. We hate superflous boilerplate like everyone
else so the daemons have a common entry point.

Download
--------

Github: http://github.com/ustream/daemon/tree/master

Composer:

    composer require ustream/daemon:~0.1.0


Versioning
----------

We follow the guidelines on http://semver.org

Features
--------

* contexts
* extensible via symfony event dispatcher

Usage
-----

We recommend using the library with a single entry point per project. Creating this entry point is up to you, it should
bootstrap your application as you need it, then call the daemon runner class:
```
require 'bootstrap.php';

$runner = new Ustream_Daemon_Runner();

$runner->runDaemon(
	__DIR__ . '/run', // run directory, pids are stored here
	'production',     // context it is used to suffix log filenames and pidfiles
	__DIR__ . '/ini', // ini directory, the configuration files are stored here
	array()           // event listeners
);
```

You can start a daemon by calling this entry point with the --id="daemon-name" parameter. The config file daemon-name.ini is
used from the provided ini directory to configure the daemon.

Daemon configuration
--------------------

The most important is to specify a factory method, which returns an object implementing the `Ustream_Daemon_Task` interface. The interface itself has only one method, `doTask()` - you should specify what the daemon should periodically do in this method. This is the only required element of the ini file, the others are optional.

* factory : A string specifying a class and static factory method to get the task
* sleep : An integer, specifying the period length of the `doTask()` runs. The daemon waits this many seconds (substracting the real execution length of the `doTask()` method, so it normally won't accumulate delay)
* min-sleep : Another integer value in seconds, specifying the minimum break between two runs. This is useful if the task itself causes something (like system load for example) which needs a bit break even if there would be not enough time before the next run.
* memory-limit : Specified as the php shorthand notation for bytes (see [the manual](http://hu1.php.net/manual/en/faq.using.php#faq.using.shorthandbytes) ). This will be set as `memory_limit` via `ini_set`
* memory-threshold : A value between 0 and 100. It specifies the part of the above memory-limit which is allowed to be used by the daemon, in percents. The runner checks the memory usage after each run and exits the process if it is exceeded.
* log-dir : A path to a directory where the daemon should put its log file.
* common-log : A path to a common log file where the daemon handler itself writes some logs (like when it starts or stops a child process). Typical use case for it is to have one file which is used by all daemons and logs their starts and stops.

An example configuration ini file:
```
factory=Ustream\EditorialMetrics\LogProcessor::create
sleep=0
min-sleep=0
memory-limit=256M
memory-threshold=70
log-dir=/var/log/custom_php/
common-log=/var/log/custom_php/daemon_util.log
```

Events
------

The daemon dispatches two events. One on startup, and one on each completion of its task. The event identifier constants are defined in the `Ustream_Daemon_Event` class. You can add listeners to these events via the `addListeners()` method. An example:

```
$daemon->addListeners(array(
			Ustream_Daemon_Event::START => array(
				array($onStartListener, 'onStart'),
			),
			Ustream_Daemon_Event::TASK_DONE => array(
				array($onTaskDoneListener, 'onTaskDone'),
			)
		);
```

The events are dispatched via symfony's event dispatcher, which basically means that the  listener object's specified method will be called with an event object as parameter. More info about this in the [symfony manual](http://symfony.com/doc/current/components/event_dispatcher/introduction.html)

Contributing
------------

Please see CONTRIBUTING.md for details.

Credits
-------

Ustream_Daemon is maintained by [ustream.tv, inc](http://ustream.tv/)

Authors:
* [Tamas Ecsedi](https://github.com/ecsy)
* [Zoltan Nemeth](https://github.com/syntaxerror13)
* [Gergely Hodicska](https://github.com/felho)
* Zoltan Szabo
* [Zsolt Takacs](https://github.com/oker1)
* [Peter Wilcsinszky](https://github.com/pepov)

License
-------

Ustream_Daemon is Copyright © 2013 Ustream Inc. It is free software, and may be redistributed under the terms specified in the LICENSE file.