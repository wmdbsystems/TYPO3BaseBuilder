<?php
namespace Typo3BaseBuilder\Base;

class Svn {
	/**
	 * @var array $conf configuration Array
	 */
	protected $conf = array();

	public function __construct(array $conf=array()){
		if(count($conf) > 0) {
			$this->conf = $conf;
		} else {
			trigger_error('Configuration array is empty...' . LF . 'Please check your configurations!'.LF ,E_USER_ERROR);
		}

		$this->configureExportPath();
		$this->conf['user']['username'] = trim(shell_exec("read -p 'Enter user SVN username: ' username\necho \$username"));
		/** We dont want that someone can read the user password */
		echo 'Enter user SVN password: ';
		system('stty -echo');
		$this->conf['user']['password'] = trim(fgets(STDIN));
		system('stty echo');
	}

	public function exportExtension() {
		Helper::printLine(LF . LF . 'Starting export process from SVN ...', LINE);
		$extKey = $this->conf['params']['k:'];
		$exportPath = $this->conf['svn']['exportRepos'];
		$importRepos = $this->conf['svn']['importRepos'];
		$completeImportPath = $importRepos . $extKey . '/trunk/';

		if($exportPath == '') {
			trigger_error('No export path configured. ' . LF . 'Please check your configurations!'.LF ,E_USER_ERROR);
		} elseif(!Helper::urlExists($exportPath)) {
			trigger_error('The export repository url "' . $exportPath . '" is not valid! ' . LF . 'Please check your configurations!'.LF ,E_USER_ERROR);
		}
		if(is_dir($_SERVER['PWD'] . '/' . $this->conf['params']['k:'])) {
			trigger_error('The extension "' . $this->conf['params']['k:'] . '" already exists! ' . LF ,E_USER_ERROR);
		}

		if(!isset($this->conf['params']['novcs::'])) {
			if(!Helper::urlExists($completeImportPath)) {
				trigger_error('The import repository url "' . $completeImportPath . '" is not valid! ' . LF . 'Please check your configurations!'.LF ,E_USER_ERROR);
			}
			exec('svn ls --username ' . $this->conf['user']['username'] . ' --password ' . $this->conf['user']['password'] . ' --no-auth-cache ' . $completeImportPath, $out, $e);
			if(intval($e) === 0 && is_array($out) && count($out) > 0 ) {
				trigger_error('The extension "' . $extKey . '" aleready exists in SVN repos "' .  $importRepos . '"', E_USER_ERROR);
			}
		}

		Helper::printLine('Exporting from "' . $exportPath . '" ...');
		shell_exec('svn export --username ' . $this->conf['user']['username'] . ' --password ' . $this->conf['user']['password'] . ' --no-auth-cache -q ' . $exportPath . ' ' . $this->conf['params']['k:']);
		Helper::replaceNecessaryStringsAndFiles($this->conf['params']['k:'], $this->conf['params']['b::']);
	}

	public function importExtension(){
		$extKey = $this->conf['params']['k:'];
		$importPath = $this->conf['svn']['importRepos'];
		$completeImportPath = $importPath . $extKey . '/trunk/';
		Helper::printLine(LF . 'Starting import process from SVN ...', LINE);
		if(isset($this->conf['params']['novcs::'])) {
			Helper::printLine('No VCS was set, skipping import process...');
		} else {
			if($importPath == '') {
				trigger_error('No import path configured. ' . LF . 'Please check your configurations!'.LF ,E_USER_ERROR);
			} elseif(!Helper::urlExists($importPath)) {
				trigger_error('The import repository url "' . $importPath . '" is not valid! ' . LF . 'Please check your configurations!'.LF ,E_USER_ERROR);
			}
			exec('svn ls --username ' . $this->conf['user']['username'] . ' --password ' . $this->conf['user']['password'] . ' --no-auth-cache ' . $completeImportPath, $out, $e);
			if($e == 0 && is_array($out) && count($out) > 0 ) {
				trigger_error('The extension "' . $extKey . '" aleready exists in SVN repos "' .  $importPath . '"', E_USER_ERROR);
			}
			Helper::printLine('Import extenion "' . $extKey . '" ...');
			shell_exec('svn import -m "Initial import of the extension: ' . $extKey . '" --username ' . $this->conf['user']['username'] . ' --password ' . $this->conf['user']['password'] . ' --no-auth-cache -q ./' . $extKey . '/ ' . $completeImportPath);
			Helper::printLine('Move extension "' . $extKey . '" to "' . $extKey . '_" ...');
			shell_exec('mv ' . $extKey . ' ' . $extKey . '_');
			Helper::printLine('Checkout extenion "' . $extKey . '" ...');
			shell_exec('svn checkout --username ' . $this->conf['user']['username'] . ' --password ' . $this->conf['user']['password'] . ' --no-auth-cache -q ' . $completeImportPath . ' ./' . $extKey);
			Helper::printLine('Remove backup....');
			shell_exec('rm -rf ./' . $extKey . '_');
		}
	}


	public function configureExportPath(){
		$exportPath = $this->conf['svn']['exportRepos'];
		$exportPath = substr($exportPath, -1) !== '/' ? $exportPath . '/' : $exportPath;

		$branch = isset($this->conf['params']['branch::']) ? trim($this->conf['params']['branch::']) : false;
		$tag = isset($this->conf['params']['tag::']) ? trim($this->conf['params']['tag::']) : false;
		$defaultFallback = isset($this->conf['default']['exportVersion']) && trim($this->conf['default']['exportVersion']) != '' ?
							$this->conf['default']['exportVersion'] : false;

		if( $tag ) {
			$exportPath = $exportPath . 'tags/' . $tag;
		} elseif( $branch ) {
			$exportPath = $exportPath . 'branches/' . $branch;
		} elseif( $defaultFallback ) {
			$exportPath = $exportPath . $defaultFallback;
		} else {
			$exportPath = $exportPath . 'trunk/';
		}
		$this->conf['svn']['exportRepos'] = $exportPath;
	}
}