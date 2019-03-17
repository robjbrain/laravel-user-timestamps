<?php

namespace Robjbrain\LaravelUserTimestamps;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait UserSoftDeletes
{
    use SoftDeletes {
        restore as protected originalRestore;
    }

    /**
     * Indicates if the soft delete should include a soft delete
     *
     * @var bool
     */
    public $userSoftDeletes = true;

    /**
     * Indicates if the soft delete should include a polymorphic actor
     *
     * @var bool
     */
    public $polymorphicSoftDeletes = false;

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        $user = $this->getSoftDeletesActor();

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $time];

        if ($this->usesUserSoftDeletes()) {
            $columns[$this->getDeletedByIdColumn()] = $user->getKey();
        }

        if ($this->usesPolymorphicSoftDeletes()) {
            $columns[$this->getDeletedByTypeColumn()] = $user->getMorphClass();
        }

        if ($this->timestamps) {
            $this->setUpdatedAt($time, $user);

            $columns[$this->getUpdatedAtColumn()] = $time;

            if ($this->usesUserSoftDeletes()) {
                $columns[$this->getUpdatedByIdColumn()] = $user->getKey();

                if ($this->usesPolymorphicSoftDeletes()) {
                    $columns[$this->getUpdatedByTypeColumn()] = $user->getMorphClass();
                }
            }
        }

        // Assign the updated properties to the model
        foreach ($columns as $key => $value) {
            $this->{$key} = $value;
        }

        $query->update($columns);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        if ($this->usesUserSoftDeletes()) {
            $this->{$this->getDeletedByIdColumn()} = null;
        }

        if ($this->usesPolymorphicSoftDeletes()) {
            $this->{$this->getDeletedByTypeColumn()} = null;
        }

        // This is an alias of SoftDeletes::restore()
        return $this->originalRestore();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getSoftDeletesActor()
    {
        return Auth::user();
    }

    /**
     * @return string
     */
    protected function getSoftDeletesActorModel()
    {
        return Auth::getProvider()->getModel();
    }

    /**
     * Determine if the model uses soft deletes with user id's.
     *
     * @return bool
     */
    public function usesUserSoftDeletes()
    {
        return $this->userSoftDeletes || $this->polymorphicSoftDeletes;
    }

    /**
     * Determine if the model uses polymorphic actors
     *
     * @return bool
     */
    public function usesPolymorphicSoftDeletes()
    {
        return $this->polymorphicSoftDeletes;
    }

    /**
     * Get the name of the "deleted by id" column.
     *
     * @return string
     */
    public function getDeletedByIdColumn()
    {
        return defined('static::DELETED_BY_ID') ? static::DELETED_BY_ID : 'deleted_by_id';
    }

    /**
     * Get the fully qualified "deleted by id" column.
     *
     * @return string
     */
    public function getQualifiedDeletedByIdColumn()
    {
        return $this->qualifyColumn($this->getDeletedByIdColumn());
    }

    /**
     * Get the name of the "deleted by type" column.
     *
     * @return string
     */
    public function getDeletedByTypeColumn()
    {
        return defined('static::DELETED_BY_TYPE') ? static::DELETED_BY_TYPE : 'deleted_by_type';
    }

    /**
     * Get the fully qualified "deleted by type" column.
     *
     * @return string
     */
    public function getQualifiedDeletedByTypeColumn()
    {
        return $this->qualifyColumn($this->getDeletedByTypeColumn());
    }

    /**
     * @return mixed
     */
    public function deletedBy()
    {
        return $this->usesPolymorphicSoftDeletes() ? $this->morphTo('deleted_by', $this->getDeletedByIdColumn(), $this->getDeletedByTypeColumn()) : $this->belongsTo($this->getSoftDeletesActorModel());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $filter
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \Exception
     */
    public function scopeFilterTrashed(Builder $query, string $filter)
    {
        if (!in_array($filter, ['only', 'with', 'without'])) {
            throw new \Exception('Unexpected trashed filter - ' . $filter);
        }

        // onlyTrashed, withTrashed or withoutTrashed
        return $query->{$filter . 'Trashed'}();
    }
}