<?

namespace rude;

class exception
{
	public static function error     ($message, $file = null, $line = null, $args = null) { return static::trigger($message, $file, $line, $args, RUDE_E_ERROR);      }
	public static function warning   ($message, $file = null, $line = null, $args = null) { return static::trigger($message, $file, $line, $args, RUDE_E_WARNING);    }
	public static function notice    ($message, $file = null, $line = null, $args = null) { return static::trigger($message, $file, $line, $args, RUDE_E_NOTICE);     }
	public static function deprecated($message, $file = null, $line = null, $args = null) { return static::trigger($message, $file, $line, $args, RUDE_E_DEPRECATED); }
	public static function strict    ($message, $file = null, $line = null, $args = null) { return static::trigger($message, $file, $line, $args, RUDE_E_STRICT);     }
	public static function unknown   ($message, $file = null, $line = null, $args = null) { return static::trigger($message, $file, $line, $args, RUDE_E_UNKNOWN);    }

	private static function trigger($error_message, $error_file = null, $error_line = null, $error_args = null, $error_type = null)
	{
		$errors = static::errors($error_message, $error_file, $error_line, $error_args, $error_type);

		     if (RUDE_CLI) { static::cli ($errors); }
		else               { static::html($errors); }

		if ($error_type == RUDE_E_ERROR)
		{
			die;
		}

		return true;
	}

	public static function handler_error($error_id, $error_message, $error_file, $error_line, $error_args)
	{
		     if (static::is_error     ($error_id)) { static::error     ($error_message, $error_file, $error_line, $error_args); }
		else if (static::is_warning   ($error_id)) { static::warning   ($error_message, $error_file, $error_line, $error_args); }
		else if (static::is_notice    ($error_id)) { static::notice    ($error_message, $error_file, $error_line, $error_args); }
		else if (static::is_deprecated($error_id)) { static::deprecated($error_message, $error_file, $error_line, $error_args); }
		else if (static::is_strict    ($error_id)) { static::strict    ($error_message, $error_file, $error_line, $error_args); }
		else                                       { static::unknown   ($error_message, $error_file, $error_line, $error_args); }

		return true;
	}

//	public static function handler_exception($e)
//	{
//		debug($e, true);
//
//		exit;
//	}

//	private static function get_error_name($error_id)
//	{
//		$errors =
//		[
//				'E_ERROR'             => 1,
//				'E_WARNING'           => 2,
//				'E_PARSE'             => 4,
//				'E_NOTICE'            => 8,
//				'E_CORE_ERROR'        => 16,
//				'E_CORE_WARNING'      => 32,
//				'E_COMPILE_ERROR'     => 64,
//				'E_COMPILE_WARNING'   => 128,
//				'E_USER_ERROR'        => 256,
//				'E_USER_WARNING'      => 512,
//				'E_USER_NOTICE'       => 1024,
//				'E_STRICT'            => 2048,
//				'E_RECOVERABLE_ERROR' => 4096,
//				'E_DEPRECATED'        => 8192,
//				'E_USER_DEPRECATED'   => 16384
//		];
//
//		foreach ($errors as $key => $val)
//		{
//			if ($val == $error_id)
//			{
//				$error_name = char::remove_first($key, 2);
//				$error_name = strings::replace($error_name, '_', ' ');
//
//				return $error_name;
//			}
//		}
//
//		return 'UNKNOWN ERROR';
//	}

	public static function backtrace()
	{
		$backtrace      = debug_backtrace();
//		$backtrace      = items::reverse($backtrace, true);
		$backtrace      = items::remove_first($backtrace, 4);
		$backtrace_size = count($backtrace);


		$result = [];

		foreach ($backtrace as $index => $caller)
		{
			$args = $caller['args'];

			unset($caller['args']);

			$caller = items::to_object($caller);
			$caller->args = $args;

			$result[$backtrace_size - $index] = $caller;
		}

		return $result;
	}

	public static function errors($error_message, $error_file = null, $error_line = null, $error_args = null, $error_type = null)
	{
		if ($error_file !== null and
		    $error_line !== null and
		    $error_args !== null and
		    $error_type !== null)
		{
			return [static::format($error_message, $error_file, $error_line, $error_args, $error_type)];
		}


		$result = [];

		$backtrace      = static::backtrace();
		$backtrace_size = count($backtrace);

		foreach ($backtrace as $index => $caller)
		{
			     if ($backtrace_size == $index) { $type = $error_type;    }
			else                                { $type = RUDE_E_SUCCESS; }

			     if (isset($caller->class, $caller->type)) { $method = $caller->class . $caller->type . $caller->function; }
			else                                           { $method = $caller->function;                                  }

			$result[] = static::format($error_message, $caller->file, $caller->line, $caller->args, $type, $method, $index);
		}

		return $result;
	}

	private static function format($error_message, $error_file = null, $error_line = null, $error_args = null, $error_type = null, $error_method = null, $error_index = null)
	{
		$error = new \stdClass();
		$error->message = $error_message;
		$error->file    = $error_file;
		$error->line    = $error_line;
		$error->args    = $error_args;
		$error->type    = $error_type;
		$error->method  = $error_method;
		$error->index   = $error_index;

		return $error;
	}

	private static function cli($errors)
	{
		console::lines();

		$error = items::first($errors);
		$error = static::colorize($error);

		if (count($errors) == 1)
		{
			console::log("$error->message called at $error->file:$error->line", $error->type);
		}
		else
		{
			console::log("$error->message", $error->type);

			console::lines();

			$errors = static::colorize_array($errors);

			foreach ($errors as $error)
			{
				console::write("$error->index $error->status $error->method called at $error->file:$error->line");
			}
		}

		console::lines();
		console::write();
	}

	private static function html($errors)
	{
		$error = items::first($errors);
		$error = static::colorize($error);

		$buffer = '<hr>';

		if (count($errors) == 1)
		{
			$buffer .= "<b>$error->type:</b> $error->message called at $error->file:$error->line";

			$buffer .= '<hr>';
		}
		else
		{
			$buffer .= "<b>$error->type:</b> $error->message" . PHP_EOL . PHP_EOL;

			$errors = static::colorize_array($errors);

			foreach ($errors as $error)
			{
				$buffer .= "$error->index $error->status $error->method called at $error->file:$error->line" . PHP_EOL;
			}

			$buffer .= '<hr>';
		}

		echo "<pre>$buffer</pre>";
	}

	private static function colorize_array($errors)
	{
		$errors = items::pad_left($errors, 'index');

		foreach ($errors as $index => $error)
		{
			$errors[$index] = static::colorize($error);
		}

		$errors = items::pad_right($errors, 'status');
		$errors = items::pad_right($errors, 'method');

		return $errors;
	}

	private static function colorize($error)
	{
		$error = clone $error; # as of PHP 5, all objects are passed and assigned by reference

		if (RUDE_CLI)
		{
			$error->line   = console_colors::bold($error->line);
			$error->index  = console_colors::bold("$error->index.");
			$error->status = console_colors::format("[$error->type]", console_colors::get_error_color($error->type), null, true);
		}
		else
		{
			$error->line = "<b>$error->line</b>";
			$error->index = "<b>$error->index.</b>";

			     if (exception::is_success($error->type)) { $span_color = '#008000'; }
			else                                          { $span_color = '#FF0000'; }

			$error->status = "<span style=\"color: $span_color\"><b>[$error->type]</b></span>";
		}

		$error->method = $error->method . '()';

		if (strings::contains($error->file, RUDE_DIR))
		{
			$error->file = '.' . strings::read_after($error->file, RUDE_DIR);
		}

		return $error;
	}

	public static function is_error     ($error_id) { return items::contains([RUDE_E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], $error_id); }
	public static function is_warning   ($error_id) { return items::contains([RUDE_E_WARNING, E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING],            $error_id); }
	public static function is_notice    ($error_id) { return items::contains([RUDE_E_NOTICE, E_NOTICE, E_USER_NOTICE],                                                  $error_id); }
	public static function is_deprecated($error_id) { return items::contains([RUDE_E_DEPRECATED, E_DEPRECATED, E_USER_DEPRECATED],                                      $error_id); }
	public static function is_strict    ($error_id) { return items::contains([RUDE_E_STRICT, E_STRICT],                                                                 $error_id); }
	public static function is_success   ($error_id) { return items::contains([RUDE_E_SUCCESS],                                                                          $error_id); }
}