<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Http\Resources\Aircraft as AircraftResource;
use App\Http\Resources\Airport as AirportResource;
use App\Http\Resources\AirportDistance as AirportDistanceResource;
use App\Models\Airport;
use App\Repositories\AircraftRepository;
use App\Repositories\AirportRepository;
use App\Services\AirportService;
use Illuminate\Http\Request;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class AirportController
 */
class AirportController extends Controller
{
    private $airportRepo;
    private $airportSvc;

    /**
     * AirportController constructor.
     *
     * @param AirportRepository $airportRepo
     * @param AirportService    $airportSvc
     */
    public function __construct(
        AirportRepository $airportRepo,
        AirportService $airportSvc
    ) {
        $this->airportRepo = $airportRepo;
        $this->airportSvc = $airportSvc;
    }

    /**
     * Return all the airports, paginated
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $where = [];
        if ($request->filled('hub')) {
            $where['hub'] = $request->get('hub');
        }

        $this->airportRepo->pushCriteria(new RequestCriteria($request));

        $airports = $this->airportRepo
            ->whereOrder($where, 'icao', 'asc')
            ->paginate();

        return AirportResource::collection($airports);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index_hubs()
    {
        $where = [
            'hub' => true,
        ];

        $airports = $this->airportRepo
            ->whereOrder($where, 'icao', 'asc')
            ->paginate();

        return AirportResource::collection($airports);
    }

    /**
     * Do a lookup, via vaCentral, for the airport information
     *
     * @param $id
     *
     * @return AirportResource
     */
    public function get($id)
    {
        $id = strtoupper($id);

        return new AirportResource($this->airportRepo->find($id));
    }

    /**
     * Do a lookup, via vaCentral, for the airport information
     *
     * @param $id
     *
     * @return AirportResource
     */
    public function lookup($id)
    {
        $airport = $this->airportSvc->lookupAirport($id);
        return new AirportResource(collect($airport));
    }

    /**
     * Do a lookup, via vaCentral, for the airport information
     *
     * @param $fromIcao
     * @param $toIcao
     *
     * @return AirportDistanceResource
     */
    public function distance($fromIcao, $toIcao)
    {
        $distance = $this->airportSvc->calculateDistance($fromIcao, $toIcao);
        return new AirportDistanceResource([
            'fromIcao' => $fromIcao,
            'toIcao'   => $toIcao,
            'distance' => $distance,
        ]);
    }

    /**
     * Get aircrafts from actual airport.
     * State, status and rank are optional
     * /api/fleet/airport/ICAO?state=0&status=B&rank=idrank
     *
     * @param         $id
     * @param Request $request
     *
     * @return AircraftResource
     */
    public function get_fleet($id, Request $request, AircraftRepository $aircraftRepo)
    {
        $aircrafts = $aircraftRepo
            ->with(['subfleet', 'subfleet.fares', 'subfleet.ranks']);

        if ($request->filled('rank')) {
            $searchRank = function ($query) use ($request) {
                $query->where('id', $request->get('rank'));
            };
            $aircrafts = $aircrafts->whereHas('subfleet.ranks', $searchRank);
        }

        $where = ['airport_id' => $id];
        if ($request->filled('state')) {
            $where['state'] = $request->get('state');
        }
        if ($request->filled('status')) {
            $where['status'] = $request->get('status');
        }

        $aircrafts = $aircrafts->findWhere($where);

        return AircraftResource::collection($aircrafts);
    }

    /**
     * Find airport based in ICAO pattern.
     * Created for slect2 functions in airports
     *
     * @param $id
     *
     * @return AirportResource
     */
    public function find(Request $request)
    {
        if (strlen($request->get('term')) < 2) {
            return AirportResource::collection([]);
        }
        //@TODO Unir ambos resultados o find with OR
        //airportsName =$this->airportRepo->findWhere([['name','like','%'.$request->get('term').'%']]);
        $airportsICAO = $this->airportRepo->findWhere([['icao', 'like', '%'.$request->get('term').'%']]);
        return AirportResource::collection($airportsICAO);
    }
}
