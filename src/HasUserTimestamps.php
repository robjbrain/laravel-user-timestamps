<?php

namespace Robjbrain\LaravelUserTimestamps;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

trait HasUserTimestamps
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the model should be timestamped with a user id
     *
     * @var bool
     */
    public $userTimestamps = false;

    /**
     * Indicates if the model should be timestamped with a polymorphic actor
     *
     * @var bool
     */
    public $polymorphicTimestamps = false;

    /**
     * Update the model's update timestamp.
     *
     * @param  string|null  $attribute
     * @return bool
     */
    public function touch($attribute = null)
    {
        if ($attribute) {
            $this->$attribute = $this->freshTimestamp();

            return $this->save();
        }

        if (!$this->usesTimestamps()) {
            return false;
        }

        $this->updateTimestamps();

        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     *
     * @param  Model $user
     * @return void
     */
    public function updateTimestamps(Model $user = null)
    {
        $time = $this->freshTimestamp();

        if (!is_null($this->getUpdatedByIdColumn())) {
            $this->setUpdatedAt($time, $user);
        }

        if (!$this->exists && !is_null($this->getCreatedAtColumn())) {
            $this->setCreatedAt($time, $user);
        }
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed $value
     * @param  Model $user
     * @return $this
     */
    public function setCreatedAt($value, Model $user = null)
    {
        if ($this->usesTimestamps() && $this->isClean($this->getCreatedAtColumn())) {
            $this->{$this->getCreatedAtColumn()} = $value;
        }

        if ($this->usesUserTimestamps() && $this->isClean($this->getCreatedByIdColumn())) {
            if (!$user) $user = $this->getTimestampActor();

            if ($user) {
                $this->{$this->getCreatedByIdColumn()} = $user->getKey();

                if ($this->usesPolymorphicTimestamps() && $this->isClean($this->getCreatedByTypeColumn())) {
                    $this->{$this->getCreatedByTypeColumn()} = $user->getMorphClass();
                }
            }
        }

        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed $value
     * @param  Model $user
     * @return $this
     */
    public function setUpdatedAt($value, Model $user = null)
    {
        if ($this->usesTimestamps() && $this->isClean($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $value;
        }

        if ($this->usesUserTimestamps() && $this->isClean($this->getUpdatedByIdColumn())) {
            if (!$user) $user = $this->getTimestampActor();

            if ($user) {
                $this->{$this->getUpdatedByIdColumn()} = $user->getKey();

                if ($this->usesPolymorphicTimestamps() && $this->isClean($this->getUpdatedByTypeColumn())) {
                    $this->{$this->getUpdatedByTypeColumn()} = $user->getMorphClass();
                }
            }
        }

        return $this;
    }

    /**
     * @return Authenticatable
     */
    protected function getTimestampActor()
    {
        return Auth::user();
    }

    /**
     * @return string
     */
    protected function getTimestampActorModel()
    {
        return Auth::getProvider()->getModel();
    }


    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Illuminate\Support\Carbon
     */
    public function freshTimestamp()
    {
        return new Carbon;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return string
     */
    public function freshTimestampString()
    {
        return $this->fromDateTime($this->freshTimestamp());
    }

    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps || $this->userTimestamps || $this->polymorphicTimestamps;
    }

    /**
     * Determine if the model uses timestamps with user id's.
     *
     * @return bool
     */
    public function usesUserTimestamps()
    {
        return $this->userTimestamps || $this->polymorphicTimestamps;
    }

    /**
     * Determine if the model uses timestamps with polymorphic actors
     *
     * @return bool
     */
    public function usesPolymorphicTimestamps()
    {
        return $this->polymorphicTimestamps;
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return property_exists($this, 'CREATED_AT') ? static::CREATED_AT : 'created_at';
    }

    /**
     * Get the name of the "created by id" column.
     *
     * @return string
     */
    public function getCreatedByIdColumn()
    {
        return property_exists($this, 'CREATED_BY_ID') ? static::CREATED_BY_ID : 'created_by_id';
    }

    /**
     * Get the name of the "updated by id" column.
     *
     * @return string
     */
    public function getCreatedByTypeColumn()
    {
        return property_exists($this, 'CREATED_BY_TYPE') ? static::CREATED_BY_TYPE : 'created_by_type';
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return property_exists($this, 'UPDATED_AT') ? static::UPDATED_AT : 'updated_at';
    }

    /**
     * Get the name of the "updated by id" column.
     *
     * @return string
     */
    public function getUpdatedByIdColumn()
    {
        return property_exists($this, 'UPDATED_BY_ID') ? static::UPDATED_BY_ID : 'updated_by_id';
    }

    /**
     * Get the name of the "updated by type" column.
     *
     * @return string
     */
    public function getUpdatedByTypeColumn()
    {
        return property_exists($this, 'UPDATED_BY_TYPE') ? static::UPDATED_BY_TYPE : 'updated_by_type';
    }

    /**
     * @return mixed
     */
    public function createdBy()
    {
        return $this->usesPolymorphicTimestamps() ? $this->morphTo('created_by', $this->getCreatedByTypeColumn(), $this->getCreatedByIdColumn()) : $this->belongsTo($this->getTimestampActorModel());
    }

    /**
     * @return mixed
     */
    public function updatedBy()
    {
        return $this->usesPolymorphicTimestamps() ? $this->morphTo('updated_by', $this->getUpdatedByTypeColumn(), $this->getUpdatedByIdColumn()) : $this->belongsTo($this->getTimestampActorModel());
    }
}