<?php
/**
 * Tests for Hook class.
 *
 * @package Freyja\Hook\Tests
 * @copyright 2016 SqueezyWeb
 * @since 1.0.0
 */

use Freyja\Hook\Hook;

/**
 * Test Hook class.
 *
 * @package Freyja\Hook\Tests
 * @author Mattia Migliorini <mattia@squeezyweb.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class HookTest extends PHPUnit_Framework_TestCase {
	/**
	 * Tear down.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function tearDown() {
		$hook = Hook::getInstance();
		$r = new ReflectionObject($hook);
		$p = $r->getProperty('instance');
		$p->setAccessible(true);
		$p->setValue(null);
	}

	/**
	 * Test getInstance() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testGetInstance() {
		$hook = Hook::getInstance();

		$this->assertTrue(is_a($hook, 'Freyja\Hook\Hook'), 'getInstance() instantiates and returns a new Hook object');

		$new_hook = Hook::getInstance();
		$this->assertSame($hook, $new_hook, 'getInstance() always returns the same instance of Hook');
	}

	/**
	 * Test add() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testAdd() {
		$hook = Hook::getInstance();
		$r = new ReflectionObject($hook);
		$p = $r->getProperty('hooks');
		$p->setAccessible(true);

		$hook->add('foo', 'fooCallback', 10);
		$expected = array(
			'foo' => array(
				10 => array(
					'fooCallback'
				)
			)
		);
		$this->assertEquals($p->getValue($hook), $expected, 'add() correctly registers a new hook');

		$hook->add('foo', 'barCallback');
		$expected = array(
			'foo' => array(
				10 => array(
					'fooCallback',
					'barCallback'
				)
			)
		);
		$this->assertEquals($p->getValue($hook), $expected, 'add() has 10 as default priority and registers a new hook without overriding existing callbacks');

		$return = $hook->add('foo', 'gizCallback');
		$this->assertSame($return, $hook, 'add() returns the Hook instance in use');
	}

	/**
	 * Test has() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testHas() {
		$hook = Hook::getInstance();

		$has = $hook->has('foo');
		$this->assertSame(false, $has, 'has() returns false if no callback has been registered');

		$hook->add('foo', 'fooCallback', 90);
		$has = $hook->has('foo');
		$this->assertTrue($has, 'has() returns true if at least one callback has been registered for given tag');

		$has = $hook->has('foo', 'fooCallback');
		$this->assertSame(90, $has, 'has() returns the priority if the specified callback has been registered for the given tag');

		$has = $hook->has('foo', 'barCallback');
		$this->assertSame(false, $has, 'has() returns false if the specified callback has not been registered for the given tag');

		$hook->add('bar', 'barCallback');
		$has = $hook->has('bar', 'fooCallback');
		$this->assertSame(false, $has, 'has() returns false if the specified callback has been registered, but not for the given tag');
	}

	/**
	 * Test run() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testRun() {
		$hook = Hook::getInstance();
		$hook->add('foo', function($string) {
			return $string.'foo';
		});
		$result = $hook->run('foo', '');
		$this->assertEquals('foo', $result, 'run() executes the only one callback attached to the specified hook');

		$hook->add('bar', function($string) {
			return $string.'bar';
		});
		$result = $hook->run('foo', '');
		$this->assertEquals('foo', $result, 'run() executes only functions attached to a hook');

		$hook->add('foo', function($string) {
			return $string.'foo1';
		});
		$result = $hook->run('foo', '');
		$this->assertEquals('foofoo1', $result, 'run() executes functions in the order they are attached to the hook when priority is the same');

		$hook->add('foo', function($string) {
			return $string.'late';
		}, 999);
		$hook->add('foo', function($string) {
			return $string.'early';
		}, 1);
		$result = $hook->run('foo', '');
		$this->assertEquals('earlyfoofoo1late', $result, 'run() executes functions by priority');

		$result = $hook->run('giz', 'giz');
		$this->assertEquals('giz', $result, 'run() returns the original value if the hook does not have functions attached');
	}

	/**
	 * Test remove() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testRemove() {
		$hook = Hook::getInstance();
		$hook->add('foo', 'fooCallback');
		$hook->add('foo', 'barCallback', 20);

		$hook->remove('foo', 'fooCallback');
		$this->assertSame(false, $hook->has('foo', 'fooCallback'), 'remove() removes a callback with default priority');

		$this->assertFalse($hook->remove('foo', 'barCallback'), 'remove() returns false when the specified callback has not the given priority');
		$this->assertSame(20, $hook->has('foo', 'barCallback'), 'remove() does not remove a callback if it is not in the given priority');

		$hook->remove('foo', 'barCallback', 20);
		$this->assertSame(false, $hook->has('foo', 'barCallback'), 'remove() removes a callback with specified priority');

		$this->assertFalse($hook->remove('bar', 'barCallback'), 'remove() returns false when the specified tag does not exist');

		$this->assertFalse($hook->remove('foo', 'barCallback', 9999), 'remove() returns false when the specified priority has no callbacks');
	}

	/**
	 * Test has() after remove.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testHasAfterRemove() {
		$hook = Hook::getInstance();
		$hook->add('foo', 'fooCallback');
		$hook->remove('foo', 'fooCallback');

		$this->assertSame(false, $hook->has('foo', 'fooCallback'), 'has() returns false after the specified callback has been removed');
		$this->assertSame(false, $hook->has('foo'), 'has() returns false after all callbacks attached to a tag have been removed');
	}

	/**
	 * Test removeAll() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testRemoveAll() {
		$hook = Hook::getInstance();
		$hook->add('foo', 'fooCallback');
		$hook->add('foo', 'barCallback');

		$hook->removeAll('foo');
		$this->assertSame(false, $hook->has('foo'), 'removeAll() removes all functions from a hook');

		$hook->add('foo', 'fooCallback');
		$hook->add('foo', 'barCallback', 20);
		$hook->add('foo', 'bazCallback', 20);

		$hook->removeAll('foo', 20);
		$this->assertTrue(false === $hook->has('foo', 'barCallback') && false === $hook->has('foo', 'bazCallback'), 'removeAll() removes all functions in a hook with the specified priority');
		$this->assertSame(10, $hook->has('foo', 'fooCallback'), 'removeAll() does not remove functions with different priorities if priority is specified');
	}

	/**
	 * Test getCurrent() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testGetCurrent() {
		$hook = Hook::getInstance();

		$hook->add('foo', function() {
			echo Hook::getInstance()->getCurrent();
		});

		$this->expectOutputString('foo');
		$hook->run('foo');
	}

	/**
	 * Test getCurrent() method with nested hooks.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testGetCurrentNestedHooks() {
		$hook = Hook::getInstance();
		$hook->add('foo', function() {
			echo Hook::getInstance()->getCurrent();
		});
		$hook->add('bar', function() {
			Hook::getInstance()->run('foo');
		});

		$this->expectOutputString('foo');
		$hook->run('bar');
	}

	/**
	 * Test doing() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testDoing() {
		$hook = Hook::getInstance();
		$hook->add('foo', array($this, 'doingAssertions'));
		$hook->add('bar', function() {
			Hook::getInstance()->run('foo');
		});
	}

	/**
	 * Doing assertions.
	 *
	 * This method is used to make some assertion on testDoing().
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function doingAssertions() {
		$hook = Hook::getInstance();

		$doing = $hook->doing();
		$this->assertTrue($doing, 'doing() returns true if at least one hook is being executed');

		$doing = $hook->doing('foo');
		$this->assertTrue($doing, 'doing() returns true if the specified hook is the last one being executed');

		$doing = $hook->doing('bar');
		$this->assertTrue($doing, 'doing() returns true if the specified hook is being executed, but not the most recent one');

		$doing = $hook->doing('baz');
		$this->assertFalse($doing, 'doing() returns false if the specified hook is not being executed');
	}

	/**
	 * Test did() method.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function testDid() {
		$hook = Hook::getInstance();
		$hook->add('foo', function() {});

		$did = $hook->did('foo');
		$this->assertEquals(0, $did, 'did() returns 0 if given hook has never been executed');

		$hook->run('foo');
		$did = $hook->did('foo');
		$this->assertEquals(1, $did, 'did() returns 1 the first time the given hook has been executed');

		$hook->run('foo');
		$did = $hook->did('foo');
		$this->assertEquals(2, $did, 'did() returns the number of times the given hook has been executed');
	}
}
