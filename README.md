# Laravel User Timestamps

This is a drop in replacement for `HasTimestamps` trait in Laravel. It will add the additional fields. `created_by_id` and `updated_by_id` of the user that performed the operation.

It also supports polymorphic relationship which will add `created_by_type` and `updated_by_type` fields.

It also has support for `SoftDeletes` replacing Laravel's built in `SoftDeletes` trait to include `deleted_by_id` for the user who performed the soft delete. 

## Installation

You can install the package via composer:

```bash
composer require robjbrain/laravel-user-timestamps
```

## Usage

Add the `CacheMutationsTrait` trait to a model you like to cache the mutations of.

```php
use Robjbrain\LaravelUserTimestamps\HasUserTimestamps;
use Robjbrain\LaravelUserTimestamps\UserSoftDeletes;

class YourEloquentModel extends Model
{
    use HasUserTimestamps;
    use UserSoftDeletes;
    
    // This will behave the same UserTimestamps
    public $timestamps = true;
    
    // This will utilise the updated_by_id and created_by_id fields
    public $userTimestamps = true;
    
    // This will utilise the updated_by_type and created_by_type fields
    public $polymorphicTimestamps = true;
    
    // This will utilise the deleted_by_id field
    public $userSoftDeletes = true;
    
    // This will utilise the deleted_by_type field
    public $polymorphicSoftDeletes = true;
}
```

If you are using `$polymorphicTimestamps` you don't need to set `$userTimestamps` or `$timestamps` to true.

If you are using `$userTimestamps` you do not need to set `$timestamps` to true.