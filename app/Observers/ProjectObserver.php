<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ProjectLog;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function updated(Project $project): void
    {
        if (! $project->wasChanged('status')) {
            return;
        }

        ProjectLog::query()->create([
            'project_id' => $project->getKey(),
            'status_lama' => $project->getOriginal('status'),
            'status_baru' => $project->status,
            'diubah_oleh' => Auth::id(),
        ]);
    }
}
