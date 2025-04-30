<?php

namespace App\Repositories;

use App\Models\Ride;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class RideRepository
{
    /**
     * @var Ride
     */
    protected $model;

    /**
     * RideRepository constructor.
     *
     * @param Ride $model
     */
    public function __construct(Ride $model)
    {
        $this->model = $model;
    }

    /**
     * Get all rides.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model->where('is_archived', false)->orderBy('departure_time', 'asc')->with('driver')->get();
    }


    /**
     * Get ride by ID.
     *
     * @param int $id
     * @return Ride|null
     */
    public function findById(int $id): ?Ride
    {
        return $this->model->where('id', operator: $id)->first();
    }

    /**
     * Create a new ride.
     *
     * @param array $data
     * @param int $driverId
     * @return Ride
     */
    public function create(array $data, int $driverId): Ride
    {
        $data['driver_id'] = $driverId;
        return $this->model->create($data);
    }

    /**
     * Update an existing ride.
     *
     * @param Ride $ride
     * @param array $data
     * @return Ride
     */
    public function update(Ride $ride, array $data): Ride
    {
        $ride->update($data);
        return $ride->fresh();
    }

    /**
     * Archive a ride.
     *
     * @param Ride $ride
     * @return Ride
     */
    public function archive(Ride $ride): Ride
    {
        $ride->is_archived = true;
        $ride->save();
        return $ride;
    }

    /**
     * Delete a ride.
     *
     * @param Ride $ride
     * @return bool
     */
    public function delete(Ride $ride): bool
    {
        return $ride->delete();
    }

    /**
     * Filter rides by criteria.
     *
     * @param array $filters
     * @param int $perPage
     * @return Collection
     */
    public function filter(array $filters, int $perPage = 15): Collection
    {
        $query = $this->model->where('is_archived', false);

        if (!empty($filters['departure_location'])) {
            $query->where('departure_location', 'like', '%' . $filters['departure_location'] . '%');
        }

        if (!empty($filters['destination'])) {
            $query->where('destination', 'like', '%' . $filters['destination'] . '%');
        }

        if (!empty($filters['departure_date'])) {
            $date = Carbon::parse($filters['departure_date']);
            $query->whereDate('departure_time', $date->format('Y-m-d'));
        }

        if(!empty($filters['available_seats'])) {
            $query->where('available_seats', '>=', $filters['available_seats']);
        }

        return $query->orderBy('departure_time', 'asc')->with("driver")->get();
    }

    /**
     * Get rides by driver ID.
     *
     * @param int $driverId
     * @param int $perPage
     * @return Collection
     */
    public function getByDriverId(int $driverId):Collection
    {
        return $this->model->where('driver_id', $driverId)
            ->where('is_archived', false)
            ->orderBy('departure_time', 'asc')->with("driver")->get();
    }
}