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

    private $config         = '';
    private $tasksDir       = '';
    private $documentation  = [];

    /**
     * @param array|null $arguments
     * @throws NotFoundException
     */
    public function handle(array $arguments = null) {
        if (isset($arguments['task']) && in_array($arguments['task'], ['-h', '--help', 'help'])) {
            $this->setTasksDir();
            $this->createHelp();
            $this->showHelp();
            return;

        } elseif (isset($arguments['action']) && in_array($arguments['action'], ['-h', '--help', 'help'])) {
            $this->setTasksDir();
            $this->createHelp();
            $this->showTaskHelp($arguments['task']);
            return;
        }
    }

    /**
     * @throws NotFoundException
     */
    private function setTasksDir() {
        $config = $this->config;

        if (!isset($config['tasksDir']) || !is_dir($config['tasksDir'])) {
            throw new NotFoundException("Invalid provided tasks Dir");
        }

        $this->tasksDir = $config['tasksDir'];
    }

    private function createHelp() {
        $scannedTasksDir = array_diff(scandir($this->tasksDir), ['..', '.']);

        $config = $this->config;
        $dispatcher = $this->config->dispatcher;
        $namespace = $dispatcher->getNamespaceName();

        if (isset($config['annotationsAdapter']) && $config['annotationsAdapter']) {
            $adapter = '\Phalcon\Annotations\Adapter\\' . $config['annotationsAdapter'];
            if (class_exists($adapter)) {
                $reader = new $adapter();
            } else {
                $reader = new ConsoleAdapter();
            }
        } else {
            $reader = new ConsoleAdapter();
        }

        foreach ($scannedTasksDir as $taskFile) {
            $taskFileInfo = pathinfo($taskFile);
            $taskClass = ($namespace ? $namespace . '\\' : '') . $taskFileInfo["filename"];
            $taskName  = strtolower(str_replace('Task', '', $taskFileInfo["filename"]));

            $this->documentation[$taskName] = ['description' => [''], 'actions' => []];

            $reflector = $reader->get($taskClass);

            $annotations = $reflector->getClassAnnotations();

            if (!$annotations) {
                continue;
            }

            // Class Annotations
            foreach ($annotations as $annotation) {
                if ($annotation->getName() == 'description') {
                    $this->documentation[$taskName]['description'] = $annotation->getArguments();
                }
            }

            $methodAnnotations = $reflector->getMethodsAnnotations();

            // Method Annotations
            if (!$methodAnnotations) {
                continue;
            }

            foreach ($methodAnnotations as $action => $collection) {
                if ($collection->has('DoNotCover')) {
                    continue;
                }

                $actionName = strtolower(str_replace('Action', '', $action));

                $this->documentation[$taskName]['actions'][$actionName] = [];

                $actionAnnotations = $collection->getAnnotations();

                foreach ($actionAnnotations as $actAnnotation) {
                    $_anotation = $actAnnotation->getName();
                    if ($_anotation == 'description') {
                        $getDesc = $actAnnotation->getArguments();
                        $this->documentation[$taskName]['actions'][$actionName]['description'] = $getDesc;
                    } elseif ($_anotation == 'param') {
                        $getParams = $actAnnotation->getArguments();
                        $this->documentation[$taskName]['actions'][$actionName]['params'][]  = $getParams;
                    }
                }
            }
        }
    }

    /**
     * Show help
     */
    private function showHelp() {
        $config = $this->config;
        $helpOutput = PHP_EOL;
        if (isset($config['appName'])) {
            $helpOutput .= $config['appName'] . ' ';
        }

        if (isset($config['version'])) {
            $helpOutput .= $config['version'];
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
        if (isset($config['appName'])) {
            $helpOutput .= $config['appName'] . ' ';
        }

        if (isset($config['version'])) {
            $helpOutput .= $config['version'];
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
