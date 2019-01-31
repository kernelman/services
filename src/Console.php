<?php
/**
 * Class Console
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/29/19
 * Time:    5:44 PM
 */

namespace Services;


use Exceptions\NotFoundException;

class Console
{

    private $config         = null;
    private $tasksDir       = '';
    private $documentation  = [];

    /**
     * @param array|null $arguments
     * @param $cfg object
     * @throws NotFoundException
     */
    public function handle(array $arguments = null, $cfg = null) {
        $this->config = $cfg;
        if (isset($arguments['task']) && in_array($arguments['task'], ['-h', '--help', 'help'])) {
            $this->setTasksDir();
            $this->createHelp($arguments);
            $this->showHelp();
            return;

        } elseif (isset($arguments['action']) && in_array($arguments['action'], ['-h', '--help', 'help'])) {
            $this->setTasksDir();
            $this->createHelp($arguments);
            $this->showTaskHelp($arguments['task']);
            return;
        }
    }

    /**
     * @throws NotFoundException
     */
    private function setTasksDir() {
        $config = $this->config;

        if (!isset($config->tasksDir) || !is_dir($config->tasksDir)) {
            throw new NotFoundException("Invalid provided tasks Dir");
        }

        $this->tasksDir = $config->tasksDir;
    }

    private function createHelp($arguments) {
        $config = $this->config;
        $namespace = $config->namespace;
        $scannedTasksDir = array_diff(scandir($this->tasksDir), ['..', '.', $config->cliName]);

        foreach ($scannedTasksDir as $taskFile) {
            $taskFileInfo = pathinfo($taskFile);
            $taskClass = ($namespace ? $namespace . '\\' : '') . $taskFileInfo["filename"];
            $taskName  = strtolower($taskFileInfo["filename"]);
            $this->documentation[$taskName] = [];
            $reflector = new \ReflectionClass($taskClass);
            $annotations = $reflector->getMethods(\ReflectionMethod::IS_PUBLIC);
            $description = $reflector->getProperty('description');
            $getParams[] = [];

            // Class Annotations
            foreach ($annotations as $annotation) {

                $params     = $annotation->getParameters();
                $actionName = $annotation->getName();

                foreach ($params as $param) {
                    $getParams[] = $param->getName();
                }

                $this->documentation[$taskName][$actionName]['params'] = $getParams;
                $this->documentation[$taskName][$actionName]['description'] = (array)$description->getName();
                $this->documentation[$taskName][$actionName]['actions'] = $annotation->getName();
            }
        }
    }

    /**
     * Show help
     */
    private function showHelp() {
        $config = $this->config;
        $helpOutput = PHP_EOL;
        if (isset($config->appName)) {
            $helpOutput .= $config->appName . ' ';
        }

        if (isset($config->version)) {
            $helpOutput .= $config->version;
        }

        echo $helpOutput . PHP_EOL;
        echo PHP_EOL . 'Usage:' . PHP_EOL;
        echo PHP_EOL;
        echo "\t" , 'command [<task> [<action> [<param1> <param2> ... <paramN>] ] ]', PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL . 'To show task help type:' . PHP_EOL;
        echo PHP_EOL;
        echo '           command <task> -h | --help | help'. PHP_EOL;
        echo PHP_EOL;
        echo 'Available tasks '.PHP_EOL;
        foreach ($this->documentation as $task => $doc) {
            echo  PHP_EOL;
            echo '    '. $task . PHP_EOL ;

            foreach ($doc['description'] as $line) {
                echo '            '.$line . PHP_EOL;
            }
        }
    }

    /**
     * @param $taskTogetHelp
     */
    private function showTaskHelp($taskTogetHelp) {
        $config = $this->config;
        $helpOutput = PHP_EOL;
        if (isset($config->appName)) {
            $helpOutput .= $config->appName . ' ';
        }

        if (isset($config->version)) {
            $helpOutput .= $config->version;
        }

        echo $helpOutput . PHP_EOL;
        echo PHP_EOL . 'Usage:' . PHP_EOL;
        echo PHP_EOL;
        echo "\t" , 'command [<task> [<action> [<param1> <param2> ... <paramN>] ] ]', PHP_EOL;
        echo PHP_EOL;
        foreach ($this->documentation as $task => $doc) {
            if ($taskTogetHelp != $task) {
                continue;
            }

            echo  PHP_EOL;
            echo "Task: " . $task . PHP_EOL . PHP_EOL ;

            foreach ($doc['description'] as $line) {
                echo '  '.$line . PHP_EOL;
            }
            echo  PHP_EOL;
            echo 'Available actions:'.PHP_EOL.PHP_EOL;

            foreach ($doc['actions'] as $actionName => $aDoc) {
                echo '           '.$actionName . PHP_EOL;
                if (isset($aDoc['description'])) {
                    echo '               '.implode(PHP_EOL, $aDoc['description']) . PHP_EOL;
                }
                echo  PHP_EOL;
                if (isset($aDoc['params']) && is_array($aDoc['params'])) {
                    echo '               Parameters:'.PHP_EOL;
                    foreach ($aDoc['params'] as $param) {
                        if (is_array($param)) {
                            $_to_print = '';
                            if (isset($param[0]['name'])) {
                                $_to_print = $param[0]['name'];
                            }

                            if (isset($param[0]['type'])) {
                                $_to_print .= ' ( '.$param[0]['type'].' )';
                            }

                            if (isset($param[0]['description'])) {
                                $_to_print .= ' '.$param[0]['description'].PHP_EOL;
                            }

                            if (!empty($_to_print)) {
                                echo '                   '.$_to_print;
                            }
                        }
                    }
                }
            }
            break;
        }
    }
}
