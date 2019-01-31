<?php

namespace App\Models;

use App\Models\Contracts\Active as ActiveContract;
use App\Models\Traits\Active;
use App\Models\Traits\DefaultOrder;
use App\Models\Traits\ExternalKey;
use App\Models\Traits\Searchable;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Mietvertraege extends Model implements ActiveContract
{
    use Searchable {
        Searchable::scopeSearch as scopeSearchFromTrait;
    }
    use DefaultOrder {
        DefaultOrder::scopeDefaultOrder as scopeDefaultOrderFromTrait;
    }

    use Active;
    use ExternalKey;

    public $timestamps = false;
    protected $table = 'MIETVERTRAG';
    protected $primaryKey = 'MIETVERTRAG_DAT';
    protected $externalKey = 'id';
    protected $searchableFields = ['id', 'MIETVERTRAG_VON', 'MIETVERTRAG_BIS'];
    protected $defaultOrder = ['MIETVERTRAG_BIS' => 'asc', 'MIETVERTRAG_VON' => 'desc', 'id' => 'desc'];
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('aktuell', function (Builder $builder) {
            $builder->where('MIETVERTRAG_AKTUELL', '1');
        });
    }

    public function mieter()
    {
        return $this->belongsToMany(
            Person::class,
            'PERSON_MIETVERTRAG',
            'PERSON_MIETVERTRAG_MIETVERTRAG_ID',
            'PERSON_MIETVERTRAG_PERSON_ID',
            'id',
            'id'
        )->wherePivot('PERSON_MIETVERTRAG_AKTUELL', '1');
    }

    public function einheit()
    {
        return $this->belongsTo(Einheiten::class, 'EINHEIT_ID', 'id');
    }

    public function getMieterNamenAttribute()
    {
        return $this->mieter->implode('full_name', '; ');
    }

    public function getStartDateFieldName()
    {
        return 'MIETVERTRAG_VON';
    }

    public function getEndDateFieldName()
    {
        return 'MIETVERTRAG_BIS';
    }

    public function scopeSearch($query, $tokens)
    {
        $query->with(['einheit', 'mieter'])->orWhere(function ($query) use ($tokens) {
            $query->searchFromTrait($tokens);
        })->orWhereHas('einheit', function ($query) use ($tokens) {
            $query->search($tokens);
        })->orWhereHas('mieter', function ($query) use ($tokens) {
            $query->search($tokens);
        });
        return $query;
    }

    public function scopeDefaultOrder($query)
    {
        $query->join(
            'EINHEIT', function ($join) {
            $join->on('MIETVERTRAG.EINHEIT_ID', '=', 'EINHEIT.id')
                ->where('EINHEIT.EINHEIT_AKTUELL', '1');
        })->orderBy(
            'EINHEIT.EINHEIT_KURZNAME'
        )->defaultOrderFromTrait()->select(DB::raw('MIETVERTRAG.*'), DB::raw('MIETVERTRAG.id as id'));
        return $query;
    }

    public function scopeMovingInOrder($query)
    {
        $query->orderBy(
            'MIETVERTRAG_VON', 'asc'
        );
        return $query;
    }

    public function scopeMovedInOrder($query)
    {
        $query->orderBy(
            'MIETVERTRAG_VON', 'desc'
        );
        return $query;
    }

    public function scopeMovingOutOrder($query)
    {
        $query->where(
            'MIETVERTRAG_BIS', '<>', '0000-00-00'
        )->orderBy(
            'MIETVERTRAG_BIS', 'asc'
        );
        return $query;
    }

    public function scopeMovedOutOrder($query)
    {
        $query->orderBy(
            'MIETVERTRAG_BIS', 'desc'
        );
        return $query;
    }

    public function basicRentDefinitions($from = null, $to = null)
    {
        return $this->rentDefinitions($from, $to)
            ->where(function ($query) {
                $query->where('KOSTENKATEGORIE', '=', 'Miete kalt')
                    ->orWhere('KOSTENKATEGORIE', '=', 'MHG')
                    ->orWhere('KOSTENKATEGORIE', '=', 'Mietminderung')
                    ->orWhere('KOSTENKATEGORIE', '=', 'MOD')
                    ->orWhere('KOSTENKATEGORIE', '=', 'Stellplatzmiete')
                    ->orWhere('KOSTENKATEGORIE', '=', 'Untermieter Zuschlag');
            });
    }

    public function rentDefinitions($from = null, $to = null)
    {
        if (is_string($from)) {
            $from = Carbon::parse($from);
        }
        if (is_string($to)) {
            $to = Carbon::parse($to);
        }
        $rentDefinitions = $this->morphMany(RentDefinition::class, 'rentDefinitions', 'KOSTENTRAEGER_TYP', 'KOSTENTRAEGER_ID', 'id');
        if ($from) {
            $rentDefinitions->whereDate('ANFANG', '<=', $to);
        }
        if ($to) {
            $rentDefinitions->where(function ($query) use ($from) {
                $query->whereDate('ENDE', '>=', $from)
                    ->orWhere('ENDE', '0000-00-00');
            });
        }
        return $rentDefinitions;
    }

    public function heatingExpenseDefinitions($from = null, $to = null)
    {
        return $this->rentDefinitions($from, $to)
            ->where(function ($query) {
                $query->where('KOSTENKATEGORIE', 'LIKE', 'Heizkostenabrechnung%')
                    ->orWhere('KOSTENKATEGORIE', '=', 'Heizkosten Vorauszahlung');
            });
    }

    public function operatingCostDefinitions($from = null, $to = null)
    {
        return $this->rentDefinitions($from, $to)
            ->where(function ($query) {
                $query->where('KOSTENKATEGORIE', 'LIKE', 'Betriebskostenabrechnung%')
                    ->orWhere('KOSTENKATEGORIE', 'LIKE', 'Kabel TV%')
                    ->orWhere('KOSTENKATEGORIE', 'LIKE', 'Kaltwasserabrechnung%')
                    ->orWhere('KOSTENKATEGORIE', '=', 'Nebenkosten Vorauszahlung')
                    ->orWhere('KOSTENKATEGORIE', 'LIKE', 'Thermenwartung%');
            });
    }

    public function openingBalanceDefinitions($from = null, $to = null)
    {
        return $this->rentDefinitions($from, $to)
            ->where('KOSTENKATEGORIE', '=', 'Saldo Vortrag Vorverwaltung');
    }

    public function postings($from = null, $to = null)
    {
        if (is_string($from)) {
            $from = Carbon::parse($from);
        }
        if (is_string($to)) {
            $to = Carbon::parse($to);
        }
        $rentDefinitions = $this->morphMany(Posting::class, 'postings', 'KOSTENTRAEGER_TYP', 'KOSTENTRAEGER_ID', 'id');
        if ($from) {
            $rentDefinitions->whereDate('DATUM', '>=', $from);
        }
        if ($to) {
            $rentDefinitions->whereDate('DATUM', '<=', $to);
        }
        return $rentDefinitions;
    }
}
