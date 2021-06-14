<?php
namespace DirProcessCopy\PluginHandler;

/**
 * PluginHandler
 *
 * @package DirProcessCopy
 * @author Andy Kirk
 * @copyright Copyright (c) 2021
 * @version 0.1
 **/
class PluginHandler
{
    /**
     * Stores the config data
     * @var array
     **/
    protected $c = [];

    /**
	 * Stores a list of all input filetypes found in the plugins.
	 *
	 * @var array
	 * @access protected
	 **/
	public $input_filetypes = [];

    /**
	 * Stores a list of all filetypes found in the plugins.
	 *
	 * @var array
	 * @access protected
	 **/
	public $output_filetypes = [];

    /**
	 * The plugins objects.
	 *
	 * @var array
	 * @access protected
	 **/
	public $handlers = [];

    /**
     * PluginHandler::__construct()
     *
     * @param array $config
     * @param object $plugin_handler
     **/
    public function __construct($config)
    {
        $this->c = $config;
        $this->registerHandlers();
    }

    /**
	 * PluginHandler::registerHandlers()
	 *
	 * Checks config for listed handlers.
	 * Then analyses the new objects for valid input filetypes and stores these
	 * as an array so when each event is triggered, all matching input filetypes
	 * will be called without any need for further analysis loops.
	 *
	 * @return bool
	 * @access protected
	 **/
	protected function registerHandlers()
	{
        #require_once(dirname(dirname(dirname(__DIR__))) . '/vendor/akirk/dirprocesscopyhandlertwig/DirProcessCopyHandlerTwig.php');
        #$t = new \DirProcessCopyHandlerTwig\DirProcessCopyHandlerTwig($this->c);
        #echo 'registerHandlers<pre>'; var_dump(get_declared_classes()); echo '</pre>'; exit;
		$handlers = $this->c['dpc_handlers'];
		foreach ($handlers as $handler_name) {

			$handler_classname = '\DirProcessCopyHandler' . $handler_name . '\DirProcessCopyHandler' . $handler_name;
            #echo 'registerHandlers<pre>'; var_dump($handler_classname); echo '</pre>'; exit;
			$handler = new $handler_classname($this->c);
			$this->handlers[$handler_name] = $handler;

            // Note we currently only allow for one handler for each filetype to avoid conflicts:
            $this->input_filetypes[$handler->getInputExtension()] = $handler_name;
            $this->output_filetypes[] = $handler->getOutputExtension();
		}
	}
}