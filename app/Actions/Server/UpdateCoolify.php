<?php

namespace App\Actions\Server;

use App\Models\InstanceSettings;
use App\Models\Server;

class UpdateCoolify
{
    public Server $server;
    public string $latest_version;
    public string $current_version;

    public function __invoke(bool $force)
    {
        try {
            $settings = InstanceSettings::get();
            ray('Running InstanceAutoUpdateJob');
            $localhost_name = 'localhost';
            if (is_dev()) {
                $localhost_name = 'testing-local-docker-container';
            }
            $this->server = Server::where('name', $localhost_name)->firstOrFail();
            $this->latest_version = get_latest_version_of_coolify();
            $this->current_version = config('version');
            ray('latest version:' . $this->latest_version . " current version: " . $this->current_version . ' force: ' . $force);
            if ($settings->next_channel) {
                ray('next channel enabled');
                $this->latest_version = 'next';
            }
            if ($force) {
                $this->update();
            } else {
                if (!$settings->is_auto_update_enabled) {
                    throw new \Exception('Auto update is disabled');
                }
                if ($this->latest_version === $this->current_version) {
                    throw new \Exception('Already on latest version');
                }
                if (version_compare($this->latest_version, $this->current_version, '<')) {
                    throw new \Exception('Latest version is lower than current version?!');
                }
                $this->update();
            }
            return;
        } catch (\Exception $e) {
            ray('InstanceAutoUpdateJob failed');
            ray($e->getMessage());
            return;
        }
    }

    private function update()
    {
        if (is_dev()) {
            ray("Running update on local docker container. Updating to $this->latest_version");
            remote_process([
                "sleep 10"
            ], $this->server);
            ray('Update done');
            return;
        } else {
            ray('Running update on production server');
            remote_process([
                "curl -fsSL https://cdn.coollabs.io/coolify/upgrade.sh -o /data/coolify/source/upgrade.sh",
                "bash /data/coolify/source/upgrade.sh $this->latest_version"
            ], $this->server);
            return;
        }
    }
}
