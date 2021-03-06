<?php
/**
 * Freyja Hook API.
 *
 * This API allows for creating hooking functions and methods. The functions or
 * methods will then be run when the hook is called.
 *
 * The API callback example reference functions, but can be methods of classes.
 * To hook methods, you'll need to pass an array one of two ways.
 *
 * Any of the syntaxes explained in the PHP documentation for the
 * {@link(callback, http://us2.php.net/manual/en/language.pseudo-types.php#language.types.callback)}
 * type are valid.
 *
 * This API is based on the KYSS Hook API by Mattia Migliorini and Nicola Dalla
 * Costa.
 *
 * @package Freyja\Hook
 * @copyright 2016 SqueezyWeb
 * @author Mattia Migliorini <mattia@squeezyweb.com>
 * @since 1.0.0
 */

namespace Freyja\Hook;

/**
 * Hook Class.
 *
 * Handles the creation of hooking functions and methods, which will then be run
 * when the hook is called.
 *
 * @package Freyja\Hook
 * @since 1.0.0
 * @author Mattia Migliorini <mattia@squeezyweb.com>
 * @version 1.0.0
 */
class Hook implements HookInterface {
	/**
	 * Singleton instance of this class.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var Hook
	 */
	protected static $instance = null;

	/**
	 * Hook names.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $hooks = array();

	/**
	 * Hooks to be merged for later.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $merged = array();

	/**
	 * List of current hooks with current one last.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $current = array();

	/**
	 * Amount of times a hook was triggered.
	 *
	 * This is an associative array $tag => $number.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $triggered = array();

	/**
	 * Private constructor.
	 *
	 * This is a singleton class. You MUST instantiate it via the
	 * `Hook::getInstance()` method.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Retrieve singleton instance of this class.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return self
	 */
	public static function getInstance() {
		if ( is_null( self::$instance ) )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Hook a function or method to a specific hook.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The name of the hook to add the $callback to.
	 * @param callback $callback The callback to be run when the hook is run.
	 * @param int $priority Optional. The order in which the functions associated
	 * with a particular hook are executed. Lower numbers correspond to earlier
	 * execution, and functions with the same priority are executed in the order
	 * in which they were added to the hook. Default <10>.
	 * @return self.
	 */
	public function add( $tag, $callback, $priority = 10 ) {
		$this->hooks[$tag][$priority][] = $callback;
		unset($this->merged[$tag]);
		return $this;
	}

	/**
	 * Check if any function has been registered for a hook.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The name of the hook.
	 * @param callback $callback Optional. Specific function to check for.
	 * @return mixed If callback is omitted, returns boolean for whether the hook
	 * has anything registered. When checking a specific function, the priority of
	 * that hook is returned, or is false if the function is not attached.
	 * When using the $callback argument, this method may return a non-boolean
	 * value that evaluates to false (e.g. 0), so use the `===` operator for
	 * testing the return value.
	 */
	public function has( $tag, $callback = false ) {
		$has = ! empty( $this->hooks[$tag] );

		// Return early if we already know it's false.
		if ( false === $callback || false === $has )
			return $has;

		// Cycle through the hook priorities to find the callback we're looking for.
		foreach ( (array) array_keys( $this->hooks[$tag] ) as $priority )
			if ( in_array( $callback, $this->hooks[$tag][$priority] ) )
				return $priority;

		return false;
	}

	/**
	 * Call functions added to a hook.
	 *
	 * The callback functions attached to the hook $tag are invoked by calling
	 * this method. This function can be used to create a new hook by simply
	 * calling this method with the name of the new hook specified using the $tag
	 * parameter.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The name of the hook.
	 * @param mixed $value Optional. The value on which the functions hooked to
	 * $tag are applied on.
	 * @return mixed The filtered value after all hooked functions are applied to
	 * it, if any. Null if no value.
	 */
	public function run( $tag, $value = null ) {
		if (!isset( $this->triggered[$tag]))
			$this->triggered[$tag] = 1;
		else
			++$this->triggered[$tag];
		if (!isset($this->hooks[$tag]))
			return $value;

		$this->current[] = $tag;

		// Sort by priority.
		if (!isset($this->merged[$tag])) {
			ksort($this->hooks[$tag]);
			$this->merged[$tag] = true;
		}

		reset($this->hooks[$tag]);

		// array( $tag, $value, [additional arguments] );
		$args = func_get_args();

		do {
			foreach ((array) current($this->hooks[$tag]) as $callback)
				if (!is_null($callback))
					$value = call_user_func_array($callback, array_merge(array($value), array_slice( $args, 2)));
		} while (next($this->hooks[$tag]) !== false);

		array_pop($this->current);

		return $value;
	}

	/**
	 * Remove a function from a specified hook.
	 *
	 * This method can be used to remove default functions attached to a specific
	 * hook and possibly replace them with a substitute.
	 *
	 * To remove a hook, the `$callback` and `$priority` arguments must match when
	 * the hook was added.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The hook to which the function to be removed is attached.
	 * @param callback $callback The name of the function to remove.
	 * @param int $priority Optional. The priority of the function. Default <10>.
	 * @return bool Whether the function existed before it was removed.
	 */
	public function remove( $tag, $callback, $priority = 10 ) {
		if (!array_key_exists($tag, $this->hooks) || !array_key_exists($priority, $this->hooks[$tag]))
			return false;

		$position = array_search($callback, $this->hooks[$tag][$priority]);

		if (false === $position)
			return false;

		unset($this->hooks[$tag][$priority][$position]);

		if (empty($this->hooks[$tag][$priority]))
			unset($this->hooks[$tag][$priority]);

		unset($this->merged[$tag]);

		return true;
	}

	/**
	 * Remove all functions from a hook.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The hook to remove functions from.
	 * @param int $priority Optional. The priority number to remove.
	 * @return bool True when finished.
	 */
	public function removeAll( $tag, $priority = false ) {
		if ( isset( $this->hooks[$tag] ) ) {
			if ( false !== $priority && isset( $this->hooks[$tag][$priority] ) )
				unset( $this->hooks[$tag][$priority] );
			else
				unset( $this->hooks[$tag] );
		}

		if ( isset( $this->merged[$tag] ) )
			unset( $this->merged[$tag] );

		return true;
	}

	/**
	 * Retrieve the name of the current hook.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Name of the current hook.
	 */
	public function getCurrent() {
		return end( $this->current );
	}

	/**
	 * Whether a hook is currently being processed.
	 *
	 * The method `Hook::getCurrent()` only returns the most recent hook being
	 * executed. `Hook::did()` returns true once the hook is initially processed.
	 * This method allows detection for any hook currently being executed (despite
	 * not being the most recent hook to fire, in the case of hooks called from
	 * hook callbacks) to be verified.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see Hook::getCurrent()
	 * @see Hook::did()
	 *
	 * @param null|string $tag Optional. Name of the hook to check. If null, checks
	 * if any hook is currently being run. Default <null>.
	 * @return bool Whether the hook is currently in the stack.
	 */
	public function doing( $tag = null ) {
		if (null === $tag)
			return !empty($this->current);

		return in_array($tag, $this->current);
	}

	/**
	 * Retrieve number of times a hook is run.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The name of the hook.
	 * @return int Number of times the hook `$tag` is fired.
	 */
	public function did( $tag ) {
		if ( ! isset( $this->triggered[$tag] ) )
			return 0;
		return $this->triggered[$tag];
	}
}
