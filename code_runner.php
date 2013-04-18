<?php

require_once "php_process.php";

class CodeRunner {
  /**
   * 
   * @var string
   */
  public $cwd = null;

  /**
   * 
   * @var resource
   */
  protected $_process;

  /**
   * 
   */
  public function __construct() {
    $this->cwd = dirname(__FILE__);
  }

  /**
   * Runs some php code through php process
   * 
   * @param  string $code
   * @return array
   */
  public function run_code($code) {
    return $this->_get_process()->run($code);
  }

  /**
   * Returns PHP config path
   * 
   * @return string
   */
  public function get_config_path() {
    return '/home/'. $this->_discover_username() .'/html/php/php.ini';
  }

  private function _discover_username() {
    $cwd = __DIR__;
    $username = 'wzalewski';
    if(preg_match('@^/home/([^/]*)/@', $cwd, $match)){
      $username = $match[1];
    }
    return $username;
  }

  /**
   * Returns process class instance
   * 
   * @return PHPProcess
   */
  protected function _get_process() {
    if(null == $this->_process) {
      $this->_process = new PHPProcess($this->get_config_path(), $this->cwd, array());
    }
    return $this->_process;
  }

}


?>
