<?php

class PHPProcess {

  /**
   * 
   * @var resource
   */
  protected $_process_handle;

  /**
   * Default process descriptors
   * 
   * @var array
   */
  protected $_descriptors = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("pipe", "w")
  );

  /**
   * PHP config path
   * 
   * @var string
   */
  public $config_path;

  /**
   * Working dir
   * 
   * @var string
   */
  public $cwd;

  /**
   * Env properties
   * 
   * @var array
   */
  public $env;

  /**
   * Create process
   * 
   * @param string $config_path
   * @param string $cwd
   * @param array  $env
   */
  public function __construct($config_path, $cwd, $env = array()) {
    $this->config_path = $config_path;
    $this->cwd = $cwd;
    $this->env = $env;
  }

  /**
   * Run some code through php process and return response
   * as array.
   * 
   * @param  string $code
   * @return array
   */
  public function run($code) {
    if(null === $this->_process_handle) {
      $this->_process_handle = $this->_create_process($this->config_path, $this->_descriptors, $pipes, $this->cwd, $this->env);
    }

    // Write to stdin
    fwrite($pipes[0], $code);
    fclose($pipes[0]);

    // Get result
    $result = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Get any errors if occured
    $errors = stream_get_contents($pipes[2]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $exit_value = proc_close($this->_process_handle);

    return array(
      'status' => $exit_value,
      'result' => $result,
      'errors' => $errors,
    );
  }

  /**
   * Creates php process
   * 
   * @param  string         $config_path 
   * @param  array          $descriptors 
   * @param  $pipes
   * @param  string         $cwd
   * @param  array          $env
   * @return resource|null
   */
  protected function _create_process($config_path, $descriptors, &$pipes, $cwd, $env) {
    $command = 'php -c ' . $config_path;
    $process = proc_open($command, $descriptors, $pipes, $cwd, $env); 
    
    if(is_resource($process)) {
      return $process;
    }

    throw new Exception("Couldn't create process.");
  }

}

?>
