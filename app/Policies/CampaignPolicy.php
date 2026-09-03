<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function view(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin() || $campaign->user_id === $user->id;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id && $campaign->isEditable();
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        $isSafeToDelete = in_array($campaign->status, [
            Campaign::STATUS_DRAFT,
            Campaign::STATUS_PENDING,
            Campaign::STATUS_REJECTED,
        ], true);

        return $isSafeToDelete && ($user->isAdmin() || $campaign->user_id === $user->id);
    }

    public function submit(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id
            && $campaign->isEditable()
            && $user->canSubmitCampaign();
    }

    public function review(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin() && $campaign->status === Campaign::STATUS_PENDING;
    }

    /** Pemilik kampanye boleh mengelola pencairan & LPJ setelah disetujui. */
    public function manageFunds(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id && $campaign->isPublished();
    }
}
