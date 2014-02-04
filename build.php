<?php

namespace Typo3BaseBuilder;

class Build {

	protected $conf;

	public function __construct() {
		Base\Helper::clearScr();
		Base\Configuration::loadConfiguration();
	}

	public function init() {
		$conf = Base\Configuration::getConf();
		/**
		 * Set export type, SVN or GIT
		 */
		switch($conf['default']['exportType']) {
			case 'svn':
				$export = new Base\Svn($conf);
			break;
			case 'git':
				$export = new Base\Git($conf);
			break;
			default:
				trigger_error('The export type is not configured!' . LF . 'Please define default::exportType at your config.ini!'.LF ,E_USER_ERROR);
			break;
		}
		/**
		 * Set import type
		 */
		if($conf['default']['importType'] == $conf['default']['exportType']) {
			$import = $export;
		} else {
			switch($conf['default']['importType']) {
				case 'svn':
					$import = new Base\Svn($conf);
					break;
				case 'git':
					$import = new Base\Git($conf);
					break;
				default:
					trigger_error('The import type is not configured!' . LF . 'Please define default::importType at your config.ini!'.LF ,E_USER_ERROR);
					break;
			}
		}
		$export->exportExtension();
		$import->importExtension();
		Base\Helper::printLine('Extension build finished... Have fun (>\'.\')>', LINE);
	}
}