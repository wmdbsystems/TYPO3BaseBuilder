<?php
namespace Typo3BaseBuilder\Base;

class Configuration {

	protected static $conf;
	protected static $file;
	protected static $shortOptions = array(
		'k:' => array(
			'name' => 'k:',
			'type' => 'String'
		),
		't::' => array(
			'name' => 't::',
			'type' => 'String'
		),
		'h::' => array(
			'name' => 'h::',
			'type' => 'Boolean'
		),
		'novcs::' => array(
			'name' => 'novcs::',
			'type' => 'Boolean'
		),
		'b::' => array(
			'name' => 'b::',
			'type' => 'String'
		)
	);
	protected static $longOptions = array('extkey:' => 'k:', 'type::' => 't::', 'help::' => 'h::', 'baseurl::' => 'b::');

	public static function loadConfiguration(){
		self::$file = BASE . 'config.ini';
		self::$conf = parse_ini_file(self::$file, true);
		self::$conf['default']['exportType'] = strtolower(self::$conf['default']['exportType']);
		self::$conf['default']['importType'] = strtolower(self::$conf['default']['importType']);
		self::parseArguments();
		Helper::printLine('Configuration and arguments successful parsed.');
	}

	protected static function parseArguments() {
		$args = $GLOBALS['argv'];
		$options = array();
		array_shift($args);
		$key = '';
		if(count($args) <= 0) {
			trigger_error('No parameters given!' . LF . 'type --help to get an List of valid parameters.'.LF ,E_USER_ERROR);
		}
		$toReplace = 1;
		foreach($args AS $arg) {
			if(strpos($arg, '--') === 0) {
				$arg = str_replace('--', '',$arg, $toReplace);
				$key = $arg . '::';
				if(isset(self::$longOptions[$key])) {
					$key = self::$shortOptions[self::$longOptions[$key]];
				} else {
					$key = isset(self::$shortOptions[$key]) !== false ? self::$shortOptions[$key] : false;
				}
				$options[$key['name']] = true;
			} elseif(strpos($arg, '-') === 0) {
				$arg = str_replace('-', '',$arg, $toReplace);
				$key = $arg . ':';
				$key = isset(self::$longOptions[$key]) ? self::$shortOptions[self::$longOptions[$key]] : isset(self::$shortOptions[$key]) !== false ? self::$shortOptions[$key] : false;
				$options[$key['name']] = true;
			} elseif(isset($options[$key['name']])) {
				$options[$key['name']] = $arg;
				$key = '';
			} else {
				$key = false;
			}
			if($key === false) {
				trigger_error( '"' . $arg . '" is not a valid parameter' . LF . 'type --help to get an List of valid parameters.'.LF ,E_USER_ERROR);
			}
		}
		$options = self::validateParsedArguments($options);
		self::$conf['params'] = $options;
	}

	protected static function validateParsedArguments($options){
		foreach($options AS $key => $value) {
			$setup = self::$shortOptions[$key];
			switch($setup['type']) {
				case 'String':
					if(!is_string($value)) {
						trigger_error( '"' . $key . $value . '" is not a string value!' . LF . 'type --help to get an List of valid parameters.'.LF ,E_USER_ERROR);
					}
					break;
				case 'Boolean':
					if(filter_var($value, FILTER_VALIDATE_BOOLEAN) === false) {
						trigger_error( '"' . $key . $value . '" is not a boolean value!' . LF . 'type --help to get an List of valid parameters.'.LF ,E_USER_ERROR);
					}
					break;
				case 'Numeric':
					if(!is_numeric($value)) {
						trigger_error( '"' . $key . $value . '" is not a numeric value!' . LF . 'type --help to get an List of valid parameters.'.LF ,E_USER_ERROR);
					}
					break;
			}
		}

		/** Ignore validation if help is calling and call help instead */
		if(isset($options['h::'])) {
			Helper::printHelp();
		} else {
			/** Check if required arguments are parsed */
			foreach(self::$shortOptions AS $key => $option) {
				if(strpos($key, ':') >= 0 && strpos($key, '::') === false) {
					if(!isset($options[$key])) {
						trigger_error( '"' . $key . '" is required but not set!' . LF . 'type --help to get an List of required parameters.'.LF ,E_USER_ERROR);
					}
				}
			}
		}
		return $options;
	}

	public static function getConf(){
		return self::$conf;
	}
}