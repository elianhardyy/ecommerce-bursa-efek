<?php

namespace App\Services;

interface ServiceInterface
{
    /**
     * Get all resources.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll();

    /**
     * Get paginated resources.
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginated($perPage = 10);

    /**
     * Get resource by id.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getById($id);

    /**
     * Create new resource.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);

    /**
     * Update resource by id.
     *
     * @param int $id
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update($id, array $data);

    /**
     * Delete resource by id.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id);
}