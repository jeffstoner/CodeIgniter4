<?php namespace CodeIgniter\Session;

use Config\Logger;
use Tests\Support\Log\TestLogger;
use Tests\Support\Session\MockSession;
use CodeIgniter\Session\Handlers\FileHandler;

class SessionTest extends \CIUnitTestCase
{
	public function setUp()
	{
		parent::setUp();

		$_COOKIE = [];
		$_SESSION = [];
	}

	public function tearDown()
	{

	}

	protected function getInstance($options = [])
	{
		$defaults = [
			'sessionDriver' => 'CodeIgniter\Session\Handlers\FileHandler',
			'sessionCookieName' => 'ci_session',
			'sessionExpiration' => 7200,
			'sessionSavePath' => null,
			'sessionMatchIP' => false,
			'sessionTimeToUpdate' => 300,
			'sessionRegenerateDestroy' => false,
			'cookieDomain' => '',
			'cookiePrefix' => '',
			'cookiePath' => '/',
			'cookieSecure' => false,
		];

		$config = array_merge($defaults, $options);
		$config = (object)$config;

		$session = new MockSession(new FileHandler($config), $config);
		$session->setLogger(new TestLogger(new Logger()));

		return $session;
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSessionSetsRegenerateTime()
	{
		$session = $this->getInstance();
		$session->start();

		$this->assertTrue(isset($_SESSION['__ci_last_regenerate']) && !empty($_SESSION['__ci_last_regenerate']));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testWillRegenerateSessionAutomatically()
	{
		$session = $this->getInstance();

		$time = time() - 400;
		$_SESSION['__ci_last_regenerate'] = $time;
		$session->start();

		$this->assertTrue($session->didRegenerate);
		$this->assertGreaterThan($time + 90, $_SESSION['__ci_last_regenerate']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCanSetSingleValue()
	{
		$session = $this->getInstance();
		$session->start();

		$session->set('foo', 'bar');

		$this->assertEquals('bar', $_SESSION['foo']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCanSetArray()
	{
		$session = $this->getInstance();
		$session->start();

		$session->set([
			'foo' => 'bar',
			'bar' => 'baz'
		]);

		$this->assertEquals('bar', $_SESSION['foo']);
		$this->assertEquals('baz', $_SESSION['bar']);
		$this->assertArrayNotHasKey('__ci_vars', $_SESSION);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetSimpleKey()
	{
		$session = $this->getInstance();
		$session->start();

		$session->set('foo', 'bar');

		$this->assertEquals('bar', $session->get('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetReturnsNullWhenNotFound()
	{
		$_SESSION = [];

		$session = $this->getInstance();
		$session->start();

		$this->assertNull($session->get('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetReturnsAllWithNoKeys()
	{
		$_SESSION = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session = $this->getInstance();
		$session->start();

		$result = $session->get();

		$this->assertTrue(array_key_exists('foo', $result));
		$this->assertTrue(array_key_exists('bar', $result));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetAsProperty()
	{
		$session = $this->getInstance();
		$session->start();

		$session->set('foo', 'bar');

		$this->assertEquals('bar', $session->foo);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetAsNormal()
	{
		$session = $this->getInstance();
		$session->start();

		$session->set('foo', 'bar');

		$this->assertEquals('bar', $_SESSION['foo']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testHasReturnsTrueOnSuccess()
	{
		$session = $this->getInstance();
		$session->start();

		$_SESSION['foo'] = 'bar';

		$this->assertTrue($session->has('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testHasReturnsFalseOnNotFound()
	{
		$session = $this->getInstance();
		$session->start();

		$_SESSION['foo'] = 'bar';

		$this->assertFalse($session->has('bar'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRemoveActuallyRemoves()
	{
		$session = $this->getInstance();
		$session->start();

		$_SESSION['foo'] = 'bar';
		$session->remove('foo');

		$this->assertArrayNotHasKey('foo', $_SESSION);
		$this->assertFalse($session->has('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testHasReturnsCanRemoveArray()
	{
		$session = $this->getInstance();
		$session->start();

		$_SESSION = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$this->assertTrue($session->has('foo'));

		$session->remove(['foo', 'bar']);

		$this->assertArrayNotHasKey('foo', $_SESSION);
		$this->assertArrayNotHasKey('bar', $_SESSION);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetMagicMethod()
	{
		$session = $this->getInstance();
		$session->start();

		$session->foo = 'bar';

		$this->assertArrayHasKey('foo', $_SESSION);
		$this->assertEquals('bar', $_SESSION['foo']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCanFlashData()
	{
		$session = $this->getInstance();
		$session->start();

		$session->setFlashdata('foo', 'bar');

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('new', $_SESSION['__ci_vars']['foo']);

		// Should reset the 'new' to 'old'
		$session->start();

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('old', $_SESSION['__ci_vars']['foo']);

		// Should no longer be available
		$session->start();

		$this->assertFalse($session->has('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCanFlashArray()
	{
		$session = $this->getInstance();
		$session->start();

		$session->setFlashdata([
			'foo' => 'bar',
			'bar' => 'baz'
		]);

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('new', $_SESSION['__ci_vars']['foo']);
		$this->assertTrue($session->has('bar'));
		$this->assertEquals('new', $_SESSION['__ci_vars']['bar']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testKeepFlashData()
	{
		$session = $this->getInstance();
		$session->start();

		$session->setFlashdata('foo', 'bar');

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('new', $_SESSION['__ci_vars']['foo']);

		// Should reset the 'new' to 'old'
		$session->start();

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('old', $_SESSION['__ci_vars']['foo']);

		$session->keepFlashdata('foo');

		$this->assertEquals('new', $_SESSION['__ci_vars']['foo']);

		// Should no longer be available
		$session->start();

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('old', $_SESSION['__ci_vars']['foo']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testUnmarkFlashDataRemovesData()
	{
		$session = $this->getInstance();
		$session->start();

		$session->setFlashdata('foo', 'bar');
		$session->set('bar', 'baz');

		$this->assertTrue($session->has('foo'));
		$this->assertArrayHasKey('foo', $_SESSION['__ci_vars']);

		$session->unmarkFlashdata('foo');

		// Should still be here
		$this->assertTrue($session->has('foo'));
		// but no longer marked as flash
		$this->assertFalse(isset($_SESSION['__ci_vars']['foo']));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetFlashKeysOnlyReturnsFlashKeys()
	{
		$session = $this->getInstance();
		$session->start();

		$session->setFlashdata('foo', 'bar');
		$session->set('bar', 'baz');

		$keys = $session->getFlashKeys();

		$this->assertContains('foo', $keys);
		$this->assertNotContains('bar', $keys);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetTempDataWorks()
	{
		$session = $this->getInstance();
		$session->start();

		$session->setTempdata('foo', 'bar', 300);
		$this->assertGreaterThanOrEqual($_SESSION['__ci_vars']['foo'], time() + 300);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetTempDataArrayMultiTTL()
	{
		$session = $this->getInstance();
		$session->start();

		$time = time();

		$session->setTempdata([
			'foo' => 300,
			'bar' => 400,
			'baz' => 100
		]);

		$this->assertLessThanOrEqual($_SESSION['__ci_vars']['foo'], $time + 300);
		$this->assertLessThanOrEqual($_SESSION['__ci_vars']['bar'], $time + 400);
		$this->assertLessThanOrEqual($_SESSION['__ci_vars']['baz'], $time + 100);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetTempDataArraySingleTTL()
	{
		$session = $this->getInstance();
		$session->start();

		$time = time();

		$session->setTempdata(['foo', 'bar', 'baz'], null, 200);

		$this->assertLessThanOrEqual($_SESSION['__ci_vars']['foo'], $time + 200);
		$this->assertLessThanOrEqual($_SESSION['__ci_vars']['bar'], $time + 200);
		$this->assertLessThanOrEqual($_SESSION['__ci_vars']['baz'], $time + 200);
	}

	/**
	 * @group single
	 * @runInSeparateProcess
	 */
	public function testGetTestDataReturnsAll()
	{
		$session = $this->getInstance();
		$session->start();

		$data = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session->setTempdata($data);
		$session->set('baz', 'ballywhoo');

		$this->assertEquals($data, $session->getTempdata());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetTestDataReturnsSingle()
	{
		$session = $this->getInstance();
		$session->start();

		$data = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session->setTempdata($data);

		$this->assertEquals('bar', $session->getTempdata('foo'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRemoveTempDataActuallyDeletes()
	{
		$session = $this->getInstance();
		$session->start();

		$data = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session->setTempdata($data);
		$session->removeTempdata('foo');

		$this->assertEquals(['bar' => 'baz'], $session->getTempdata());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testUnMarkTempDataSingle()
	{
		$session = $this->getInstance();
		$session->start();

		$data = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session->setTempdata($data);
		$session->unmarkTempdata('foo');

		$this->assertEquals(['bar' => 'baz'], $session->getTempdata());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testUnMarkTempDataArray()
	{
		$session = $this->getInstance();
		$session->start();

		$data = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session->setTempdata($data);
		$session->unmarkTempdata(['foo', 'bar']);

		$this->assertEquals([], $session->getTempdata());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetTempdataKeys()
	{
		$session = $this->getInstance();
		$session->start();

		$data = [
			'foo' => 'bar',
			'bar' => 'baz'
		];

		$session->setTempdata($data);
		$session->set('baz', 'ballywhoo');

		$this->assertEquals(['foo', 'bar'], $session->getTempKeys());
	}
}
