<?php

namespace App\Models;

use App\Models\Contracts\Active as ActiveContract;
use App\Models\Traits\Active;
use App\Models\Traits\DefaultOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model implements ActiveContract
{
    use SoftDeletes;
    use Active;
    use DefaultOrder;

    public $timestamps = true;
    protected $table = 'jobs';
    protected $guarded = [];
    protected $defaultOrder = ['join_date' => 'desc', 'leave_date' => 'desc'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('employer_and_title', function (Builder $builder) {
            $builder->with(['employer', 'title']);
        });
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'employer_id', 'id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'employee_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function getStartDateFieldName()
    {
        return 'join_date';
    }

    public function getEndDateFieldName()
    {
        return 'leave_date';
    }
}
