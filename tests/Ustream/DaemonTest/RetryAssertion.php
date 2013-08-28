<?php
/**
 * @author pepov <pepov@ustream.tv>
 */

namespace Ustream\DaemonTest;

use PHPUnit_Framework_ExpectationFailedException;

/**
 * RetryAssertion
 */
class RetryAssertion
{
	const MICROSEC = 1;
	const MILLISEC = 1000;
	const SEC = 1000000;

	/**
	 * @var int
	 */
	private $times = 1;

	/**
	 * @var int
	 */
	private $microsec = 0;

	/**
	 * @static
	 * @return RetryAssertion
	 */
	public static function wait()
	{
		return new RetryAssertion();
	}

	/**
	 * @param int $times
	 * @return RetryAssertion
	 */
	public function times($times)
	{
		$this->times = $times;
		return $this;
	}

	/**
	 * @param $time
	 * @param int $unit
	 * @return RetryAssertion
	 */
	public function sleeping($time, $unit = self::MICROSEC)
	{
		$this->microsec = $time * $unit;
		return $this;
	}

	/**
	 * @param callback $callback
	 * @throws PHPUnit_Framework_ExpectationFailedException
	 */
	public function until($callback)
	{
		if (!is_callable($callback)) {
			throw new PHPUnit_Framework_ExpectationFailedException('Callback is not callable!');
		}

		for ($i = 1; $i <= $this->times; $i++) {
			usleep($this->microsec);
			try {
				call_user_func($callback);
				break;
			} catch (PHPUnit_Framework_ExpectationFailedException $e) {
				if ($i === $this->times) {
					throw $e;
				}
			}
		}
	}
}

?>