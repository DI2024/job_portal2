<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

        /**
         * Get the user that owns the Job
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
         */
        public function jobType(): BelongsTo
        {
            return $this->belongsTo(JobType::class);
        }
        public function category(): BelongsTo
        {
            return $this->belongsTo(Category::class);
        }

        /**
         * Get all of the applications for the Job
         *
         * @return \Illuminate\Database\Eloquent\Relations\HasMany
         */
        public function applications(): HasMany
        {
            return $this->hasMany(JobApplication::class);
        }

        /**
         * Get the user that owns the Job
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
         */
        public function user() {
            return $this->belongsTo(User::class);
        }

}
