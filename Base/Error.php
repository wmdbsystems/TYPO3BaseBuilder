<?php
namespace Typo3BaseBuilder\Base;


class Error {

	public static function errorHandler() {
		$error = error_get_last();
		switch($error['type']) {
			case E_ERROR: // 1 //
				var_dump($error);
				return 'E_ERROR';
			case E_WARNING: // 2 //
				var_dump($error);
				return 'E_WARNING';
			case E_PARSE: // 4 //
				var_dump($error);
				return 'E_PARSE';
			case E_NOTICE: // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';
			case E_CORE_ERROR: // 64 //
				var_dump($error);
				return 'E_COMPILE_ERROR';
			case E_CORE_WARNING: // 128 //
				var_dump($error);
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR: // 256 //
				echo LF . $error['message'] . LF;
				Helper::clearScr();
				Helper::printLine('', LINE);
				Helper::printLine($error['message'], LINE);
				return 'E_USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}
		return true;
	}

}