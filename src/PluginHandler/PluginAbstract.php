<?php
namespace DirProcessCopy\PluginHandler;
/**
 * PluginAbstract
 *
 * To be extended by any plugins.
 *
 * @package DirProcessCopy
 * @author Andy Kirk
 * @copyright Copyright (c) 2021
 * @version 0.1
 **/
abstract class PluginAbstract
{
    protected $config;

    protected $input_extension;
    protected $output_extension;

    /**
     * PluginAbstract::__construct()
     *
     * @param Array $config DirProcessCopy config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * PluginAbstract::getInputExtension()
     *
     */
    public function getInputExtension()
    {
        return $this->input_extension;
    }
    
    /**
     * PluginAbstract::getOutputExtension()
     *
     */
    public function getOutputExtension()
    {
        return $this->output_extension;
    }
}
