<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

abstract class BaseService implements ServiceInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var int
     */
    protected $cacheTtl = 3600; // 1 hour

    /**
     * Get all resources.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return Cache::remember($this->cacheKey . '.all', $this->cacheTtl, function () {
            return $this->model->all();
        });
    }

    /**
     * Get paginated resources.
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginated($perPage = 10)
    {
        $page = request()->get('page', 1);
        $cacheKey = $this->cacheKey . '.paginated.' . $perPage . '.' . $page;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($perPage) {
            return $this->model->paginate($perPage);
        });
    }

    /**
     * Get resource by id.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getById($id)
    {
        return Cache::remember($this->cacheKey . '.id.' . $id, $this->cacheTtl, function () use ($id) {
            return $this->model->findOrFail($id);
        });
    }

    /**
     * Create new resource.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data)
    {
        $model = $this->model->create($data);
        $this->clearCache();
        return $model;
    }

    /**
     * Update resource by id.
     *
     * @param int $id
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update($id, array $data)
    {
        $model = $this->getById($id);
        $model->update($data);
        $this->clearCache();
        return $model;
    }

    /**
     * Delete resource by id.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $model = $this->getById($id);
        $this->clearCache();
        return $model->delete();
    }

    /**
     * Clear cache for this service.
     *
     * @return void
     */
    protected function clearCache()
    {
        Cache::forget($this->cacheKey . '.all');
        
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget($this->cacheKey . '.paginated.10.' . $i);
        }
    }
}