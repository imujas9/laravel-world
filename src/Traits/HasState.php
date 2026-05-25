<?php

namespace Imujas9\World\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Imujas9\World\DTO\StateData;
use Imujas9\World\Facades\State as StateFacade;
use Imujas9\World\Models\State;

trait HasState
{
    // Override in your model to use code-based FK instead:
    //   protected string $stateForeignKey = 'state_code';
    //   protected string $stateOwnerKey   = 'code';
    protected string $stateForeignKey = 'state_id';
    protected string $stateOwnerKey   = 'id';

    public function getStateAttribute(): ?StateData
    {
        $fk = $this->{$this->stateForeignKey} ?? null;

        if (empty($fk)) {
            return null;
        }

        if ($this->relationLoaded('state')) {
            $model = $this->getRelation('state');
            return $model ? StateData::fromEloquent($model) : null;
        }

        return $this->stateOwnerKey === 'id'
            ? StateFacade::find((int) $fk)
            : StateFacade::findByCode((string) $fk);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, $this->stateForeignKey, $this->stateOwnerKey);
    }
}
