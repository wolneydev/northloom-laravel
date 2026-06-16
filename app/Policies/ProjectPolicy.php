<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Ensures a project can only be reached by its owner.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }

    private function owns(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
