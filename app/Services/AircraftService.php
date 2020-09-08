<?php

namespace App\Services;

use App\Contracts\Service;
use App\Models\Aircraft;
use App\Models\Enums\PirepState;
use App\Models\Pirep;
use App\Repositories\PirepRepository;

class AircraftService extends Service
{
    /** @var PirepRepository */
    private $pirepRepo;

    public function __construct(PirepRepository $pirepRepo)
    {
        $this->pirepRepo = $pirepRepo;
    }

    /**
     * Get a list of the PIREPs that have been flown by this aircraft
     *
     * @param Aircraft $aircraft
     * @param int      $count
     *
     * @return Pirep[] Collection of PIREPs
     */
    public function getFlightHistory(Aircraft $aircraft, $count = 5)
    {
        return $this->pirepRepo
            ->orderBy('created_at', 'desc')
            ->limit($count)
            ->where([
                'aircraft_id' => $aircraft->id,
                'state'       => PirepState::ACCEPTED,
            ])
            ->get();
    }

    /**
     * Recalculate all aircraft stats and hours
     */
    public function recalculateStats()
    {
        $allAircraft = Aircraft::all(); // TODO: Soft delete
        foreach ($allAircraft as $aircraft) {
            $pirep_time_total = Pirep::where('aircraft_id', $aircraft->id)
                ->sum('flight_time');
            $aircraft->flight_time = $pirep_time_total;
            $aircraft->save();
        }
    }
}
