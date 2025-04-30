<?php

namespace App\Http\Controllers;

use App\Http\Requests\RideRequest;
use App\Http\Requests\RideFilterRequest;
use App\Models\Ride;
use App\Services\RideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class RideController extends Controller
{
    /**
     * @var RideService
     */
    protected $rideService;

    /**
     * RideController constructor.
     *
     * @param RideService $rideService
     */
    public function __construct(RideService $rideService)
    {
        $this->rideService = $rideService;
        $this->middleware('auth');
    }

    /**
     * Display a listing of the rides.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $rides = $this->rideService->getAllRides();
        
        return response()->json( $rides
        );
    }

    /**
     * Store a newly created ride in storage.
     *
     * @param RideRequest $request
     * @return JsonResponse
     */
    public function store(RideRequest $request): JsonResponse
    {
        $ride = $this->rideService->createRide($request->validated());
        
        return response()->json(
             $ride
        , Response::HTTP_CREATED);
    }

    /**
     * Display the specified ride.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(ride $ride): JsonResponse
    {
        $ride->load('driver');
        
        return response()->json(
            $ride, Response::HTTP_OK
        );
    }

    /**
     * Update the specified ride in storage.
     *
     * @param RideRequest $request
     * @param Ride $ride
     * @return JsonResponse
     */
    public function update(RideRequest $request, Ride $ride): JsonResponse
    {
        if (!$this->rideService->userOwnsRide($ride)) {
            return response()->json([
                'message' => 'you can only update your own rides'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $updatedRide = $this->rideService->updateRide($ride, $request->validated());
        
        return response()->json( $updatedRide
        , Response::HTTP_OK);
    }

    /**
     * Archive the specified ride.
     *
     * @param Ride $ride
     * @return JsonResponse
     */
    public function archive(Ride $ride): JsonResponse
    {
        if (!$this->rideService->userOwnsRide($ride)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $archivedRide = $this->rideService->archiveRide($ride);
        
        return response()->json( $archivedRide
        ,Response::HTTP_OK);
    }

    /**
     * Remove the specified ride from storage.
     *
     * @param Ride $ride
     * @return JsonResponse
     */
    public function destroy(Ride $ride): JsonResponse
    {
        if (!$this->rideService->userOwnsRide($ride)) {
            return response()->json([
                'message' => 'you can only delete your own rides'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $this->rideService->deleteRide($ride);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Ride deleted successfully'
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Filter rides based on criteria.
     *
     * @param RideFilterRequest $request
     * @return JsonResponse
     */
    public function filter(RideFilterRequest $request): JsonResponse
    {
        $filteredRides = $this->rideService->filterRides($request->validated());
        
        return response()->json( $filteredRides);
    }

    /**
     * Get rides for the authenticated user (driver).
     *
     * @return JsonResponse
     */
    public function myRides(): JsonResponse
    {
        $myRides = $this->rideService->getMyRides();
        
        return response()->json($myRides);
    }
}