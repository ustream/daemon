<?php
/**
 * @author oker <takacs.zsolt@ustream.tv>
 */

$standardClassLoader = new Ustream_Autoload_StandardAutoload();
$standardClassLoader->addPathWithClassPrefix(__DIR__.'/src', 'Ustream_Daemon');
$standardClassLoader->register(true);

?>