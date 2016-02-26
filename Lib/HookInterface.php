<?php
/**
 * Freyja Hook API Interface.
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
 * @since 1.0.0
 */

namespace Freyja\Hook;

/**
 * Hook Interface.
 *
 * Defines standards for the creation of hooking functions and methods, which
 * will then be run when the hook is called.
 *
 * @package Freyja\Hook
 * @since 1.0.0
 * @author Mattia Migliorini <mattia@squeezyweb.com>
 * @version 1.0.0
 */
interface HookInterface {
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
	public function add( $tag, $callback, $priority = 10 );

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
	public function has( $tag, $callback = false );

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
	public function run( $tag, $value = null );

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
	public function remove( $tag, $callback, $priority = 10 );

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
	public function remove_all( $tag, $priority = false );

	/**
	 * Retrieve the name of the current hook.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Name of the current hook.
	 */
	public function getCurrent();

	/**
	 * Retrieve the name of a hook currently being processed.
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
	public function doing( $tag = null );

	/**
	 * Retrieve number of times a hook is run.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag The name of the hook.
	 * @return int Number of times the hook `$tag` is fired.
	 */
	public function did( $tag );
}
