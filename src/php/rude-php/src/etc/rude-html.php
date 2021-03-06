<?

namespace rude;

class html
{
	public static function escape($string)
	{
		return htmlspecialchars($string);
	}

	public static function unescape($string)
	{
		return htmlspecialchars_decode($string);
	}

	public static function js($path)
	{
		if (defined('RUDE_AUTOVERSION') and RUDE_AUTOVERSION)
		{
			$path .= '?v=' . RUDE_VERSION;
		}

		return '<script src="' . $path . '" type="text/javascript"></script>';
	}

	public static function css($path)
	{
		if (defined('RUDE_AUTOVERSION') and RUDE_AUTOVERSION)
		{
			$path .= '?v=' . RUDE_VERSION;
		}

		return '<link href="' . $path . '" rel="stylesheet" type="text/css">';
	}

	public static function close_tags($html)
	{
		# http://stackoverflow.com/a/10988758 (solution - comments, do not use code from the answer)

		$doc = new \DOMDocument();

		libxml_use_internal_errors(true);

		$doc->loadHTML('<?xml version="1.0" encoding="UTF-8"?><html_tags>' . $html . '</html_tags>');

		libxml_clear_errors();

		$html = substr($doc->saveXML($doc->getElementsByTagName('html_tags')->item(0)), strlen('<html_tags>'), -strlen('</html_tags>'));

		libxml_use_internal_errors(false);

		$html = html_entity_decode($html);

		return $html;
	}
}