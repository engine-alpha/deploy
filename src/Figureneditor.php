<?php

namespace App;

class Figureneditor {
	private $event;
	private $data;

	public function handleEvent($event, $data) {
		$this->event = $event;
		$this->data = $data;

		switch($event) {
			case 'push':
				$this->handlePush();
				break;
			case 'release':
				$this->handleRelease();
				break;
		}
	}

	public function handlePush() {
		if ($this->data->ref !== 'refs/heads/master') {
			return;
		}

		$dir = sys_get_temp_dir() . '/engine-alpha';

		mkdir($dir, 0755, true);
		chdir($dir);

		$this->exec('git', 'clone', 'https://github.com/engine-alpha/figureneditor');

		if (!file_exists('figureneditor/res')) {
			mkdir('figureneditor/res', 0755, true);
		}

		file_put_contents('figureneditor/res/commit', $this->data->after);

		if (!file_exists(DEPLOY_ROOT . '/downloads/nightly')) {
			mkdir(DEPLOY_ROOT . '/downloads/nightly', 0755, true);
		}

		chdir('figureneditor');
		$ant = $this->exec('ant', 'jar');
		$error = strpos($ant, 'BUILD SUCCESSFUL') === false;

		if (!$error) {
			$this->exec('cp', 'build/Figureneditor.jar', DEPLOY_ROOT . '/downloads/nightly/figureneditor.jar');
		}

		rrmdir($dir);
	}

	public function handleRelease() {
		header('Content-Type: text/plain');

		$tag = $this->data->release->tag_name;

		if($this->data->release->draft || $this->data->release->prerelease) {
			die('no final release, aborting');
		}

		if(!preg_match('@^v[0-9]+\.[0-9]+(\.[0-9]+)?$@', $tag)) {
			die('no valid tag name: "' . $tag . '"');
		}

		$dir = sys_get_temp_dir() . '/engine-alpha';

		mkdir($dir, 0755, true);
		chdir($dir);

		$this->exec('git', 'clone', 'https://github.com/engine-alpha/figureneditor');
		chdir('figureneditor');
		$this->exec('git', 'checkout', $tag);

		if(!file_exists('res')) {
			mkdir('res', 0755, true);
		}

		file_put_contents('res/version', $tag);

		if (!file_exists(DEPLOY_ROOT . '/downloads/' . $tag)) {
			mkdir(DEPLOY_ROOT . '/downloads/' . $tag, 0755, true);
		}

		if (!file_exists(DEPLOY_ROOT . '/downloads/latest')) {
			mkdir(DEPLOY_ROOT . '/downloads/latest', 0755, true);
		}

		$ant = $this->exec('ant', 'jar');
		$error = strpos($ant, 'BUILD SUCCESSFUL') === FALSE;

		if (!$error) {
			$this->exec('cp', 'build/Figureneditor.jar', DEPLOY_ROOT . '/downloads/latest/figureneditor.jar');
		}

		rrmdir($dir);
	}

	private function exec(string ...$args) {
		return shell_exec(implode(" ", array_map("escapeshellarg", $args)));
	}
}
