<?php

namespace App\Http\Livewire;

use App\Actions\Server\UpdateCoolify;
use App\Models\InstanceSettings;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Upgrade extends Component
{
    public bool $showProgress = false;
    public bool $isUpgradeAvailable = false;
    public string $latestVersion = '';

    public function checkUpdate()
    {
        $this->latestVersion = get_latest_version_of_coolify();
        $currentVersion = config('version');
        version_compare($currentVersion, $this->latestVersion, '<') ? $this->isUpgradeAvailable = true : $this->isUpgradeAvailable = false;
        if (is_dev()) {
            $this->isUpgradeAvailable = true;
        }
        $settings = InstanceSettings::get();
        if ($settings->next_channel) {
            $this->isUpgradeAvailable = true;
            $this->latestVersion = 'next';
        }
    }

    public function upgrade()
    {
        try {
            if ($this->showProgress) {
                return;
            }
            $this->showProgress = true;
            resolve(UpdateCoolify::class)(true);
            Toaster::success("Upgrading to {$this->latestVersion} version...");
        } catch (\Exception $e) {
            return general_error_handler(err: $e, that: $this);
        }
    }
}
