<?php
/**
 
 
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SessionHandlerInterface
 *
 * PHP 5.4 compatibility interface
 *
  * @subpackage	Libraries
 * @category	Sessions
 * @author	   
  */
interface SessionHandlerInterface {

	public function open($save_path, $name);
	public function close();
	public function read($session_id);
	public function write($session_id, $session_data);
	public function destroy($session_id);
	public function gc($maxlifetime);
}
