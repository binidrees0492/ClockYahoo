<?php

namespace App\Livewire;

use App\Models\CompanySetting;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Livewire\Attributes\On;

class NavbarClock extends Component
{
    public $showModal = false;
    public $selectedTimezone = 'UTC';
    public $changeType = 'temp'; // 'temp' (this session) or 'permanent' (personal configs)
    public $searchTimezone = '';
    public $isTemp = false;

    public $previewTime = '';
    public $previewDate = '';
    public $previewAbbr = '';

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        $user = Auth::user();
        $this->selectedTimezone = $user ? $user->getTimezone() : CompanySetting::getServerTimezone();
        $this->isTemp = $user ? $user->isTemporaryTimezone() : false;
        $this->changeType = $this->isTemp ? 'temp' : 'permanent';
        $this->updatePreview();
    }

    #[On('open-timezone-modal')]
    public function openModal()
    {
        $user = Auth::user();
        $this->selectedTimezone = $user ? $user->getTimezone() : CompanySetting::getServerTimezone();
        $this->isTemp = $user ? $user->isTemporaryTimezone() : false;
        $this->changeType = $this->isTemp ? 'temp' : 'permanent';
        $this->searchTimezone = '';
        $this->updatePreview();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function updatedSelectedTimezone()
    {
        $this->updatePreview();
    }

    public function selectTimezone($tz)
    {
        $this->selectedTimezone = $tz;
        $this->updatePreview();
    }

    public function updatePreview()
    {
        try {
            $now = Carbon::now($this->selectedTimezone);
            $this->previewTime = $now->format('g:i:s A');
            $this->previewDate = $now->format('m/d/Y');
            $dt = new DateTime('now', new DateTimeZone($this->selectedTimezone));
            $this->previewAbbr = $dt->format('T') . ' (' . $now->format('P') . ')';
        } catch (\Throwable $e) {
            $this->previewTime = Carbon::now()->format('g:i:s A');
            $this->previewDate = Carbon::now()->format('m/d/Y');
            $this->previewAbbr = 'UTC';
        }
    }

    public function applyTimezone()
    {
        $validTzs = DateTimeZone::listIdentifiers();
        if (!in_array($this->selectedTimezone, $validTzs)) {
            $this->toastMessage = 'Invalid timezone selected.';
            $this->toastType = 'error';
            return;
        }

        $user = Auth::user();

        if ($this->changeType === 'temp') {
            session(['temp_timezone' => $this->selectedTimezone]);
            $this->isTemp = true;
            $this->toastMessage = "Temporary timezone set to {$this->selectedTimezone} for this session.";
            $this->toastType = 'success';
        } else {
            if ($user) {
                $user->update(['timezone' => $this->selectedTimezone]);
            }
            session()->forget('temp_timezone');
            $this->isTemp = false;
            $this->toastMessage = "Timezone permanently saved to your Personal Configs ({$this->selectedTimezone}).";
            $this->toastType = 'success';
        }

        $this->showModal = false;
        $this->dispatch('timezone-changed', timezone: $this->selectedTimezone, isTemp: $this->isTemp);
        $this->js('setTimeout(() => window.location.reload(), 300);');
    }

    public function revertToProfile()
    {
        session()->forget('temp_timezone');
        $this->isTemp = false;
        $user = Auth::user();
        $this->selectedTimezone = ($user && $user->timezone) ? $user->timezone : CompanySetting::getServerTimezone();
        $this->showModal = false;
        $this->dispatch('timezone-changed', timezone: $this->selectedTimezone, isTemp: false);
        $this->js('setTimeout(() => window.location.reload(), 300);');
    }

    public static function getCuratedTimezones(): array
    {
        return [
            'Global' => [
                'UTC'                 => 'UTC — Coordinated Universal Time (+00:00)',
            ],
            'North America' => [
                'America/New_York'    => 'US Eastern Time (New York, Miami, Atlanta)',
                'America/Chicago'     => 'US Central Time (Chicago, Dallas, Houston)',
                'America/Denver'      => 'US Mountain Time (Denver, Phoenix, Salt Lake)',
                'America/Los_Angeles' => 'US Pacific Time (Los Angeles, San Francisco, Seattle)',
                'America/Anchorage'   => 'US Alaska Time (Anchorage)',
                'Pacific/Honolulu'    => 'US Hawaii Time (Honolulu)',
                'America/Toronto'     => 'Canada Eastern (Toronto, Montreal)',
                'America/Vancouver'   => 'Canada Pacific (Vancouver)',
            ],
            'Europe' => [
                'Europe/London'       => 'UK & Ireland (London, Dublin — GMT/BST)',
                'Europe/Paris'        => 'Central Europe (Paris, Berlin, Rome, Madrid, Amsterdam — CET)',
                'Europe/Athens'       => 'Eastern Europe (Athens, Bucharest, Helsinki — EET)',
                'Europe/Moscow'       => 'Russia (Moscow — MSK)',
            ],
            'Asia & Middle East' => [
                'Asia/Dubai'          => 'UAE & Gulf (Dubai, Abu Dhabi — GST +04:00)',
                'Asia/Karachi'        => 'Pakistan (Karachi, Islamabad, Lahore — PKT +05:00)',
                'Asia/Kolkata'        => 'India & Sri Lanka (New Delhi, Mumbai — IST +05:30)',
                'Asia/Dhaka'          => 'Bangladesh (Dhaka — BST +06:00)',
                'Asia/Bangkok'        => 'Southeast Asia (Bangkok, Jakarta, Hanoi — ICT +07:00)',
                'Asia/Singapore'      => 'Singapore & Malaysia (Singapore, Kuala Lumpur — SGT +08:00)',
                'Asia/Hong_Kong'      => 'Hong Kong & China (Hong Kong, Beijing — HKT +08:00)',
                'Asia/Tokyo'          => 'Japan & Korea (Tokyo, Seoul — JST/KST +09:00)',
                'Asia/Riyadh'         => 'Saudi Arabia (Riyadh — AST +03:00)',
            ],
            'Australia & Pacific' => [
                'Australia/Sydney'    => 'Eastern Australia (Sydney, Melbourne, Brisbane — AEST)',
                'Australia/Perth'     => 'Western Australia (Perth — AWST)',
                'Pacific/Auckland'    => 'New Zealand (Auckland, Wellington — NZST)',
            ],
            'Latin America & Caribbean' => [
                'America/Mexico_City' => 'Mexico (Mexico City — CST)',
                'America/Bogota'      => 'Colombia & Peru (Bogota, Lima — COT/PET)',
                'America/Sao_Paulo'   => 'Brazil (São Paulo, Rio de Janeiro — BRT)',
                'America/Buenos_Aires'=> 'Argentina (Buenos Aires — ART)',
            ],
            'Africa' => [
                'Africa/Cairo'        => 'Egypt (Cairo — EET)',
                'Africa/Johannesburg' => 'South Africa (Johannesburg, Cape Town — SAST)',
                'Africa/Lagos'        => 'West Africa (Lagos, Accra — WAT)',
                'Africa/Nairobi'      => 'East Africa (Nairobi, Addis Ababa — EAT)',
            ],
        ];
    }

    public function render()
    {
        $user = Auth::user();
        $activeTz = $user ? $user->getTimezone() : CompanySetting::getServerTimezone();
        $isTemp = $user ? $user->isTemporaryTimezone() : false;
        $profileTz = ($user && $user->timezone) ? $user->timezone : CompanySetting::getServerTimezone();

        $curated = static::getCuratedTimezones();
        $filtered = [];

        $q = trim(strtolower($this->searchTimezone));
        foreach ($curated as $region => $tzs) {
            foreach ($tzs as $tzKey => $tzLabel) {
                if ($q === '' || str_contains(strtolower($tzKey), $q) || str_contains(strtolower($tzLabel), $q)) {
                    $filtered[$region][$tzKey] = $tzLabel;
                }
            }
        }

        try {
            $dt = new DateTime('now', new DateTimeZone($activeTz));
            $initialTimeStr = $dt->format('g:i A') . ' (' . $dt->format('T') . ')';
            $tzAbbr = $dt->format('T');
        } catch (\Throwable $e) {
            $initialTimeStr = Carbon::now()->format('g:i A') . ' (UTC)';
            $tzAbbr = 'UTC';
        }

        return view('livewire.navbar-clock', [
            'activeTz'       => $activeTz,
            'isTemp'         => $isTemp,
            'profileTz'      => $profileTz,
            'timezones'      => $filtered,
            'user'           => $user,
            'initialTimeStr' => $initialTimeStr,
            'tzAbbr'         => $tzAbbr,
        ]);
    }
}
