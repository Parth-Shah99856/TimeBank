<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\ServiceRequestOtp;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Safe transactional user deletion service.
 *
 * Relationship audit (derived from schema):
 *
 *   RESTRICT on delete (must handle first):
 *     - transactions.from_user_id  ? immutable; set null via DB update
 *     - transactions.to_user_id    ? immutable; set null via DB update
 *     - projects.lead_user_id      ? reassign or delete led projects
 *
 *   CASCADE on delete (DB handles automatically):
 *     - service_requests.requester_id  ? cascades (messages, otps, reviews cascade too)
 *     - service_requests.provider_id   ? cascades
 *     - idea_collaborators.user_id     ? cascades
 *     - project_members.user_id        ? cascades
 *     - service_request_messages.sender_id ? cascades (via service_request cascade)
 *     - service_request_otps.user_id   ? cascades
 *
 *   CASCADE via parent:
 *     - ideas ? collaborators, projects cascade
 *     - projects ? members, tasks cascade
 *     - services ? service_requests cascade
 *     - service_requests ? messages, otps, reviews cascade
 *
 *   INDEPENDENT (not FK-linked to user):
 *     - notifications (morphs notifiable) ? delete manually
 *     - sessions ? not modelled; cleaned by session driver
 *     - password_reset_tokens ? not modelled; email-keyed
 *
 *   STAYS:
 *     - reviews where reviewer_id = user ? cascade via service_request
 *     - reviews where reviewee_id = user ? cascade via service_request
 */
class AdminUserDeletionService
{
    /**
     * Perform complete safe user deletion inside a transaction.
     *
     * @throws \RuntimeException if admin tries to delete themselves
     * @throws \Throwable on any DB error (rollback is automatic via DB::transaction)
     */
    public function delete(User $admin, User $target): void
    {
        if ($admin->id === $target->id) {
            throw new \RuntimeException('An administrator cannot delete their own account.');
        }

        DB::transaction(function () use ($admin, $target): void {
            $meta = $this->collectMeta($target);

            // 1. Nullify immutable transaction ledger references (RESTRICT constraints)
            //    Transactions cannot be deleted (model guard), so we null the FK.
            Transaction::where('from_user_id', $target->id)
                ->update(['from_user_id' => null]);

            Transaction::where('to_user_id', $target->id)
                ->update(['to_user_id' => null]);

            // 2. Handle led projects (RESTRICT constraint on lead_user_id).
            //    We delete projects led by this user (members, tasks, service_requests cascade).
            $ledProjectIds = Project::where('lead_user_id', $target->id)->pluck('id');
            if ($ledProjectIds->isNotEmpty()) {
                // Delete tasks for led projects
                ProjectTask::whereIn('project_id', $ledProjectIds)->delete();
                // Delete memberships for led projects
                ProjectMember::whereIn('project_id', $ledProjectIds)->delete();
                // Delete led projects themselves
                Project::whereIn('id', $ledProjectIds)->delete();
            }

            // 3. Nullify notifications using morph relationship.
            //    The notifications table uses notifiable_type / notifiable_id.
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $target->id)
                ->delete();

            // 4. Delete sessions for this user.
            DB::table('sessions')
                ->where('user_id', $target->id)
                ->delete();

            // 5. Delete password reset tokens.
            DB::table('password_reset_tokens')
                ->where('email', $target->email)
                ->delete();

            // 6. Now delete the user.
            //    The DB cascades will handle:
            //      - services ? service_requests ? messages, otps, reviews
            //      - idea_collaborators
            //      - project_members (for projects led by others)
            //      - project_tasks (assigned_to is not a FK constrained field — check schema)
            //      - ideas ? collaborators, projects (idea.user_id cascades; but project has
            //                                         restrict on lead_user_id which was handled above)
            $target->delete();

            // 7. Audit log
            AdminAuditLog::record(
                admin: $admin,
                action: 'user.deleted',
                targetType: 'user',
                targetId: $meta['id'],
                targetLabel: $meta['name'],
                meta: $meta,
            );
        });
    }

    /**
     * Collect summary metadata about the user before deletion.
     */
    public function collectMeta(User $target): array
    {
        return [
            'id'               => $target->id,
            'name'             => $target->name,
            'email'            => $target->email,
            'role'             => $target->role,
            'services_count'   => Service::where('user_id', $target->id)->count(),
            'requests_count'   => ServiceRequest::where('requester_id', $target->id)
                                    ->orWhere('provider_id', $target->id)->count(),
            'ideas_count'      => Idea::where('user_id', $target->id)->count(),
            'projects_led'     => Project::where('lead_user_id', $target->id)->count(),
            'time_balance'     => (string) $target->time_balance,
            'created_at'       => $target->created_at?->toIso8601String(),
        ];
    }
}