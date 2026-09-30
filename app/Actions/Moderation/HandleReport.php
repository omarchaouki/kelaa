<?php

namespace App\Actions\Moderation;

use App\Enums\ReportStatus;
use App\Models\Business;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use App\Notifications\ContentRejected;
use App\Support\Moderation\ModerationGate;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Closes a report, optionally acting on the reported content. Other open
 * reports on the same content are closed with it.
 */
class HandleReport
{
    public function __construct(
        private readonly ModerateReview $reviews,
        private readonly ModerateReply $replies,
        private readonly ModerateBusiness $businesses,
    ) {}

    public function dismiss(User $moderator, Report $report, string $note): void
    {
        $this->close($moderator, $report, ReportStatus::Dismissed, $note, 'report.dismissed');
    }

    /**
     * Upholds the report and removes the content from public view:
     * reviews and replies are hidden, photos deleted, businesses suspended.
     */
    public function uphold(User $moderator, Report $report, string $note): void
    {
        ModerationGate::asModerator($moderator, function () use ($moderator, $report, $note): void {
            $target = $report->reportable;

            match (true) {
                $target instanceof Review => $target->isPublished() ? $this->reviews->hide($moderator, $target, $note) : null,
                $target instanceof ReviewReply => $this->replies->hide($moderator, $target, $note),
                $target instanceof Business => $this->businesses->suspend($moderator, $target, $note),
                $target instanceof Media => $this->deletePhoto($moderator, $target, $note),
                default => null,
            };

            $this->close($moderator, $report, ReportStatus::Resolved, $note, 'report.resolved');
        });
    }

    private function deletePhoto(User $moderator, Media $media, string $note): void
    {
        activity('moderation')->causedBy($moderator)
            ->withProperties(['media_id' => $media->id, 'model_type' => $media->model_type, 'model_id' => $media->model_id, 'reason' => $note])
            ->log('photo.removed');

        $owner = $media->model instanceof Review ? $media->model->user : null;
        $mediaId = $media->id;
        $media->delete();

        $owner?->notify(new ContentRejected('photo', $mediaId, $note));
    }

    private function close(User $moderator, Report $report, ReportStatus $status, string $note, string $event): void
    {
        ModerationGate::asModerator($moderator, function () use ($moderator, $report, $status, $note, $event): void {
            DB::transaction(function () use ($moderator, $report, $status, $note, $event): void {
                Report::query()
                    ->where('reportable_type', $report->reportable_type)
                    ->where('reportable_id', $report->reportable_id)
                    ->where('status', ReportStatus::Open)
                    ->update([
                        'status' => $status->value,
                        'handled_by' => $moderator->id,
                        'handled_at' => now(),
                        'resolution_note' => mb_substr($note, 0, 255),
                    ]);

                activity('moderation')->performedOn($report)->causedBy($moderator)
                    ->withProperties(['note' => $note])->log($event);
            });
        });
    }
}
