<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 26/10/14
 * Time: 06:14
 */

namespace ServerMenu;


use Slim\Slim;

class PluginLoader {

        private static $plugins, $receivers;

	/**
	 * Fetch a certain plugin (Service, SearchEngine or Feed)
	 *
	 * @param string $pluginType
	 * @param int $pluginId
	 *
	 * @return Object
	 * @throws \Exception
	 */
	public static function fetch($pluginType, $pluginId)
	{
		if (!isset(self::$plugins)) {
			self::$plugins = array();
		} elseif (isset(self::$plugins[$pluginType][$pluginId])) {
			return self::$plugins[$pluginType][$pluginId];
		}

		$config = Slim::getInstance()->config('s');
		$pluginConfig = $config[$pluginType . 's'][$pluginId];
		$name = $pluginConfig['plugin'];
		$pluginTypeClass = ucfirst($pluginType);
		$pluginClass = ucfirst($name);

		$file = __DIR__ . '/' . $pluginTypeClass . 's/' . $pluginClass . '/' . $pluginClass . '.php';

		if (file_exists($file)) {
			$className = "\\ServerMenu\\{$pluginTypeClass}s\\$pluginClass\\$pluginClass";

			self::$plugins[$pluginType][$pluginId] = new $className($pluginConfig, $pluginId);
			return self::$plugins[$pluginType][$pluginId];
		} else {
			throw new \Exception('Plugin not found: ' . $file);
		}

	}

	/**
	 * Returns plugins (currently only Services) that can receive
	 * certain "receiverTypes".
	 *
	 * @param string $pluginType
	 * @param int $receiverType
	 * @return array
	 * @throws \Exception
	 */
	public static function getReceivers($pluginType, $receiverType) {
                if (!isset(self::$receivers)) {
                        self::$receivers[$pluginType][$receiverType] = array();
                } else {
                        if (isset(self::$receivers[$pluginType][$receiverType]))
                                return self::$receivers[$pluginType][$receiverType];
                }

                $pluginType = $pluginType.'s';
                $pluginClass = ucfirst($pluginType);

                $config = Slim::getInstance()->config('s');
                $config = $config[$pluginType];

                foreach ($config as $pluginId => $pluginConfig) {
                        $name = $pluginConfig['plugin'];

                        if (file_exists(__DIR__.'/'.$pluginClass.'/'.$name.'/'.$name.'.php')) {
                                $className = "\\ServerMenu\\$pluginClass\\$name\\$name";

	                        /* @var $classInstance Receiver */
                                $classInstance = new $className($pluginConfig, $pluginId);

	                        if (!in_array('ServerMenu\Receiver', class_uses($classInstance)))
		                        continue;

	                        if (!$classInstance->canReceive($receiverType))
		                        continue;

                                self::$receivers[$pluginType][$receiverType][] = array(
                                        'pluginId' => $pluginId,
                                        'plugin'   => $name
                                );
                        }
                }

                return array("plugins"=>self::$receivers[$pluginType][$receiverType]);
        }

} 