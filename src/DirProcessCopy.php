<?php
namespace DirProcessCopy;
/**
 * DirProcessCopy
 *
 * ...
 *
 * @package DirProcessCopy
 * @author Andy Kirk
 * @copyright Copyright (c) 2021
 * @version 0.1
 **/
class DirProcessCopy
{
    /**
     * Stores the config data
     * @var array
     **/
    protected $c = [];

    /**
     * Stores the default data
     * @var array
     **/
    protected $d = [];

    /**
     * DirProcessCopy::__construct()
     *
     * @param Array|String|Null $config  config array or path to file, or nothing (auto look for file)
     */
    public function __construct($c = null)
    {
        $config = null;

        // Check if we've given an array:
        if (is_array($c)) {
            $config = $c;
        }

        // If not check if it's a valid file path:
        if (empty($config)) {
            if (is_string($c) && file_exists($c)) {
                $config = require $c;
            } else {
                trigger_error('DirProcessCopy error: could not find config file: ' . $c, E_USER_ERROR);
            }
        }

        // If not, have a go at auto-detection on include paths:
        if (empty($config)) {
            $found = stream_resolve_include_path('config.php');
            if ($found !== false) {
                $config = require $found;
            }
        }

        // If there's still no config, give up:
        if (empty($config)) {
            trigger_error('DirProcessCopy error: no config provided. Expected array, file path, or existence of ' . __DIR__ . '/config.php', E_USER_ERROR);
            return false;
        }

        $this->c = $config;

        $this->plugin_handler = new \DirProcessCopy\PluginHandler\PluginHandler($this->c);
    }

    /**
     * DirProcessCopy::run()
     */
    public function run()
    {
        $c = $this->c;
        $input_dir       = $c['dpc_input_dir'];
        $dpc_process_dir = $c['dpc_process_dir'];
        $output_dir      = $c['dpc_output_dir'];

        // Empty the tmp directory:
        $this->empty_dir($dpc_process_dir);

        #exit;

        // Build a list of files to process:
        $filter_config = [
            'c'             => $c,
            'start_dir'     => $input_dir,
            'exclude'       => $c['dpc_input_exclude'],
            'is_processing' => true
        ];

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS;
        $directory = new \RecursiveDirectoryIterator($input_dir, $flags);
        $filter    = new DirProcessCopyFilterIterator($directory, $filter_config, $this->plugin_handler);
        $iterator  = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $info) {
            #echo '<hr>IN RUN 1<pre>'; var_dump($info->getPathname()); echo '</pre><hr>';
            $pathname = $info->getPathname();
            #echo '<hr>IN RUN 1<pre>'; var_dump($pathname); echo '</pre>';
            $tmp_pathname = str_replace($c['dpc_input_dir'], $c['dpc_process_dir'], $pathname);
            #echo 'IN RUN 2<pre>'; var_dump($tmp_pathname); echo '</pre><hr>';

            // Directories would already have been created by the Filter Iterator:
            if (is_dir($tmp_pathname)) {
                continue;
            }
            copy($pathname, $tmp_pathname);
        }

        // Clear out any files of types that we have a handler for:
        $output_filetypes = $this->plugin_handler->output_filetypes;
        $o_filter_config = [
            'c'         => $c,
            'start_dir' => $output_dir,
            'exclude'   => $c['dpc_output_exclude']
        ];

        $o_flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS;
        $o_directory = new \RecursiveDirectoryIterator($output_dir, $o_flags);
        $o_filter    = new DirProcessCopyFilterIterator($o_directory, $o_filter_config, $this->plugin_handler);
        $o_iterator  = new \RecursiveIteratorIterator($o_filter, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach($o_iterator as $o_file) {
            if (in_array($o_file->getExtension(), $output_filetypes)) {
                unlink($o_file->getRealPath());
            }
        }

        // Go through the list again and delete any empty dirs:
        foreach($o_iterator as $o_file) {
            $path = $o_file->getRealPath();
            if (is_dir($path) && file_exists($path) && count(scandir($path)) == 2) {
                rmdir($path);
            }
        }

        // Next, copy the tmp dir over.
        $t_flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS;
        $t_directory = new \RecursiveDirectoryIterator($dpc_process_dir, $t_flags);
        $t_iterator  = new \RecursiveIteratorIterator($t_directory, \RecursiveIteratorIterator::CHILD_FIRST);

        $tmp_files = [];

        // For this, we need to build a list ...
        foreach($t_iterator as $t_file) {
            $tmp_files[$t_file->getRealPath()] = $t_file;
        }

        // ... so we can sort it so that we can create dirs before copying files.
        ksort($tmp_files);

        // Now copy the files:
        foreach($tmp_files as $path => $t_file) {
            $output_path = str_replace($dpc_process_dir, $output_dir, $path);
            if ($t_file->isDir()) {
                if (!file_exists($output_path)) {
                    mkdir($output_path);
                }
            } else {
                copy($path, $output_path);
            }
        }


    }

    /**
     * DirProcessCopy::empty_dir()
     *
     * @param string $dir Path to the directory to be emptied
     */
    public function empty_dir($dir = '')
    {
        $filter_config = [
            'c'            => $this->c,
            'start_dir'    => $dir
        ];

        $e_flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS;
        $e_directory = new \RecursiveDirectoryIterator($dir, $e_flags);
        $e_filter    = new DirProcessCopyFilterIterator($e_directory, $filter_config, $this->plugin_handler);
        $e_iterator  = new \RecursiveIteratorIterator($e_filter, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach($e_iterator as $e_file) {
            if ($e_file->isDir()){
                rmdir($e_file->getRealPath());
            } else {
                unlink($e_file->getRealPath());
            }
        }
    }

}

class DirProcessCopyFilterIterator extends \RecursiveFilterIterator {

    /**
     * Stores the filter config data
     * @var array
     **/
    protected $filter_config = [];

    /**
     * Stores the filter config data
     * @var array
     **/
    protected $plugin_handler;

    /**
     * Stores the config data
     * @var array
     **/
    protected $c = [];

    /**
     * Stores the start dir
     * @var string
     **/
    protected $start_dir = '';

    /**
     * Stores the exclusion array
     * @var array
     **/
    protected $exclude = [];

    /**
     * Stores the is_processing setting
     * @var bool
     **/
    protected $is_processing = false;


    public function __construct(\RecursiveIterator $rit, array $filter_config = [], PluginHandler\PluginHandler $plugin_handler) {
        // A new filter is constructed for each item iterated over, so we need to store the full
        // config passed at initiation, so that getChildren() can pass it each time.
        $this->filter_config = $filter_config;
        $this->plugin_handler = $plugin_handler;

        foreach ($filter_config as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }

        parent::__construct($rit);
    }

    public function accept() {
        $c = $this->c;

        $filename = $this->current()->getFilename();
        $pathname = $this->current()->getPathname();

        $filepath = str_replace($this->start_dir, '', $pathname);


        if (in_array($filepath, $this->exclude)) {
            return false;
        }


        if ($this->is_processing && $c['dpc_input_dir'] == $this->start_dir) {
            if ($this->isDir()) {
                // We know we want this because it hasn't been excluded above, so we want to create it
                // in the destination so that plugin-generated files have somewhere to go:
                $file_tmp = str_replace($c['dpc_input_dir'], $c['dpc_process_dir'], $pathname);

                mkdir($file_tmp);

                return true;
            }
            else {
                $file_info = pathinfo($filename);

                if (isset($this->plugin_handler->input_filetypes[$file_info['extension']])) {
                    // We have a handler for this filetype.
                    $handler_name = $this->plugin_handler->input_filetypes[$file_info['extension']];
                    $handler = $this->plugin_handler->handlers[$handler_name];

                    return $handler->handle($pathname);
                }

                return true;
            }
        }

        return true;
    }

    public function getChildren() {
        return new self($this->getInnerIterator()->getChildren(), $this->filter_config, $this->plugin_handler);
    }

}