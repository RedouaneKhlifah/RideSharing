<?php

namespace App\Services;

use App\Models\Ride;
use App\Repositories\RideRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class RideService
{
    /**
     * @var RideRepository
     */
    protected $rideRepository;

    /**
     * RideService constructor.
     *
     * @param RideRepository $rideRepository
     */
    public function __construct(RideRepository $rideRepository)
    {
        $this->rideRepository = $rideRepository;
    }

    /**
     * Get all rides.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllRides():Collection
    {
        return $this->rideRepository->getAll();
    }

    /**
     * Get a ride by ID.
     *
     * @param int $id
     * @return Ride|null
     */
    public function getRideById(int $id): ?Ride
    {
        return $this->rideRepository->findById($id);
    }

    /**
     * Create a new ride.
     *
     * @param array $data
     * @return Ride
     */
    public function createRide(array $data): Ride
    {
        return $this->rideRepository->create(
            $data,
            Auth::id()
        );
    }

    /**
     * Update an existing ride.
     *
     * @param Ride $ride
     * @param array $data
     * @return Ride
     */
    public function updateRide(Ride $ride, array $data): Ride
    {
        return $this->rideRepository->update($ride, $data);
    }

    /**
     * Archive a ride.
     *
     * @param Ride $ride
     * @return Ride
     */
    public function toggleArchive(Ride $ride): Ride
    {
        return $this->rideRepository->toggleArchive($ride);
    }

    /**
     * Delete a ride.
     *
     * @param Ride $ride
     * @return bool
     */
    public function deleteRide(Ride $ride): bool
    {
        return $this->rideRepository->delete($ride);
    }

    /**
     * Filter rides by criteria.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filterRides(array $filters, int $perPage = 15): Collection
    {
        return $this->rideRepository->filter($filters);
    }

    /**
     * Get rides by current user (driver).
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMyRides(string $archived = 'all'): Collection
    {
        return $this->rideRepository->getByDriverId(Auth::id(), $archived);
    }


    /**
     * Check if user owns a ride.
     *
     * @param Ride $ride
     * @return bool
     */
    public function userOwnsRide(Ride $ride): bool
    {
        return $ride->driver_id === Auth::id();
    }
}